<?php
declare(strict_types=1);

namespace Momento\Topic\Internal;

use Cache_client\Pubsub\_PublishRequest;
use Cache_client\Pubsub\_SubscriptionRequest;
use Cache_client\Pubsub\_TopicItem;
use Cache_client\Pubsub\_TopicValue;
use Exception;
use Grpc\ServerStreamingCall;
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
    private TopicGrpcManager $grpcManager;
    private LoggerInterface $logger;
    private int $timeout;
    private string $authToken;

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

    /**
     * Publish to a topic in a cache.
     *
     * @param string   $cacheName The name of the cache.
     * @param string   $topicName The name of the topic to publish to.
     * @param string   $value The message to be published.
     * @return TopicPublishResponse
     */
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
    public function subscribeAsync(string $cacheName, string $topicName): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateTopicName($topicName);
            $authToken = $this->authToken;
            $request = new _SubscriptionRequest();
            $request->setCacheName($cacheName);
            $request->setTopic($topicName);

            $call = $this->grpcManager->client->Subscribe($request, ['authorization' => [$authToken]]);
            $subscription = new TopicSubscribeResponseSubscription($call, $cacheName, $topicName);
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new TopicSubscribeResponseError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new TopicSubscribeResponseError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($subscription, $call, $topicName, $cacheName): TopicSubscribeResponse {
                try {
                    return $subscription;
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
