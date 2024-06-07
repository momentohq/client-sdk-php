<?php
declare(strict_types=1);

namespace Momento\Storage\Internal;

use Exception;
use Grpc\UnaryCall;
use Momento\Auth\ICredentialProvider;
use Momento\Cache\CacheOperationTypes\ResponseFuture;
use Momento\Cache\Errors\SdkError;
use Momento\Cache\Errors\UnknownError;
use Momento\Config\IStorageConfiguration;
use Momento\Storage\StorageOperationTypes\StorageDeleteResponse;
use Momento\Storage\StorageOperationTypes\StorageDeleteError;
use Momento\Storage\StorageOperationTypes\StorageDeleteSuccess;
use Momento\Storage\StorageOperationTypes\StorageGetResponse;
use Momento\Storage\StorageOperationTypes\StorageGetError;
use Momento\Storage\StorageOperationTypes\StorageGetSuccess;
use Momento\Storage\StorageOperationTypes\StorageSetResponse;
use Momento\Storage\StorageOperationTypes\StorageSetError;
use Momento\Storage\StorageOperationTypes\StorageSetSuccess;
use Momento\Utilities\_ErrorConverter;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Store\_StoreDeleteRequest;
use Store\_StoreGetRequest;
use Store\_StoreSetRequest;
use Store\_StoreValue;
use function Momento\Utilities\validateNullOrEmpty;
use function Momento\Utilities\validateOperationTimeout;
use function Momento\Utilities\validateStoreName;

class StorageDataClient implements LoggerAwareInterface
{
    private static int $DEFAULT_DEADLINE_MILLISECONDS = 5000;
    private int $deadline_milliseconds;
    // Used to convert deadline_milliseconds into microseconds for gRPC
    private static int $TIMEOUT_MULTIPLIER = 1000;
    private StorageGrpcManager $grpcManager;
    private LoggerInterface $logger;
    private int $timeout;

    public function __construct(IStorageConfiguration $configuration, ICredentialProvider $credentialProvider)
    {
        $operationTimeoutMs = $configuration
            ->getTransportStrategy()
            ->getGrpcConfig()
            ->getDeadlineMilliseconds();
        validateOperationTimeout($operationTimeoutMs);
        $this->deadline_milliseconds = $operationTimeoutMs ?? self::$DEFAULT_DEADLINE_MILLISECONDS;
        $this->timeout = $this->deadline_milliseconds * self::$TIMEOUT_MULTIPLIER;
        $this->grpcManager = new StorageGrpcManager($credentialProvider, $configuration);
        $this->setLogger($configuration->getLoggerFactory()->getLogger(get_class($this)));
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    private function processCall(UnaryCall $call)
    {
        [$response, $status] = $call->wait();
        if ($status->code !== 0) {
            $this->logger->debug("Data client error: {$status->details}");
            throw _ErrorConverter::convert($status->code, $status->details, $call->getMetadata());
        }
        return $response;
    }

    /**
     * @param string $storeName
     * @param string $key
     * @param string|int|double $value
     * @return ResponseFuture<StorageSetResponse>
     */
    public function set(string $storeName, string $key, $value): ResponseFuture
    {
        try {
            validateStoreName($storeName);
            validateNullOrEmpty($key, "Key");
            $storeValue = new _StoreValue();
            if (is_int($value)) {
                $storeValue->setIntegerValue($value);
            } elseif (is_double($value)) {
                $storeValue->setDoubleValue($value);
            } elseif (is_string($value)) {
                $storeValue->setStringValue($value);
            } else {
                throw new \InvalidArgumentException("Value must be a string, int, or double");
            }
            $setRequest = new _StoreSetRequest();
            $setRequest->setKey($key);
            $setRequest->setValue($storeValue);
            $call = $this->grpcManager->client->Set(
                $setRequest,
                ["store" => [$storeName]],
                ['timeout' => $this->timeout]
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new StorageSetError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new StorageSetError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): StorageSetResponse {
                try {
                    $this->processCall($call);
                } catch (SdkError $e) {
                    return new StorageSetError($e);
                } catch (Exception $e) {
                    return new StorageSetError(new UnknownError($e->getMessage(), 0, $e));
                }
                return new StorageSetSuccess();
            }
        );
    }

    public function get(string $storeName, string $key): ResponseFuture
    {
        try {
            validateStoreName($storeName);
            validateNullOrEmpty($key, "Key");
            $getRequest = new _StoreGetRequest();
            $getRequest->setKey($key);
            $call = $this->grpcManager->client->Get(
                $getRequest,
                ["store" => [$storeName]],
                ['timeout' => $this->timeout]
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new StorageGetError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new StorageGetError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): StorageGetResponse {
                try {
                    $response = $this->processCall($call);
                } catch (SdkError $e) {
                    return new StorageGetError($e);
                } catch (Exception $e) {
                    return new StorageGetError(new UnknownError($e->getMessage(), 0, $e));
                }
                return new StorageGetSuccess($response);
            }
        );
    }

    public function delete(string $storeName, string $key): ResponseFuture
    {
        try {
            validateStoreName($storeName);
            validateNullOrEmpty($key, "Key");
            $deleteRequest = new _StoreDeleteRequest();
            $deleteRequest->setKey($key);
            $call = $this->grpcManager->client->Delete(
                $deleteRequest,
                ["store" => [$storeName]],
                ['timeout' => $this->timeout]
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new StorageDeleteError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new StorageDeleteError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): StorageDeleteResponse {
                try {
                    $this->processCall($call);
                } catch (SdkError $e) {
                    return new StorageDeleteError($e);
                } catch (Exception $e) {
                    return new StorageDeleteError(new UnknownError($e->getMessage(), 0, $e));
                }
                return new StorageDeleteSuccess();
            }
        );
    }

    public function close(): void
    {
        $this->grpcManager->close();
    }
}
