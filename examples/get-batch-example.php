<?php

require "vendor/autoload.php";

use Momento\Auth\CredentialProvider;
use Momento\Cache\CacheClient;
use Momento\Config\Configurations\Laptop;
use Momento\Logging\StderrLoggerFactory;
use Psr\Log\LoggerInterface;

$CACHE_NAME = "test-php";
$ITEM_DEFAULT_TTL_SECONDS = 60;

$keys = ["key1", "key2", "key3"];

// Setup
$authProvider = CredentialProvider::fromEnvironmentVariable("MOMENTO_AUTH_TOKEN");
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

// get elements from cache
$logger->info("Getting elements from cache '$CACHE_NAME' with keys: " . implode(", ", $keys));
$response = $client->getBatch($CACHE_NAME, $keys);
if ($response->asSuccess()) {
    $logger->info("SUCCESS");
} elseif ($err = $response->asError()) {
    $logger->info("Error getting elements: {$err->message()}");
    exit(1);
}

// Delete test cache
//$logger->info("Deleting cache $CACHE_NAME\n");
//$response = $client->deleteCache($CACHE_NAME);
//if ($response->asError()) {
//    $logger->info("Error deleting cache: " . $response->asError()->message() . "\n");
//    exit(1);
//}

printBanner("*                       Momento Example End                      *", $logger);
