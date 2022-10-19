<?php

require "vendor/autoload.php";

use Momento\Auth\EnvMomentoTokenProvider;
use Momento\Cache\SimpleCacheClient;

$MOMENTO_AUTH_TOKEN = getenv("MOMENTO_AUTH_TOKEN");
$CACHE_NAME = getenv("CACHE_NAME");
$LIST_NAME = "example-list";
$PUSH_FRONT_VALUE = "MyPushFrontValue";
$PUSH_BACK_VALUE = "MyPushBackValue";
$ITEM_DEFAULT_TTL_SECONDS = 60;

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

// Push front
print "Pushing value: $PUSH_FRONT_VALUE to list: $LIST_NAME\n";
$response = $client->listPushFront($CACHE_NAME, $LIST_NAME, $PUSH_FRONT_VALUE, true, 6000);
if ($response->asSuccess()) {
    print "SUCCESS: Pushed front - value: " . $PUSH_FRONT_VALUE . " list: " . $LIST_NAME . "\n";
} elseif ($response->asError()) {
    print "Error pushing a value to front: " . $response->asError()->message() . "\n";
    exit;
}

// Push back
print "Pushing value: $PUSH_BACK_VALUE to list: $LIST_NAME\n";
$response = $client->listPushBack($CACHE_NAME, $LIST_NAME, $PUSH_BACK_VALUE, true, 6000);
if ($response->asSuccess()) {
    print "SUCCESS: Pushed back - value: " . $PUSH_BACK_VALUE . " list: " . $LIST_NAME . "\n";
} elseif ($response->asError()) {
    print "Error pushing a value to front: " . $response->asError()->message() . "\n";
    exit;
}

// List fetch
print "Fetching list: $LIST_NAME\n";
$response = $client->listFetch($CACHE_NAME, $LIST_NAME);
if ($response->asHit()) {
    print "HIT: Fetched values from list (" . $LIST_NAME . "): ";
    foreach ($response->asHit()->values() as $value) {
        print $value . "\t";
    }
    print "\n";
} elseif ($response->asMiss()) {
    print "Fetch operation was a MISS\n";
} elseif ($response->asError()) {
    print "Error fetching a list: " . $response->asError()->message() . "\n";
    exit;
}

// Pop front
print "Popping front list: $LIST_NAME\n";
$response = $client->listPopFront($CACHE_NAME, $LIST_NAME);
if ($response->asHit()) {
    print "HIT: Popped front - value: " . $response->asHit()->value() . " list: " . $LIST_NAME . "\n";

} elseif ($response->asMiss()) {
    print "Pop Front operation was a MISS\n";
} elseif ($response->asError()) {
    print "Error popping a value from front: " . $response->asError()->message() . "\n";
    exit;
}

// Pop back
print "Popping back list: $LIST_NAME\n";
$response = $client->listPopBack($CACHE_NAME, $LIST_NAME);
if ($response->asHit()) {
    print "HIT: Popped back - value: " . $response->asHit()->value() . " list: " . $LIST_NAME . "\n";
} elseif ($response->asMiss()) {
    print "Pop Back operation was a MISS\n";
} elseif ($response->asError()) {
    print "Error popping a value from back: " . $response->asError()->message() . "\n";
    exit;
}
printBanner("*                       Momento Example End                      *");
