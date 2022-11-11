<?php
declare(strict_types=1);

require "vendor/autoload.php";

use Momento\Auth\EnvMomentoTokenProvider;
use Momento\Cache\SimpleCacheClient;
use Momento\Config\Configurations\Laptop;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

$CACHE_NAME = getenv("CACHE_NAME");
$DICTIONARY_NAME = "example-dictionary";
$FIELD = "MyField";
$VALUE = "MyValue";
$ITEM_DEFAULT_TTL_SECONDS = 60;

// Setup
$authProvider = new EnvMomentoTokenProvider("MOMENTO_AUTH_TOKEN");

// Use your favorite PSR-3 logger.
$logger = new Logger("example");
$streamHandler = new StreamHandler("php://stderr");
$formatter = new LineFormatter("%message%\n");
$streamHandler->setFormatter($formatter);
$logger->pushHandler($streamHandler);

// Or use the build-in minimal logger, equivalent to the above Monolog configuration.
//$logger = \Momento\Utilities\LoggingHelper::getMinimalLogger();
// Or discard all log messages.
//$logger = \Momento\Utilities\LoggingHelper::getNullLogger();

$configuration = Laptop::latest($logger);
$client = new SimpleCacheClient($configuration, $authProvider, $ITEM_DEFAULT_TTL_SECONDS);

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
    print "Created cache " . $CACHE_NAME . "\n";
} elseif ($response->asError()) {
    print "Error creating cache: " . $response->asError()->message() . "\n";
    exit;
} elseif ($response->asAlreadyExists()) {
    print "Cache " . $CACHE_NAME . " already exists.\n";
}

// Dictionary Set
print "Setting field: $FIELD and value: $VALUE in dictionary: $DICTIONARY_NAME\n";
$response = $client->dictionarySet($CACHE_NAME, $DICTIONARY_NAME, $FIELD, $VALUE, false, $ITEM_DEFAULT_TTL_SECONDS);
if ($response->asSuccess()) {
    print "SUCCESS: Dictionary set - field: " . $FIELD . " value: " . $VALUE . " dictionary: " . $DICTIONARY_NAME . "\n";
} elseif ($response->asError()) {
    print "Error setting a value in a dictionary: " . $response->asError()->message() . "\n";
    exit;
}

// Dictionary Get
print "Getting field: $FIELD in dictionary: $DICTIONARY_NAME\n";
$response = $client->dictionaryGet($CACHE_NAME, $DICTIONARY_NAME, $FIELD);
if ($response->asHit()) {
    print "HIT: Dictionary get - field: " . $FIELD . " value: " . $response->asHit()->value() . " dictionary: " . $DICTIONARY_NAME . "\n";
} elseif ($response->asMiss()) {
    print "Get operation was a MISS\n";
} elseif ($response->asError()) {
    print "Error getting a value in a dictionary: " . $response->asError()->message() . "\n";
    exit;
}
printBanner("*                       Momento Example End                      *", $logger);
