<?php

require "vendor/autoload.php";

use Momento\Auth\EnvMomentoTokenProvider;
use Momento\Cache\SimpleCacheClient;

$MOMENTO_AUTH_TOKEN = getenv("MOMENTO_AUTH_TOKEN");
$CACHE_NAME = getenv("CACHE_NAME");
$DICTIONARY_NAME = "exmaple-dictionary";
$FIELD = "MyField";
$VALUE = "MyValue";
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
}
if ($response->asAlreadyExists()) {
    print "Cache " . $CACHE_NAME . " already exists.\n";
}
if ($response->asError()) {
    print "Error creating cache: " . $response->asError()->message() . "\n";
    exit;
}

// Dictionary Set
print "Setting field: $FIELD and value: $VALUE in dictionary: $DICTIONARY_NAME\n";
$response = $client->dictionarySet($CACHE_NAME, $DICTIONARY_NAME, $FIELD, $VALUE, false, $ITEM_DEFAULT_TTL_SECONDS);
if ($response->asError()) {
    print "Error setting a value in a dictionary: " . $response->asError()->message() . "\n";
    exit;
} else {
    print "SUCCESS: Dictionary set - field: " . $FIELD . " value: " . $VALUE . " dictionary: " . $DICTIONARY_NAME . "\n";
}

// Dictionary Get
print "Getting field: $FIELD in dictionary: $DICTIONARY_NAME\n";
$response = $client->dictionaryGet($CACHE_NAME, $DICTIONARY_NAME, $FIELD);
if ($response->asError()) {
    print "Error getting a value in a dictionary: " . $response->asError()->message() . "\n";
    exit;
}
if ($response->asMiss()) {
    print "Get operation was a MISS\n";
} else {
    print "HIT: Dictionary get - field: " . $FIELD . " value: " . $response->asHit()->value() . " dictionary: " . $DICTIONARY_NAME . "\n";
}
printBanner("*                       Momento Example End                      *");