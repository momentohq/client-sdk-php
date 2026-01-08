<?php
declare(strict_types=1);

require "vendor/autoload.php";

use Momento\Auth\CredentialProvider;
use Momento\Cache\CacheClient;
use Momento\Config\Configurations\Laptop;

class LoadGenerator
{
    private const CACHE_NAME = "php-loadgen";
    private int $totalNumberOfOperationsToExecute;
    private string $cacheValue;
    private int $startTime;
    public array $getNanoseconds = [];
    public array $setNanoseconds = [];
    public int $errors = 0;

    public function __construct(
        int $cacheItemPayloadBytes,
        int $totalNumberOfOperationsToExecute
    )
    {
        $this->totalNumberOfOperationsToExecute = $totalNumberOfOperationsToExecute;
        $this->cacheValue = str_repeat("x", $cacheItemPayloadBytes);
    }

    public function nanosecondsToSeconds(int $nanoseconds): float
    {
        return $nanoseconds / 1e+9;
    }

    private function setUp(): void
    {
        $client = $this->getClient();
        $response = $client->createCache(self::CACHE_NAME);
        if ($error = $response->asError()) {
            throw $error->innerException();
        }
        $this->startTime = -hrtime(true);
    }

    private function cleanUp(): void
    {
        $client = $this->getClient();
        $response = $client->deleteCache(self::CACHE_NAME);
        if ($error = $response->asError()) {
            throw $error->innerException();
        }
    }

    private function getClient(): CacheClient
    {
        $cache_item_ttl_seconds = 60;
        $authProvider = CredentialProvider::fromEnvironmentVariablesV2();
        $configuration = Laptop::latest();
        return new CacheClient(
            $configuration, $authProvider, $cache_item_ttl_seconds
        );
    }

    public function run(): void
    {
        $this->setUp();
        for ($i = 1; $i <= $this->totalNumberOfOperationsToExecute; $i++) {
            $client = $client ?? $this->getClient();
            if ($i % 1000 == 0) {
                print "$i operations in " . $this->nanosecondsToSeconds($this->startTime + hrtime(true)) . " seconds.\n";
            }

            $cache_key = "single-worker-operation-$i";

            // execute and time a set
            $hrtime = -hrtime(true);
            $response = $client->set(self::CACHE_NAME, $cache_key, $this->cacheValue);
            if ($response->asSuccess()) {
                $setHrtime = $hrtime + hrtime(true);
            } else {
                print "Set error: $response\n";
                $this->errors++;
                continue;
            }

            // execute and time a get
            $hrtime = -hrtime(true);
            $response = $client->get(self::CACHE_NAME, $cache_key);
            if ($response->asHit()) {
                $getHrtime = $hrtime + hrtime(true);
            } else {
                print "Get error: $response\n";
                $this->errors++;
                continue;
            }
            $this->setNanoseconds[] = $setHrtime;
            $this->getNanoseconds[] = $getHrtime;
        }
        $this->cleanUp();
    }
}

$loadGenerator = new LoadGenerator(100, 100000);
$loadGenerator->run();
$gets = fopen("gets.txt", "w");
while ($nextGet = array_shift($loadGenerator->getNanoseconds)) {
    fwrite($gets, "$nextGet\n");
}
fclose($gets);

$sets = fopen("sets.txt", "w");
while ($nextSet = array_shift($loadGenerator->setNanoseconds)) {
    fwrite($sets, "$nextSet\n");
}
fclose($sets);
print "Completed with {$loadGenerator->errors} errors.\n";
