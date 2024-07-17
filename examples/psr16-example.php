<?php
declare(strict_types=1);

require "vendor/autoload.php";

use Momento\Auth\CredentialProvider;
use Momento\Cache\Psr16CacheClient;
use Momento\Config\Configurations\Laptop;
use Momento\Logging\StderrLoggerFactory;
use Psr\Log\LoggerInterface;

$ITEM_DEFAULT_TTL_SECONDS = 60;
$KEY = "MyKey";
$VALUE = "MyValue";

// Setup
$authProvider = CredentialProvider::fromEnvironmentVariable("MOMENTO_API_KEY");
$configuration = Laptop::latest(new StderrLoggerFactory());
$client = new Psr16CacheClient($configuration, $authProvider, $ITEM_DEFAULT_TTL_SECONDS);
$logger = $configuration->getLoggerFactory()->getLogger("ex:");

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
