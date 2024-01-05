<?php
declare(strict_types=1);

require "vendor/autoload.php";

use Momento\Auth\CredentialProvider;
use Momento\Cache\CacheClient;
use Momento\Config\Configurations\Laptop;
use Momento\Logging\StderrLoggerFactory;
use Momento\Topic\TopicClient;
use Psr\Log\LoggerInterface;

$CACHE_NAME = "php-example-cache";
$TOPIC_NAME = "php-example-topic";
$ITEM_DEFAULT_TTL_SECONDS = 60;

// Setup
$authProvider = CredentialProvider::fromEnvironmentVariable("MOMENTO_AUTH_TOKEN");
$configuration = Laptop::latest(new StderrLoggerFactory());
$client = new CacheClient($configuration, $authProvider, $ITEM_DEFAULT_TTL_SECONDS);
$topicClient = new TopicClient($configuration, $authProvider);
$logger = $configuration->getLoggerFactory()->getLogger("ex:");
$childLogger = $configuration->getLoggerFactory()->getLogger("ex_child:");

function printBanner(string $message, LoggerInterface $logger): void
{
    $line = "******************************************************************";
    $logger->info($line);
    $logger->info($message);
    $logger->info($line);
}

function subscribeAndLog(LoggerInterface $childLogger, TopicClient $topicClient, string $cacheName, string $topicName): void
{
    try {
        $childLogger->info("Subscription thread started\n");

        // Loop to keep the thread alive and process incoming messages
        while (true) {
            $childLogger->info("Inside while loop\n");
            $responseFuture = $topicClient->subscribe($cacheName, $topicName);
            $response = $responseFuture->wait();

            if ($response->asSuccess()) {
                $childLogger->info("Received message: " . $response->asSuccess()->message() . "\n");
            } elseif ($response->asError()) {
                $childLogger->error("Error receiving message: " . $response->asError()->message() . "\n");
            }
        }
    } catch (\Throwable $e) {
        $childLogger->error("Exception in subscribeAndLog: " . $e->getMessage() . "\n");
    }
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

// Subscribe to topic in a separate process
$pid = pcntl_fork();

if ($pid === -1) {
    die('Error forking process');
} elseif ($pid) {
    // Parent process
    // Continue execution in the main thread
    $logger->info("Back in main thread " . date("h:i:s") . "\n");
    sleep(5); // Sleep for 5 seconds to allow subscription thread to start
} else {
    // Child process
    // Run the subscription logic in a separate thread
    $logger->info("Subscribing to topic: $TOPIC_NAME\n");
    subscribeAndLog($childLogger, $topicClient, $CACHE_NAME, $TOPIC_NAME);
    // Exit the child process after handling the subscription
//    exit();
}

// Parent process continues here

// Publish to topic
$logger->info("Publishing to topic: $TOPIC_NAME\n");
$response = $topicClient->publish($CACHE_NAME, $TOPIC_NAME, "Hello World " . date("h:i:s"));
if ($response->asSuccess()) {
    $logger->info("SUCCESS: Published to topic: " . $TOPIC_NAME . "\n");
} elseif ($response->asError()) {
    $logger->info("Error publishing to topic: " . $response->asError()->message() . "\n");
    exit(1);
}

sleep(10); // Sleep for 10 seconds to allow subscription thread to receive message

printBanner("*                       Momento Example End                      *", $logger);
