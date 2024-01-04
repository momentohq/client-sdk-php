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

// Subscribe to topic
$logger->info("Subscribing to topic: $TOPIC_NAME\n");
$onMessage = function ($message) use ($logger) {
    $logger->info("Received message: " . json_encode($message));
//    file_put_contents('message_received.flag', '1');
};
$response = $topicClient->subscribe($CACHE_NAME, $TOPIC_NAME, $onMessage);
if ($response->asSuccess()) {
    $logger->info("SUCCESS: Subscribed to topic: " . $TOPIC_NAME . "\n");
} elseif ($response->asError()) {
    $logger->info("Error subscribing to topic: " . $response->asError()->message() . "\n");
    exit(1);
}

// Publish to topic
//$logger->info("Publishing to topic: $TOPIC_NAME\n");
//$response = $topicClient->publish($CACHE_NAME, $TOPIC_NAME, "MyMessage");
//if ($response->asSuccess()) {
//    $logger->info("SUCCESS: Published to topic: " . $TOPIC_NAME . "\n");
//} elseif ($response->asError()) {
//    $logger->info("Error publishing to topic: " . $response->asError()->message() . "\n");
//    exit(1);
//}

// Wait for a message to be received and logged
//$timeout = time() + 60;
//while (!file_exists('message_received.flag') && time() < $timeout) {
//    sleep(1); // Sleep for 1 second before checking again
//}


// Delete test cache
//$logger->info("Deleting cache $CACHE_NAME\n");
//$response = $client->deleteCache($CACHE_NAME);
//if ($response->asError()) {
//    $logger->info("Error deleting cache: " . $response->asError()->message() . "\n");
//    exit(1);
//}

printBanner("*                       Momento Example End                      *", $logger);
