<?php
declare(strict_types=1);

namespace Momento\Topic\Internal;

use Cache_client\Pubsub\_PublishRequest;
use Cache_client\Pubsub\_SubscriptionRequest;
use Cache_client\Pubsub\_TopicValue;
use Exception;
use Grpc\UnaryCall;
use Momento\Auth\ICredentialProvider;
use Momento\Cache\CacheOperationTypes\ResponseFuture;
use Momento\Cache\CacheOperationTypes\TopicPublishResponse;
use Momento\Cache\CacheOperationTypes\TopicPublishResponseError;
use Momento\Cache\CacheOperationTypes\TopicPublishResponseSuccess;
use Momento\Cache\CacheOperationTypes\TopicSubscribeResponse;
use Momento\Cache\CacheOperationTypes\TopicSubscribeResponseError;
use Momento\Cache\CacheOperationTypes\TopicSubscribeResponseSubscription;
use Momento\Cache\Errors\InvalidArgumentError;
use Momento\Cache\Errors\SdkError;
use Momento\Cache\Errors\UnknownError;
use Momento\Config\IConfiguration;
use Momento\Utilities\_ErrorConverter;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use function Momento\Utilities\validateCacheName;
use function Momento\Utilities\validateOperationTimeout;
use function Momento\Utilities\validateTopicName;

class ScsTopicClient implements LoggerAwareInterface
{

    private static int $DEFAULT_DEADLINE_MILLISECONDS = 5000;
    private int $deadline_milliseconds;
    private static int $TIMEOUT_MULTIPLIER = 1000;
    private int $defaultTtlSeconds;
    private TopicGrpcManager $grpcManager;
    private LoggerInterface $logger;
    private int $timeout;
    private string $authToken;
    private bool $firstMessageReceived = false;

    /**
     * @throws InvalidArgumentError
     */
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

    /**
     * @throws SdkError
     */
    private function processCall(UnaryCall $call): void
    {
        [$response, $status] = $call->wait();
        if ($status->code !== 0) {
            $this->logger->debug("Topic client error: {$status->details}");
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
     * @return ResponseFuture<TopicSubscribeResponse>
     */
    public function subscribe(string $cacheName, string $topicName): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateTopicName($topicName);
            $authToken = $this->authToken;
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
            function () use ($call, $topicName, $cacheName): TopicSubscribeResponse {
                try {
                    $this->logger->info("Streaming call initiated successfully for topic $topicName in cache $cacheName.\n");
                    $this->logger->info("Waiting for messages...\n");

                    foreach ($call->responses() as $response) {
                        try {
                            switch ($response->getKind()) {
                                case "heartbeat":
                                    if (!$this->firstMessageReceived) {
                                        $this->logger->info("Received heartbeat from topic $topicName in cache $cacheName\n");
                                        $this->firstMessageReceived = true;
                                        break;
                                    }
                                    break;
                                case "item":
                                    $this->logger->info("Received message content: " . $response->getItem()->getValue()->getText());
                                    break;
                                case "discontinuity":
                                    $this->logger->info("Received message content: " . $response->getDiscontinuity()->getReason());
                                    break;
                                default:
                                    $this->logger->info("Received message content: " . $response->getKind());
                            }
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

    public function close(): void
    {
        $this->grpcManager->close();
    }
}
