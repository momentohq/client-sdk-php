<?php
ini_set('display_errors', 1);
require 'vendor/autoload.php';

use Momento\Auth\EnvMomentoTokenProvider;
use Momento\Cache\SimpleCacheClient;
use Momento\Config\Configurations\Laptop;

$cacheName = "php-loadgen";
$authProvider = new EnvMomentoTokenProvider("MOMENTO_AUTH_TOKEN");
$config = Laptop::latest();
$grpcConfig = $config->getTransportStrategy()->getGrpcConfig();
$config = $config->withTransportStrategy(
    $config->getTransportStrategy()->withGrpcConfig(
        $grpcConfig->withForceNew(true)
    )
);
$defaultTtl = 600;

$gets = [];
$sets = [];

$startTime = -hrtime(true);
$client = new SimpleCacheClient($config, $authProvider, $defaultTtl);
$startup = $startTime + hrtime(true);

$errors = 0;
$misses = 0;
$value = str_repeat("x", 100);
for ($i = 0; $i < 5; $i++) {
    $setStart = -hrtime(true);
    $response = $client->set($cacheName, "myKey", $value);
    if ($err = $response->asError()) {
        $errors++;
        continue;
    } else {
        $setTime = $setStart + hrtime(true);
    }

    $getStart = -hrtime(true);
    $response = $client->get($cacheName, "myKey");
    if ($err = $response->asError()) {
        $errors++;
        continue;
    } elseif ($response->asMiss()) {
        $misses++;
        continue;
    } else {
        $getTime = $getStart + hrtime(true);
    }
    $gets[] = $getTime;
    $sets[] = $setTime;
}

$startupFile = fopen("startups.txt", "a");
$getsFile = fopen("gets.txt", "a");
$setsFile = fopen("sets.txt", "a");
fwrite($startupFile, "$startup\n");
fwrite($getsFile, join("\n", $gets) . "\n");
fwrite($setsFile, join("\n", $sets) . "\n");
fclose($startupFile);
fclose($getsFile);
fclose($setsFile);

$endTime = $startTime + hrtime(true);
$endSecs = $endTime / 1e+9;
print "misses: $misses, errors: $errors in $endTime nanoseconds ($endSecs secs)\n";
