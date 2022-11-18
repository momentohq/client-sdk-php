<?php

require "vendor/autoload.php";

use Momento\Auth\EnvMomentoTokenProvider;
use Momento\Cache\SimpleCacheClient;
use Momento\Config\Configurations\Laptop;
use Momento\Logging\StderrLoggerFactory;
use Psr\Log\LoggerInterface;

$CACHE_NAME = getenv("CACHE_NAME");
if (!$CACHE_NAME) {
    print "Error: Environment variable CACHE_NAME was not found.\n";
    exit;
}
$LIST_NAME = "example-list";
$PUSH_FRONT_VALUE = "MyPushFrontValue";
$PUSH_BACK_VALUE = "MyPushBackValue";
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

// Push front
$logger->info("Pushing value: $PUSH_FRONT_VALUE to list: $LIST_NAME\n");
$response = $client->listPushFront($CACHE_NAME, $LIST_NAME, $PUSH_FRONT_VALUE, true, 6000);
if ($response->asSuccess()) {
    $logger->info("SUCCESS: Pushed front - value: " . $PUSH_FRONT_VALUE . " list: " . $LIST_NAME . "\n");
} elseif ($response->asError()) {
    $logger->info("Error pushing a value to front: " . $response->asError()->message() . "\n");
    exit;
}

// Push back
$logger->info("Pushing value: $PUSH_BACK_VALUE to list: $LIST_NAME\n");
$response = $client->listPushBack($CACHE_NAME, $LIST_NAME, $PUSH_BACK_VALUE, true, 6000);
if ($response->asSuccess()) {
    $logger->info("SUCCESS: Pushed back - value: " . $PUSH_BACK_VALUE . " list: " . $LIST_NAME . "\n");
} elseif ($response->asError()) {
    $logger->info("Error pushing a value to front: " . $response->asError()->message() . "\n");
    exit;
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
    $logger->info("Fetch operation was a MISS\n");
} elseif ($response->asError()) {
    $logger->info("Error fetching a list: " . $response->asError()->message() . "\n");
    exit;
}

// Pop front
$logger->info("Popping front list: $LIST_NAME\n");
$response = $client->listPopFront($CACHE_NAME, $LIST_NAME);
if ($response->asHit()) {
    $logger->info("HIT: Popped front - value: " . $response->asHit()->valueString() . " list: " . $LIST_NAME . "\n");

} elseif ($response->asMiss()) {
    $logger->info("Pop Front operation was a MISS\n");
} elseif ($response->asError()) {
    $logger->info("Error popping a value from front: " . $response->asError()->message() . "\n");
    exit;
}

// Pop back
$logger->info("Popping back list: $LIST_NAME\n");
$response = $client->listPopBack($CACHE_NAME, $LIST_NAME);
if ($response->asHit()) {
    $logger->info("HIT: Popped back - value: " . $response->asHit()->valueString() . " list: " . $LIST_NAME . "\n");
} elseif ($response->asMiss()) {
    $logger->info("Pop Back operation was a MISS\n");
} elseif ($response->asError()) {
    $logger->info("Error popping a value from back: " . $response->asError()->message() . "\n");
    exit;
}
printBanner("*                       Momento Example End                      *", $logger);
