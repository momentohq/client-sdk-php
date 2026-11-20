<?php
declare(strict_types=1);

namespace Momento\Topic\Internal;

use Cache_client\Pubsub\_PublishRequest;
use Cache_client\Pubsub\_TopicValue;
use Exception;
use Grpc\UnaryCall;
use Momento\Auth\ICredentialProvider;
use Momento\Cache\CacheOperationTypes\ResponseFuture;
use Momento\Cache\Errors\InvalidArgumentError;
use Momento\Cache\Errors\SdkError;
use Momento\Cache\Errors\UnknownError;
use Momento\Config\IConfiguration;
use Momento\Topic\TopicOperationTypes\TopicPublishError;
use Momento\Topic\TopicOperationTypes\TopicPublishResponse;
use Momento\Topic\TopicOperationTypes\TopicPublishSuccess;
use Momento\Utilities\_ErrorConverter;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use function Momento\Utilities\validateCacheName;
use function Momento\Utilities\validateNullOrEmpty;
use function Momento\Utilities\validateOperationTimeout;

class ScsTopicClient implements LoggerAwareInterface
{
    private const DEFAULT_DEADLINE_MILLISECONDS = 5000;
    private const TIMEOUT_MULTIPLIER = 1000;

    private TopicGrpcManager $grpcManager;
    private int $timeout;
    private LoggerInterface $logger;

    public function __construct(IConfiguration $configuration, ICredentialProvider $authProvider)
    {
        $operationTimeoutMs = $configuration
            ->getTransportStrategy()
            ->getGrpcConfig()
            ->getDeadlineMilliseconds();
        validateOperationTimeout($operationTimeoutMs);
        $this->timeout = ($operationTimeoutMs ?? self::DEFAULT_DEADLINE_MILLISECONDS) * self::TIMEOUT_MULTIPLIER;
        $this->grpcManager = new TopicGrpcManager($authProvider, $configuration);
        $this->setLogger($configuration->getLoggerFactory()->getLogger(get_class($this)));
    }

    public function close(): void
    {
        $this->grpcManager->close();
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    private function processCall(UnaryCall $call)
    {
        [$response, $status] = $call->wait();
        if ($status->code !== 0) {
            $this->logger->debug("Topic client error: {$status->details}");
            throw _ErrorConverter::convert($status, $call->getMetadata());
        }
        return $response;
    }

    /**
     * @return ResponseFuture<TopicPublishResponse>
     */
    public function publish(string $cacheName, string $topicName, string $value): ResponseFuture
    {
        return $this->sendPublish($cacheName, $topicName, $value, false);
    }

    /**
     * @return ResponseFuture<TopicPublishResponse>
     */
    public function publishBinary(string $cacheName, string $topicName, string $value): ResponseFuture
    {
        return $this->sendPublish($cacheName, $topicName, $value, true);
    }

    /**
     * @return ResponseFuture<TopicPublishResponse>
     */
    private function sendPublish(string $cacheName, string $topicName, string $value, bool $binary): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateNullOrEmpty($topicName, "Topic name");
            $topicValue = new _TopicValue();
            if ($binary) {
                $topicValue->setBinary($value);
            } else {
                if (preg_match("//u", $value) !== 1) {
                    throw new InvalidArgumentError("Topic value must be valid UTF-8 unless it is published as binary");
                }
                $topicValue->setText($value);
            }
            $publishRequest = new _PublishRequest();
            $publishRequest->setCacheName($cacheName);
            $publishRequest->setTopic($topicName);
            $publishRequest->setValue($topicValue);
            $call = $this->grpcManager->client->Publish($publishRequest, [], ["timeout" => $this->timeout]);
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new TopicPublishError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new TopicPublishError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): TopicPublishResponse {
                try {
                    $this->processCall($call);
                } catch (SdkError $e) {
                    return new TopicPublishError($e);
                } catch (Exception $e) {
                    return new TopicPublishError(new UnknownError($e->getMessage(), 0, $e));
                }

                return new TopicPublishSuccess();
            }
        );
    }
}
