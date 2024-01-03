<?php
declare(strict_types=1);

namespace Momento\Topic\Internal;

use Cache_client\Pubsub\_PublishRequest;
use Cache_client\Pubsub\_SubscriptionRequest;
use Cache_client\Pubsub\_TopicValue;
use Exception;
use Grpc\ServerStreamingCall;
use Grpc\UnaryCall;
use Momento\Auth\ICredentialProvider;
use Momento\Cache\CacheOperationTypes\ResponseFuture;
use Momento\Cache\CacheOperationTypes\TopicPublishResponse;
use Momento\Cache\CacheOperationTypes\TopicPublishResponseError;
use Momento\Cache\CacheOperationTypes\TopicPublishResponseSuccess;
use Momento\Cache\CacheOperationTypes\TopicSubscribeResponse;
use Momento\Cache\CacheOperationTypes\TopicSubscribeResponseError;
use Momento\Cache\CacheOperationTypes\TopicSubscribeResponseSubscription;
use Momento\Cache\Errors\SdkError;
use Momento\Cache\Errors\UnknownError;
use Momento\Config\IConfiguration;
use Momento\Requests\CollectionTtl;
use Momento\Utilities\_ErrorConverter;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use function Momento\Utilities\validateCacheName;
use function Momento\Utilities\validateOperationTimeout;
use function Momento\Utilities\validateTtl;

class ScsTopicClient implements LoggerAwareInterface
{

    private static int $DEFAULT_DEADLINE_MILLISECONDS = 5000;
    private int $deadline_milliseconds;
    // Used to convert deadline_milliseconds into microseconds for gRPC
    private static int $TIMEOUT_MULTIPLIER = 1000;
    private int $defaultTtlSeconds;
    private TopicGrpcManager $grpcManager;
    private LoggerInterface $logger;
    private int $timeout;
    private $authToken;

    public function __construct(IConfiguration $configuration, ICredentialProvider $authProvider)
    {
        $this->authToken = $authProvider->getAuthToken();
        $operationTimeoutMs = $configuration
            ->getTransportStrategy()
            ->getGrpcConfig()
            ->getDeadlineMilliseconds();
        validateOperationTimeout($operationTimeoutMs);
        $this->deadline_milliseconds = $operationTimeoutMs ?? self::$DEFAULT_DEADLINE_MILLISECONDS;
        $this->timeout = $this->deadline_milliseconds * self::$TIMEOUT_MULTIPLIER;
        $this->grpcManager = new TopicGrpcManager($authProvider, $configuration);
        $this->setLogger($configuration->getLoggerFactory()->getLogger(get_class($this)));
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    private function ttlToMillis(?int $ttl = null): int
    {
        if (!$ttl) {
            $ttl = $this->defaultTtlSeconds;
        }
        validateTtl($ttl);
        return $ttl * 1000;
    }

    private function returnCollectionTtl(?CollectionTtl $ttl): CollectionTtl
    {
        if (!$ttl) {
            return CollectionTtl::fromCacheTtl();
        }
        return $ttl;
    }

    private function processCall(UnaryCall $call)
    {
        [$response, $status] = $call->wait();
        if ($status->code !== 0) {
            $this->logger->debug("Topic client error: {$status->details}");
            throw _ErrorConverter::convert($status->code, $status->details, $call->getMetadata());
        }
        return $response;
    }

    private function processStreamingCall(ServerStreamingCall $call): void
    {
        foreach ($call->responses() as $response) {
            // Process each response individually
            $this->logger->info("Received message: " . json_encode($response));

            // Optionally, you can log any metadata received during the initial setup.
            $metadata = $call->getMetadata();
            $this->logger->info("Initial metadata received: " . json_encode($metadata));

            // Optionally, log the start of the streaming process.
            $this->logger->info("Streaming call initiated successfully.");
        }

        // Handle the end of the streaming process
        $status = $call->getStatus();
        if ($status->code !== 0) {
            $this->logger->error("Error during streaming: {$status->details}");
            throw _ErrorConverter::convert($status->code, $status->details, $call->getMetadata());
        }
    }


    public function publish(string $cacheName, string $topicName, string $value): TopicPublishResponse
    {
        $this->logger->info("Publishing to topic: $topicName in cache $cacheName\n");
        $topicValue = new _TopicValue();
        $topicValue->setText($value);
        try {
            validateCacheName($cacheName);
            $request = new _PublishRequest();
            $request->setCacheName($cacheName);
            $request->setTopic($topicName);
            $request->setValue($topicValue);
            $call = $this->grpcManager->client->Publish($request);
            $this->processCall($call);
        } catch (SdkError $e) {
            $this->logger->debug("Failed to publish message to topic $topicName in cache $cacheName: {$e->getMessage()}");
            return new TopicPublishResponseError($e);
        } catch (\Exception $e) {
            $this->logger->debug("Failed to publish message to topic $topicName in cache $cacheName: {$e->getMessage()}");
            return new TopicPublishResponseError(new UnknownError($e->getMessage()));
        }
        return new TopicPublishResponseSuccess();
    }


    /**
     * Subscribe to a topic in a cache.
     *
     * @param string   $cacheName The name of the cache.
     * @param string   $topicName The name of the topic to subscribe to.
     * @param callable $onMessage Callback for handling incoming messages.
     * @return TopicSubscribeResponse
     */
    public function subscribe(string $cacheName, string $topicName, callable $onMessage): TopicSubscribeResponse
    {
        $this->logger->info("Subscribing to topic: $topicName in cache $cacheName\n");

        try {
            validateCacheName($cacheName);

            $authToken = $this->authToken;
            $this->logger->info("Using auth token: $authToken\n");

            $request = new _SubscriptionRequest();
            $request->setCacheName($cacheName);
            $request->setTopic($topicName);

            $call = $this->grpcManager->client->Subscribe($request, [], [
                'timeout' => $this->timeout,
                'headers' => [
                    'authorization' => $authToken,
                ],
            ]);
            $this->processStreamingCall($call);

            foreach ($call->responses() as $response) {
                try {
                    $this->logger->info("Received message from topic $topicName in cache $cacheName\n");
                    $onMessage($response);
                } catch (\Exception $e) {
                    $this->logger->error("Error processing message: " . $e->getMessage());
                }
            }


        } catch (SdkError $e) {
            $this->logger->debug("Failed to subscribe to topic $topicName in cache $cacheName: {$e->getMessage()}");
            return new TopicSubscribeResponseError($e);
        } catch (\Exception $e) {
            $this->logger->debug("Failed to subscribe to topic $topicName in cache $cacheName: {$e->getMessage()}");
            return new TopicSubscribeResponseError(new UnknownError($e->getMessage()));
        }

        return new TopicSubscribeResponseSubscription();
    }

    public function close(): void
    {
        $this->grpcManager->close();
    }
}
