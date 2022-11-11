<?php
declare(strict_types=1);

require "vendor/autoload.php";

use Momento\Auth\EnvMomentoTokenProvider;
use Momento\Cache\Psr16CacheClient;
use Momento\Config\Configurations\Laptop;
use Momento\Utilities\LoggingHelper;
use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

$CACHE_NAME = getenv("CACHE_NAME");
if (!$CACHE_NAME) {
    print "Error: Environment variable CACHE_NAME was not found.\n";
    exit;
}
$ITEM_DEFAULT_TTL_SECONDS = 60;
$KEY = "MyKey";
$VALUE = "MyValue";

// Setup
$authProvider = new EnvMomentoTokenProvider("MOMENTO_AUTH_TOKEN");

// Use your favorite PSR-3 logger.
$logger = new Logger("example");
$streamHandler = new StreamHandler("php://stderr");
$formatter = new LineFormatter("%message%\n");
$streamHandler->setFormatter($formatter);
$logger->pushHandler($streamHandler);

// Or use the built-in minimal logger, equivalent to the above Monolog configuration.
//$logger = \Momento\Utilities\LoggingHelper::getMinimalLogger();
// Or discard all log messages.
//$logger = \Momento\Utilities\LoggingHelper::getNullLogger();

$configuration = Laptop::latest($logger);
$client = new Psr16CacheClient($configuration, $authProvider, $ITEM_DEFAULT_TTL_SECONDS);

function printBanner(string $message, LoggerInterface $logger): void
{
    $line = "******************************************************************";
    $logger->info($line);
    $logger->info($message);
    $logger->info($line);
}

printBanner("*                 Momento PSR-16 Example Start                   *", $logger);

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
