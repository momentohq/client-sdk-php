<?php

require "vendor/autoload.php";

use Momento\Auth\CredentialProvider;
use Momento\Cache\CacheClient;
use Momento\Config\Configurations\Laptop;
use Momento\Logging\StderrLoggerFactory;
use Momento\Requests\CollectionTtl;
use Psr\Log\LoggerInterface;

$CACHE_NAME = uniqid("php-list-example-");
$SET_NAME = "example-set";
$ITEM_DEFAULT_TTL_SECONDS = 60;
$SET_ELEMENT = "set-element";

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

// add element to set
$logger->info("Adding element '$SET_ELEMENT' to set $SET_NAME");
$response = $client->setAddElement($CACHE_NAME, $SET_NAME, $SET_ELEMENT, $collectionTtl);
if ($response->asSuccess()) {
    $logger->info("SUCCESS");
} elseif ($err = $response->asError()) {
    $logger->info("Error adding element to set: {$err->message()}");
    exit(1);
}

// fetch set
$logger->info("Fetching set $SET_NAME");
$response = $client->setFetch($CACHE_NAME, $SET_NAME);
if ($setHit = $response->asHit()) {
    $logger->info("Successfully fetched the set $SET_NAME:");
    $logger->info(implode(", ", $setHit->valuesArray()));
} elseif ($response->asMiss()) {
    $logger->info("Set fetch operation returned an unexpected MISS");
    exit(1);
} elseif ($err = $response->asError()) {
    $logger->info("Error adding element to set: {$err->message()}");
    exit(1);
}

// remove element from set
$logger->info("Removing element '$SET_ELEMENT' from $SET_NAME");
$response = $client->setRemoveElement($CACHE_NAME, $SET_NAME, $SET_ELEMENT);
if ($response->asSuccess()) {
    $logger->info("SUCCESS");
} elseif ($err = $response->asError()) {
    $logger->info("Error adding element to set: {$err->message()}");
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
