<?php
// redis_benchmark_concurrent.php
// Forks multiple workers, each with its own Redis client, no pipelining

declare(strict_types=1);

// === Configuration ===
$redisHost = '172.31.17.173';
$redisPort = 6379;
$testDurationSeconds = 60;
$payloadSize = 128; // bytes per SET
$maxRps = 4000;         // max requests per second per worker
$throttleMicros = (int)floor(1e6 / $maxRps);
$concurrency = 20;      // number of parallel workers

function run_worker(int $workerId, string $host, int $port, int $duration, int $payloadSize, int $throttleMicros): void {
    // Initialize Redis client
    $redis = new Redis();
    if (!@$redis->connect($host, $port)) {
        fwrite(STDERR, "Worker {$workerId}: Error connecting to Redis at {$host}:{$port}\n");
        exit(1);
    }

    $payload = str_repeat('x', $payloadSize);
    $latencies = [];
    $successes = 0;
    $errors = 0;
    $requests = 0;
    $startTime = hrtime(true);

    // Benchmark loop
    while ((hrtime(true) - $startTime) / 1e9 < $duration) {
        $key = "bench:{$workerId}:{$requests}";
        $t0 = hrtime(true);
        try {
            $ok = $redis->set($key, $payload);
            $dt = (hrtime(true) - $t0) / 1e6;
            if ($ok) {
                $successes++;
                $latencies[] = $dt;
            } else {
                $errors++;
                $err = method_exists($redis, 'getLastError') ? $redis->getLastError() : 'unknown';
                fwrite(STDERR, "Worker {$workerId}: SET false for {$key}, Redis error: {$err}\n");
                if (method_exists($redis, 'clearLastError')) {
                    $redis->clearLastError();
                }
            }
        } catch (Throwable $e) {
            $errors++;
            fwrite(STDERR, "Worker {$workerId}: Exception on {$key}: {$e->getMessage()}\n");
        }
        $requests++;
        // throttle to cap RPS
        if ($throttleMicros > 0) {
            usleep($throttleMicros);
        }
    }

    // Compute metrics
    $durationActual = (hrtime(true) - $startTime) / 1e9;
    sort($latencies);
    $count = count($latencies);
    $avg = $count > 0 ? array_sum($latencies) / $count : 0;
    $p90 = $count > 0 ? $latencies[(int)floor(($count - 1) * 0.90)] : 0;
    $p99 = $count > 0 ? $latencies[(int)floor(($count - 1) * 0.99)] : 0;
    $p999Idx = (int)floor(($count - 1) * 0.999);
    $p999 = $count > 0 ? $latencies[min($p999Idx, $count - 1)] : 0;
    $tps = $durationActual > 0 ? $successes / $durationActual : 0;

    // Report
    echo "[Worker {$workerId}] Duration: " . round($durationActual,2) . "s | Requests: {$requests} | Success: {$successes} | Errors: {$errors} | TPS: " . round($tps,2) . " | Avg: " . round($avg,2) . "ms | P90: {$p90}ms | P99: {$p99}ms | P999: {$p999}ms\n";
    $redis->close();
    exit(0);
}

// === Fork workers ===
$children = [];
for ($i = 0; $i < $concurrency; $i++) {
    $pid = pcntl_fork();
    if ($pid === -1) {
        die("Failed to fork worker {$i}\n");
    } elseif ($pid === 0) {
        run_worker($i, $redisHost, $redisPort, $testDurationSeconds, $payloadSize, $throttleMicros);
    } else {
        $children[] = $pid;
    }
}

// Wait for all
foreach ($children as $pid) {
    pcntl_waitpid($pid, $status);
}

echo "All {$concurrency} workers completed.\n";
exit(0);
