<?php

require "vendor/autoload.php";

use Momento\Cache\SimpleCacheClient;

$MOMENTO_AUTH_TOKEN = getenv("MOMENTO_AUTH_TOKEN");
$CACHE_NAME = "cache";
$ITEM_DEFAULT_TTL_SECONDS = 60;
$KEY = "MyKey";
$VALUE = "MyValue";

function printBanner(string $message) : void {
    $line = "******************************************************************";
    print "$line\n$message\n$line\n";
}

function createCache(SimpleCacheClient $client, string $cacheName) : void {
    try {
        $client->createCache($cacheName);
    } catch (\Momento\Cache\Errors\AlreadyExistsError $e) {}
}

function listCaches(SimpleCacheClient $client) : void {
    $result = $client->listCaches();
    while (true) {
        foreach ($result->caches() as $cache) {
            print "- {$cache->name()}\n";
        }
        $nextToken = $result->nextToken();
        if (!$nextToken) {
            break;
        }
        $result = $client->listCaches($nextToken);
    }
}

printBanner("*                      Momento Example Start                     *");
$client = new SimpleCacheClient($MOMENTO_AUTH_TOKEN, $ITEM_DEFAULT_TTL_SECONDS);
createCache($client, $CACHE_NAME);
listCaches($client);
print "Setting key $KEY to value $VALUE\n";
$client->set($CACHE_NAME, $KEY, $VALUE);
$response = $client->get($CACHE_NAME, $KEY);
print "Look up status is: {$response->status()}\n";
print "Look up value is: {$response->value()}\n";
printBanner("*                       Momento Example End                      *");
