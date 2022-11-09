<?php
declare(strict_types=1);

require "vendor/autoload.php";

use Momento\Auth\EnvMomentoTokenProvider;
use Momento\Cache\Psr16CacheClient;
use Momento\Utilities\LoggingHelper;
use Monolog\Logger;

$CACHE_NAME = getenv("CACHE_NAME");
if (!$CACHE_NAME) {
    print "Error: Environment variable CACHE_NAME was not found.\n";
    exit;
}
$ITEM_DEFAULT_TTL_SECONDS = 60;
$KEY = "MyKey";
$VALUE = "MyValue";
$logger = LoggingHelper::getMinimalLogger("example.php");

function printBanner(string $message, Logger $logger): void
{
    $line = "******************************************************************";
    $logger->info($line);
    $logger->info($message);
    $logger->info($line);
}

printBanner("*                   Momento PSR-16 Example Start                 *", $logger);
// Setup
$authProvider = new EnvMomentoTokenProvider("MOMENTO_AUTH_TOKEN");
$client = new Psr16CacheClient($authProvider, $ITEM_DEFAULT_TTL_SECONDS, throwExceptions: false);

// Set
$response = $client->set($KEY, $VALUE);
if (!$response) {
    if ($error = $client->getLastError()) {
        $logger->info("Set failed with error: {$error->getMessage()}\n");
        exit;
    }
    $logger->info("Set failed without error\n");
}
$logger->info("Successfully set $KEY");

// Get
$response = $client->get($KEY);
if ($error = $client->getLastError()) {
    $logger->info("Get failed with error: {$error->getMessage()}\n");
    exit;
}
$logger->info("Successfully fetched key $KEY = $response\n");

printBanner("*                   Momento PSR-16 Example End                   *", $logger);
