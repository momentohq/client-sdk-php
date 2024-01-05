<?php
declare(strict_types=1);

namespace Momento\Topic\Internal;

use Cache_client\Pubsub\_PublishRequest;
use Cache_client\Pubsub\_SubscriptionRequest;
use Cache_client\Pubsub\_TopicItem;
use Cache_client\Pubsub\_TopicValue;
use Exception;
use Grpc\UnaryCall;
use Momento\Auth\ICredentialProvider;
use Momento\Cache\CacheOperationTypes\ResponseFuture;
use Momento\Cache\CacheOperationTypes\TopicPublishError;
use Momento\Cache\CacheOperationTypes\TopicPublishSuccess;
use Momento\Cache\CacheOperationTypes\TopicPublishResponse;
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
            return new TopicPublishError($e);
        } catch (\Exception $e) {
            $this->logger->debug("Failed to publish message to topic $topicName in cache $cacheName: {$e->getMessage()}");
            return new TopicPublishError(new UnknownError($e->getMessage()));
        }
        return new TopicPublishSuccess();
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
        $this->logger->info("Inside scs topic client subscribe method\n");
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
                                    $this->handleSubscriptionItem($response->getItem());
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

    /**
     * Handle the subscription item based on its type.
     *
     * @param _TopicItem $item The received item from the subscription.
     */
    private function handleSubscriptionItem(_TopicItem $item): void
    {
        try {
            $itemType = $item->getValue()->getKind();
            $this->logger->info("Received item type: $itemType");

            switch ($itemType) {
                case "text":
                    $this->handleTextItem($item->getValue()->getText());
                    break;
                case "binary":
                    $this->handleBinaryItem($item->getValue()->getBinary());
                    break;
                default:
                    $this->logger->info("Received unknown item type: $itemType");
            }
        } catch (\Exception $e) {
            $this->logger->error("Error handling subscription item: " . $e->getMessage());
        }
    }

    /**
     * Handle a text item received during subscription.
     *
     * @param string $textContent The received text content.
     */
    private function handleTextItem(string $textContent): void
    {
        $this->logger->info("Received message content: $textContent");
    }

    private function handleBinaryItem(string $binaryContent): void
    {
        $this->logger->info("Received message content: $binaryContent");
    }


    public function close(): void
    {
        $this->grpcManager->close();
    }
}
