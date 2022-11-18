<?php
declare(strict_types=1);

require "vendor/autoload.php";

use Momento\Auth\EnvMomentoTokenProvider;
use Momento\Cache\SimpleCacheClient;
use Momento\Config\Configurations\Laptop;
use Momento\Logging\StderrLoggerFactory;
use Psr\Log\LoggerInterface;

$CACHE_NAME = getenv("CACHE_NAME");
$DICTIONARY_NAME = "example-dictionary";
$FIELD = "MyField";
$VALUE = "MyValue";
$ITEM_DEFAULT_TTL_SECONDS = 60;

// Setup
$authProvider = new EnvMomentoTokenProvider("MOMENTO_AUTH_TOKEN");

$configuration = Laptop::latest()->withLoggerFactory(new StderrLoggerFactory());
$client = new SimpleCacheClient($configuration, $authProvider, $ITEM_DEFAULT_TTL_SECONDS);
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
    exit;
} elseif ($response->asAlreadyExists()) {
    $logger->info("Cache " . $CACHE_NAME . " already exists.\n");
}

// Dictionary Set
$logger->info("Setting field: $FIELD and value: $VALUE in dictionary: $DICTIONARY_NAME\n");
$response = $client->dictionarySetField($CACHE_NAME, $DICTIONARY_NAME, $FIELD, $VALUE, false, $ITEM_DEFAULT_TTL_SECONDS);
if ($response->asSuccess()) {
    $logger->info("SUCCESS: Dictionary set - field: " . $FIELD . " value: " . $VALUE . " dictionary: " . $DICTIONARY_NAME . "\n");
} elseif ($response->asError()) {
    $logger->info("Error setting a value in a dictionary: " . $response->asError()->message() . "\n");
    exit;
}

// Dictionary Get
$logger->info("Getting field: $FIELD in dictionary: $DICTIONARY_NAME\n");
$response = $client->dictionaryGetField($CACHE_NAME, $DICTIONARY_NAME, $FIELD);
if ($response->asHit()) {
    $logger->info("HIT: Dictionary get - field: " . $FIELD . " value: " . $response->asHit()->valueString() . " dictionary: " . $DICTIONARY_NAME . "\n");
} elseif ($response->asMiss()) {
    $logger->info("Get operation was a MISS\n");
} elseif ($response->asError()) {
    $logger->info("Error getting a value in a dictionary: " . $response->asError()->message() . "\n");
    exit;
}
printBanner("*                       Momento Example End                      *", $logger);
