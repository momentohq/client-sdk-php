<?php
ini_set('display_errors', 1);
require 'vendor/autoload.php';

use Momento\Auth\EnvMomentoTokenProvider;
use Momento\Cache\SimpleCacheClient;
use Momento\Config\Configurations\Laptop;

$cacheName = "cache";
$authProvider = new \Momento\Auth\EnvMomentoTokenProvider("MOMENTO_AUTH_TOKEN");
$config = Laptop::latest();
$defaultTtl = 600;

$startTime = -hrtime(true);
$client = new SimpleCacheClient($config, $authProvider, $defaultTtl);

$errors = 0;
$misses = 0;
$hits = 0;
$value = str_repeat("x", 100);
for ($i = 0; $i < 5; $i++) {
    $response = $client->set($cacheName, "myKey", $value);
    if ($err = $response->asError()) {
        $errors++;
    }

    $response = $client->get($cacheName, "myKey");
    if ($err = $response->asError()) {
        $errors++;
    } elseif ($response->asMiss()) {
        $misses++;
    } else {
        $hits++;
    }
}

$endTime = $startTime + hrtime(true);
$endSecs = $endTime / 1e+9;
print "hits: $hits, misses: $misses, errors: $errors in $endTime nanoseconds ($endSecs secs)\n";

