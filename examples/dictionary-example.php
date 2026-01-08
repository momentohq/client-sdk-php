<?php

declare(strict_types=1);

require "vendor/autoload.php";

use Momento\Auth\CredentialProvider;
use Momento\Cache\CacheClient;
use Momento\Cache\Errors\InvalidArgumentError;
use Momento\Config\Configurations\Laptop;
use Momento\Logging\StderrLoggerFactory;
use Momento\Requests\CollectionTtl;
use Psr\Log\LoggerInterface;
use function Momento\Utilities\isNullOrEmpty;

$CACHE_NAME = uniqid("php-dictionary-example-");
$DICTIONARY_NAME = "example-dictionary";
$FIELD = "MyField";
$VALUE = "MyValue";
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

// Dictionary Set
$logger->info("Setting field: $FIELD and value: $VALUE in dictionary: $DICTIONARY_NAME\n");
$response = $client->dictionarySetField($CACHE_NAME, $DICTIONARY_NAME, $FIELD, $VALUE, $collectionTtl);
if ($response->asSuccess()) {
    $logger->info("SUCCESS: Dictionary set - field: " . $FIELD . " value: " . $VALUE . " dictionary: " . $DICTIONARY_NAME . "\n");
} elseif ($response->asError()) {
    $logger->info("Error setting a value in a dictionary: " . $response->asError()->message() . "\n");
    exit(1);
}

// Dictionary Get
$logger->info("Getting field: $FIELD in dictionary: $DICTIONARY_NAME\n");
$response = $client->dictionaryGetField($CACHE_NAME, $DICTIONARY_NAME, $FIELD);
if ($response->asHit()) {
    $logger->info("HIT: Dictionary get - field: " . $FIELD . " value: " . $response->asHit()->valueString() . " dictionary: " . $DICTIONARY_NAME . "\n");
} elseif ($response->asMiss()) {
    $logger->info("Get operation was an unexpected MISS\n");
    exit(1);
} elseif ($response->asError()) {
    $logger->info("Error getting a value in a dictionary: " . $response->asError()->message() . "\n");
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
