<?php
declare(strict_types=1);

namespace Momento\Topic;

use Momento\Auth\ICredentialProvider;
use Momento\Cache\CacheOperationTypes\ResponseFuture;
use Momento\Cache\CacheOperationTypes\TopicPublishResponse;
use Momento\Cache\CacheOperationTypes\TopicSubscribeResponse;
use Momento\Cache\Internal\ScsControlClient;
use Momento\Cache\Internal\ScsDataClient;
use Momento\Config\IConfiguration;
use Momento\Logging\ILoggerFactory;
use Momento\Topic\Internal\ScsTopicClient;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Client to perform operations against Momento Serverless Cache.
 */
class TopicClient implements LoggerAwareInterface
{

    protected IConfiguration $configuration;
    protected ILoggerFactory $loggerFactory;
    protected LoggerInterface $logger;
    private ScsTopicClient $topicClient;

    public function __construct(
        IConfiguration $configuration, ICredentialProvider $authProvider
    )
    {
        $this->configuration = $configuration;
        $this->loggerFactory = $configuration->getLoggerFactory();
        $this->setLogger($this->loggerFactory->getLogger(get_class($this)));
        $this->topicClient = new ScsTopicClient($this->configuration, $authProvider);
    }

    /**
     * Close the client and free up all associated resources. NOTE: the client object will not be usable after calling
     * this method.
     */
    public function close(): void
    {
        $this->topicClient->close();
    }


    /**
     * Assigns a LoggerInterface logging object to the client.
     *
     * @param LoggerInterface $logger Object to use for logging
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function publish(string $cacheName, string $topicName, string $message): TopicPublishResponse
    {
        $this->logger->info("Publishing to topic: $topicName\n");
        return $this->topicClient->publish($cacheName, $topicName, $message);
    }

    public function subscribe(string $cacheName, string $topicName): TopicSubscribeResponse
    {
        $this->logger->info("Subscribing to topic: $topicName\n");
        $callback = function ($message) {
            echo "Received message: " . json_encode($message) . "\n";
        };
        return $this->topicClient->subscribe($cacheName, $topicName, $callback);
    }
}
