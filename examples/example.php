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
} elseif ($response->asError()) {
    print "Error creating cache: " . $response->asError()->message() . "\n";
    exit;
} elseif ($response->asAlreadyExists()) {
    print "Cache " . $CACHE_NAME . " already exists.\n";
}

// List cache
$response = $client->listCaches();
if ($response->asSuccess()) {
    while (true) {
        print "SUCCESS: List caches: \n";
        foreach ($response->asSuccess()->caches() as $cache) {
            $cacheName = $cache->name();
            print "$cacheName\n";
        }
        $nextToken = $response->asSuccess()->nextToken();
        if (!$nextToken) {
            break;
        }
        $response = $client->listCaches($nextToken);
    }
    print "\n";
} elseif ($response->asError()) {
    print "Error listing cache: " . $response->asError()->message() . "\n";
    exit;
}

// Set
print "Setting key: $KEY to value: $VALUE\n";
$response = $client->set($CACHE_NAME, $KEY, $VALUE);
if ($response->asSuccess()) {
    print "SUCCESS: - Set key: " . $KEY . " value: " . $VALUE . " cache: " . $CACHE_NAME . "\n";
} elseif ($response->asError()) {
    print "Error setting key: " . $response->asError()->message() . "\n";
    exit;
}

// Get
print "Getting value for key: $KEY\n";
$response = $client->get($CACHE_NAME, $KEY);
if ($response->asHit()) {
    print "SUCCESS: - Get key: " . $KEY . " value: " . $response->asHit()->value() . " cache: " . $CACHE_NAME . "\n";
} elseif ($response->asMiss()) {
    print "Get operation was a MISS\n";
} elseif ($response->asError()) {
    print "Error getting cache: " . $response->asError()->message() . "\n";
    exit;
}

printBanner("*                       Momento Example End                      *");
