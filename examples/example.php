<?php

require "vendor/autoload.php";

use Momento\Auth\EnvMomentoTokenProvider;
use Momento\Cache\SimpleCacheClient;

$MOMENTO_AUTH_TOKEN = getenv("MOMENTO_AUTH_TOKEN");
$CACHE_NAME = getenv("CACHE_NAME");
$ITEM_DEFAULT_TTL_SECONDS = 60;
$KEY = "MyKey";
$VALUE = "MyValue";

function printBanner(string $message): void
{
    $line = "******************************************************************";
    print "$line\n$message\n$line\n";
}

printBanner("*                      Momento Example Start                     *");
// Setup
$authProvider = new EnvMomentoTokenProvider("MOMENTO_AUTH_TOKEN");
$client = new SimpleCacheClient($authProvider, $ITEM_DEFAULT_TTL_SECONDS);

// Ensure test cache exists
$response = $client->createCache($CACHE_NAME);
if ($response->asSuccess()) {
    print "Created cache " . $CACHE_NAME . "\n";
}
if ($response->asAlreadyExists()) {
    print "Cache " . $CACHE_NAME . " already exists.\n";
}
if ($response->asError()) {
    print "Error creating cache: " . $response->asError()->message() . "\n";
    exit;
}

// List cache
$response = $client->listCaches();
if ($response->asError()) {
    print "Error listing cache: " . $response->asError()->message() . "\n";
    exit;
}
while (true) {
    print "SUCCESS: List caches: ";
    foreach ($response->asSuccess()->caches() as $cache) {
        $cacheName = $cache->name();
        print "$cacheName\t";
    }
    $nextToken = $response->asSuccess()->nextToken();
    if (!$nextToken) {
        break;
    }
    $response = $client->listCaches($nextToken);
}
print "\n";

// Set
print "Setting key: $KEY to value: $VALUE\n";
$response = $client->set($CACHE_NAME, $KEY, $VALUE);
if ($response->asError()) {
    print "Error setting cache: " . $response->asError()->message() . "\n";
    exit;
} else {
    print "SUCCESS: - Set key: " . $KEY . " value: " . $VALUE . " cache: " . $CACHE_NAME . "\n";
}

// Get
print "Getting value for key: $KEY\n";
$response = $client->get($CACHE_NAME, $KEY);
if ($response->asError()) {
    print "Error getting cache: " . $response->asError()->message() . "\n";
    exit;
}
if ($response->asMiss()) {
    print "Get operation was a MISS\n";
} else {
    print "SUCCESS: - Get key: " . $KEY . " value: " . $response->asHit()->value() . " cache: " . $CACHE_NAME . "\n";
}

printBanner("*                       Momento Example End                      *");
