<?php
declare(strict_types=1);

namespace Momento\Topic\Internal;

use Cache_client\Pubsub\_PublishRequest;
use Cache_client\Pubsub\_SubscriptionRequest;
use Cache_client\Pubsub\_TopicValue;
use Exception;
use Grpc\ServerStreamingCall;
use Grpc\UnaryCall;
use http\Env\Response;
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
        $this->logger->info("Processing streaming call\n");
        foreach ($call->responses() as $response) {
            $this->logger->info("Received message: " . json_encode($response));
//            $metadata = $call->getMetadata();
//            $this->logger->info("Initial metadata received: " . json_encode($metadata));
            $this->logger->info("Streaming call initiated successfully.");
        }

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
     * @return ResponseFuture<TopicSubscribeResponse>
     */
    public function subscribe(string $cacheName, string $topicName, callable $onMessage): ResponseFuture
    {
        try {
            validateCacheName($cacheName);

            $authToken = $this->authToken;
            $this->logger->info("Using auth token: $authToken\n");

            $request = new _SubscriptionRequest();
            $request->setCacheName($cacheName);
            $request->setTopic($topicName);

            $call = $this->grpcManager->client->Subscribe($request, ['authorization' => [$authToken]]);
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new TopicSubscribeResponseError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new TopicSubscribeResponseError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call, $onMessage, $topicName, $cacheName): TopicSubscribeResponse {
                try {
                    $this->logger->info("Streaming call initiated successfully for topic $topicName in cache $cacheName.\n");
                    $this->logger->info("Waiting for messages...\n");

                    foreach ($call->responses() as $response) {
                        try {
                            $messageItem = $response->getItem();
                            $this->logger->info("Received message item: " . json_encode($messageItem));
                            $messageValue = $messageItem->getValue();
                            $this->logger->info("Received message value: " . json_encode($messageValue));
                            $messageText = $messageValue->getText();
                            $this->logger->info("Received message text: " . json_encode($messageText));

                            $this->logger->info("Received message from topic $topicName in cache $cacheName\n");
                            $this->logger->info("Received message content: " . $messageText);
                            $onMessage($response);
                        } catch (\Exception $e) {
                            $this->logger->error("Error processing message: " . $e->getMessage());
                        }
                    }

                    return new TopicSubscribeResponseSubscription();
                } catch (SdkError $e) {
                    return new TopicSubscribeResponseError($e);
                } catch (Exception $e) {
                    return new TopicSubscribeResponseError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }







//    /**
//     * Subscribe to a topic in a cache.
//     *
//     * @param string   $cacheName The name of the cache.
//     * @param string   $topicName The name of the topic to subscribe to.
//     * @param callable $onMessage Callback for handling incoming messages.
//     * @return TopicSubscribeResponse
//     */
//    public function subscribe(string $cacheName, string $topicName, callable $onMessage): TopicSubscribeResponse
//    {
//        $this->logger->info("Subscribing to topic: $topicName in cache $cacheName\n");
//
//        try {
//            validateCacheName($cacheName);
//
//            $authToken = $this->authToken;
//            $metadata = [
//                'authorization' => [$authToken],
//            ];
//
//            $request = new _SubscriptionRequest();
//            $request->setCacheName($cacheName);
//            $request->setTopic($topicName);
//
//            try {
//                $call = $this->grpcManager->client->Subscribe($request, $metadata);
//            } catch (Exception $e) {
//                $this->logger->error("Error during gRPC Subscribe: " . $e->getMessage());
//                throw $e;
//            }
//
////            $this->processStreamingCall($call);
//            $this->logger->info("Streaming call initiated successfully.");
//            $this->logger->info("Waiting for messages...\n");
//
//            $this->logger->info("Before foreach");
//            try{
//                foreach ($call->responses() as $response) {
//                    $this->logger->info("inside for loop");
//                    $this->logger->info("Received message content: " . json_encode($response));
//                    try {
//                        $this->logger->info("Before calling onMessage");
//                        $onMessage($response);
//                        $this->logger->info("After calling onMessage");
//                    } catch (\Exception $e) {
//                        $this->logger->error("Error processing message: " . $e->getMessage());
//                    }
//                }
//            } catch (\Exception $e){
//                $this->logger->error("Exception in response stream: " . $e->getMessage());
//            }
//
//
//        } catch (SdkError $e) {
//            $this->logger->debug("Failed to subscribe to topic $topicName in cache $cacheName: {$e->getMessage()}");
//            return new TopicSubscribeResponseError($e);
//        } catch (\Exception $e) {
//            $this->logger->debug("Failed to subscribe to topic $topicName in cache $cacheName: {$e->getMessage()}");
//            return new TopicSubscribeResponseError(new UnknownError($e->getMessage()));
//        }
//
//        return new TopicSubscribeResponseSubscription();
//    }

    public function close(): void
    {
        $this->grpcManager->close();
    }
}
