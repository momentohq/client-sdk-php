<?php

require "vendor/autoload.php";

use Momento\Auth\CredentialProvider;
use Momento\Cache\CacheClient;
use Momento\Config\Configurations\Laptop;
use Momento\Logging\StderrLoggerFactory;
use Momento\Requests\CollectionTtl;
use Psr\Log\LoggerInterface;

$CACHE_NAME = uniqid("php-sorted-set-example-");
$SET_NAME = "example-sorted-set";
$ITEM_DEFAULT_TTL_SECONDS = 60;

// Setup
$authProvider = CredentialProvider::fromEnvironmentVariable("MOMENTO_API_KEY");
$configuration = Laptop::latest(new StderrLoggerFactory());
$client = new CacheClient($configuration, $authProvider, $ITEM_DEFAULT_TTL_SECONDS);
$logger = $configuration->getLoggerFactory()->getLogger("ex:");

function printBanner(string $message, LoggerInterface $logger): void
{
    $line = "******************************************************************";
    $logger->info($line);
    $logger->info($message);
    $logger->info($line);
}

printBanner("*                      Momento Example Start                     *", $logger);

// Ensure test cache exists
$response = $client->createCache($CACHE_NAME);
if ($response->asSuccess()) {
    $logger->info("Created cache " . $CACHE_NAME . "\n");
} elseif ($response->asError()) {
    $logger->info("Error creating cache: " . $response->asError()->message() . "\n");
    exit(1);
} elseif ($response->asAlreadyExists()) {
    $logger->info("Cache " . $CACHE_NAME . " already exists.\n");
}

// The CollectionTtl object is used to control TTLs for collections as they
// are updated. By default, it is configured to reset the TTL for the collection
// to the client's default TTL each time the collection is updated.
$collectionTtl = new CollectionTtl();

// add element to sorted set
$logger->info("Adding element 'one' to sorted set $SET_NAME\n");
$response = $client->sortedSetPutElement($CACHE_NAME, $SET_NAME, "one", 1.0, $collectionTtl);
if ($response->asSuccess()) {
    $logger->info("SUCCESS: Added element to set\n");
} elseif ($err = $response->asError()) {
    $logger->info("Error adding element to set: {$err->message()}\n");
    exit(1);
}

// add multiple elements to sorted set
$logger->info("Adding elements 'two', 'three', 'four', 'five' to sorted set $SET_NAME\n");
$response = $client->sortedSetPutElements($CACHE_NAME, $SET_NAME, [
    "two" => 2.0,
    "three" => 3.0,
    "four" => 4.0,
    "five" => 5.0
], $collectionTtl);
if ($response->asSuccess()) {
    $logger->info("SUCCESS: Added elements to set\n");
} elseif ($err = $response->asError()) {
    $logger->info("Error adding elements to set: {$err->message()}\n");
    exit(1);
}

// fetch sorted set by rank (0 to -1 for all elements) in ascending order
$logger->info("Fetching sorted set $SET_NAME\n");
$response = $client->sortedSetFetchByRank($CACHE_NAME, $SET_NAME, 0, -1);
if ($response->asHit()) {
    $logger->info("SUCCESS: Sorted set $SET_NAME: " . implode(', ', $response->asHit()->valuesArray()) . "\n");
} elseif ($err = $response->asMiss()) {
    $logger->info("Sorted set $SET_NAME not found\n");
} elseif ($err = $response->asError()) {
    $logger->info("Error fetching sorted set: {$err->message()}\n");
    exit(1);
}

// fetch sorted set by score (0 to 2) in ascending order
$logger->info("Fetching sorted set $SET_NAME by score\n");
$response = $client->sortedSetFetchByScore($CACHE_NAME, $SET_NAME, 0, 2);
if ($response->asHit()) {
    $logger->info("SUCCESS: Sorted set $SET_NAME: " . implode(', ', $response->asHit()->valuesArray()) . "\n");
} elseif ($err = $response->asMiss()) {
    $logger->info("Sorted set $SET_NAME not found\n");
} elseif ($err = $response->asError()) {
    $logger->info("Error fetching sorted set: {$err->message()}\n");
    exit(1);
}

// fetch sorted set by score (0 to 2) in descending order
$logger->info("Fetching sorted set $SET_NAME by score in descending order\n");
$response = $client->sortedSetFetchByScore($CACHE_NAME, $SET_NAME, minScore: 0, maxScore: 2, order: SORT_DESC);
if ($response->asHit()) {
    $logger->info("SUCCESS: Sorted set $SET_NAME: " . implode(', ', $response->asHit()->valuesArray()) . "\n");
} elseif ($err = $response->asMiss()) {
    $logger->info("Sorted set $SET_NAME not found\n");
} elseif ($err = $response->asError()) {
    $logger->info("Error fetching sorted set: {$err->message()}\n");
    exit(1);
}

// increment score of element in sorted set
$logger->info("Incrementing score of element 'one' in sorted set $SET_NAME by 1\n");
$response = $client->sortedSetIncrementScore($CACHE_NAME, $SET_NAME, "one", 1);
if ($response->asSuccess()) {
    $logger->info("SUCCESS\n");
} elseif ($err = $response->asError()) {
    $logger->info("Error incrementing score of element in set: {$err->message()}\n");
    exit(1);
}

// get score of element in sorted set
$logger->info("Getting score of element 'one' in sorted set $SET_NAME\n");
$response = $client->sortedSetGetScore($CACHE_NAME, $SET_NAME, "one");
if ($response->asHit()) {
    $logger->info("SUCCESS: Score of element 'one' in set $SET_NAME: " . $response->asHit()->score() . "\n");
} elseif ($err = $response->asMiss()) {
    $logger->info("Element 'one' not found in set $SET_NAME\n");
} elseif ($err = $response->asError()) {
    $logger->info("Error getting score of element in set: {$err->message()}\n");
    exit(1);
}

// get length of sorted set by score
$logger->info("Getting length of sorted set $SET_NAME\n");
$response = $client->sortedSetLengthByScore($CACHE_NAME, $SET_NAME);
if ($response->asHit()) {
    $logger->info("SUCCESS: Length of set $SET_NAME: " . $response->asHit()->length() . "\n");
} elseif ($err = $response->asMiss()) {
    $logger->info("Set $SET_NAME not found\n");
} elseif ($err = $response->asError()) {
    $logger->info("Error getting length of set: {$err->message()}\n");
    exit(1);
}


// Remove element from sorted set
$logger->info("Removing element 'one' from $SET_NAME\n");
$response = $client->sortedSetRemoveElement($CACHE_NAME, $SET_NAME, "one");
if ($response->asSuccess()) {
    $logger->info("SUCCESS: Removed element from set\n");
} elseif ($err = $response->asError()) {
    $logger->info("Error removing element from set: {$err->message()}\n");
    exit(1);
}

// Remove multiple elements from sorted set
$logger->info("Removing elements 'two', 'three' from $SET_NAME\n");
$response = $client->sortedSetRemoveElements($CACHE_NAME, $SET_NAME, ["two", "three"]);
if ($response->asSuccess()) {
    $logger->info("SUCCESS: Removed elements from set\n");
} elseif ($err = $response->asError()) {
    $logger->info("Error removing elements from set: {$err->message()}\n");
    exit(1);
}

// Delete test cache
$logger->info("Deleting cache $CACHE_NAME\n");
$response = $client->deleteCache($CACHE_NAME);
if ($response->asError()) {
    $logger->info("Error deleting cache: " . $response->asError()->message() . "\n");
    exit(1);
}

printBanner("*                       Momento Example End                      *", $logger);
