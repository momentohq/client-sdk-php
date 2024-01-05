<?php
declare(strict_types=1);

require "vendor/autoload.php";

use Momento\Auth\CredentialProvider;
use Momento\Cache\CacheClient;
use Momento\Cache\CacheOperationTypes\TopicSubscribeError;
use Momento\Cache\CacheOperationTypes\TopicSubscribeSubscription;
use Momento\Config\Configurations\Laptop;
use Momento\Logging\StderrLoggerFactory;
use Momento\Topic\PreviewTopicClient;
use Psr\Log\LoggerInterface;

$CACHE_NAME = "php-example-cache";
$TOPIC_NAME = "php-example-topic";
$ITEM_DEFAULT_TTL_SECONDS = 60;

// Setup
$authProvider = CredentialProvider::fromEnvironmentVariable("MOMENTO_AUTH_TOKEN");
$configuration = Laptop::latest(new StderrLoggerFactory());
$client = new CacheClient($configuration, $authProvider, $ITEM_DEFAULT_TTL_SECONDS);
$topicClient = new PreviewTopicClient($configuration, $authProvider);
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
$subscribeResponseFuture = $topicClient->subscribeAsync($CACHE_NAME, $TOPIC_NAME);

// Publish to topic
$logger->info("Publishing to topic: $TOPIC_NAME\n");
for ($i = 0; $i < 5; $i++) { // publish 5 messages
    $publishResponse = $topicClient->publish($CACHE_NAME, $TOPIC_NAME, "message-". $i);
    if ($publishResponse->asSuccess()) {
        $logger->info("SUCCESS: Published message: " . $publishResponse->asSuccess() . "\n");
    } elseif ($publishResponse->asError()) {
        $logger->info("Error publishing message: " . $publishResponse->asError()->message() . "\n");
        exit(1);
    }
}

// Counter for received messages
$receivedMessages = 0;

// Wait for subscription to receive message
$logger->info("Waiting for subscription to receive messages\n");
$subscribeResponse = $subscribeResponseFuture->wait();

if ($subscribeResponse instanceof TopicSubscribeSubscription) {
    foreach ($subscribeResponse->getMessages() as $message) {
        $logger->info("SUCCESS: Received message: " . $message . "\n");
        $receivedMessages++;
        // Exit the loop when the desired number of messages is received otherwise it will hang waiting fpr more messages to be received
        if ($receivedMessages >= 5) {
            $logger->info("All messages received. Exiting the program.\n");
            break;
        }
    }
} elseif ($subscribeResponse instanceof TopicSubscribeError) {
    $logger->info("Error receiving message: " . $subscribeResponse->message() . "\n");
    exit(1);
} else {
    $logger->info("Unexpected response type\n");
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
