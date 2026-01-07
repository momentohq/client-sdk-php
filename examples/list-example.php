<?php

require "vendor/autoload.php";

use Momento\Auth\CredentialProvider;
use Momento\Cache\CacheClient;
use Momento\Config\Configurations\Laptop;
use Momento\Logging\StderrLoggerFactory;
use Momento\Requests\CollectionTtl;
use Psr\Log\LoggerInterface;

$CACHE_NAME = uniqid("php-list-example-");
$LIST_NAME = "example-list";
$PUSH_FRONT_VALUE = "MyPushFrontValue";
$PUSH_BACK_VALUE = "MyPushBackValue";
$ITEM_DEFAULT_TTL_SECONDS = 60;

// Setup
$authProvider = CredentialProvider::fromEnvironmentVariablesV2();
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

// Push front
$logger->info("Pushing value: $PUSH_FRONT_VALUE to list: $LIST_NAME\n");
$response = $client->listPushFront($CACHE_NAME, $LIST_NAME, $PUSH_FRONT_VALUE, null, $collectionTtl);
if ($response->asSuccess()) {
    $logger->info("SUCCESS: Pushed front - value: " . $PUSH_FRONT_VALUE . " list: " . $LIST_NAME . "\n");
} elseif ($response->asError()) {
    $logger->info("Error pushing a value to front: " . $response->asError()->message() . "\n");
    exit(1);
}

// Push back
$logger->info("Pushing value: $PUSH_BACK_VALUE to list: $LIST_NAME\n");
$response = $client->listPushBack($CACHE_NAME, $LIST_NAME, $PUSH_BACK_VALUE, null, $collectionTtl);
if ($response->asSuccess()) {
    $logger->info("SUCCESS: Pushed back - value: " . $PUSH_BACK_VALUE . " list: " . $LIST_NAME . "\n");
} elseif ($response->asError()) {
    $logger->info("Error pushing a value to front: " . $response->asError()->message() . "\n");
    exit(1);
}

// List fetch
$logger->info("Fetching list: $LIST_NAME\n");
$response = $client->listFetch($CACHE_NAME, $LIST_NAME);
if ($response->asHit()) {
    $logger->info("HIT: Fetched values from list (" . $LIST_NAME . "): ");
    foreach ($response->asHit()->valuesArray() as $value) {
        $logger->info($value . "\n");
    }
    $logger->info("\n");
} elseif ($response->asMiss()) {
    $logger->info("Fetch operation was an unexpected MISS\n");
    exit(1);
} elseif ($response->asError()) {
    $logger->info("Error fetching a list: " . $response->asError()->message() . "\n");
    exit(1);
}

// Pop front
$logger->info("Popping front list: $LIST_NAME\n");
$response = $client->listPopFront($CACHE_NAME, $LIST_NAME);
if ($response->asHit()) {
    $logger->info("HIT: Popped front - value: " . $response->asHit()->valueString() . " list: " . $LIST_NAME . "\n");
} elseif ($response->asMiss()) {
    $logger->info("Pop Front operation was an unexpected MISS\n");
    exit(1);
} elseif ($response->asError()) {
    $logger->info("Error popping a value from front: " . $response->asError()->message() . "\n");
    exit(1);
}

// Pop back
$logger->info("Popping back list: $LIST_NAME\n");
$response = $client->listPopBack($CACHE_NAME, $LIST_NAME);
if ($response->asHit()) {
    $logger->info("HIT: Popped back - value: " . $response->asHit()->valueString() . " list: " . $LIST_NAME . "\n");
} elseif ($response->asMiss()) {
    $logger->info("Pop Back operation was an unexpected MISS\n");
    exit(1);
} elseif ($response->asError()) {
    $logger->info("Error popping a value from back: " . $response->asError()->message() . "\n");
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
