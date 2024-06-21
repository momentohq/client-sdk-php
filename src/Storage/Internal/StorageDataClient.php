<?php
declare(strict_types=1);

namespace Momento\Storage\Internal;

use Exception;
use Grpc\UnaryCall;
use Momento\Auth\ICredentialProvider;
use Momento\Cache\CacheOperationTypes\ResponseFuture;
use Momento\Cache\Errors\ItemNotFoundError;
use Momento\Cache\Errors\SdkError;
use Momento\Cache\Errors\StoreNotFoundError;
use Momento\Cache\Errors\UnknownError;
use Momento\Config\IStorageConfiguration;
use Momento\Storage\StorageOperationTypes\StorageDeleteResponse;
use Momento\Storage\StorageOperationTypes\StorageDeleteError;
use Momento\Storage\StorageOperationTypes\StorageDeleteSuccess;
use Momento\Storage\StorageOperationTypes\StorageGetResponse;
use Momento\Storage\StorageOperationTypes\StorageGetError;
use Momento\Storage\StorageOperationTypes\StorageGetSuccess;
use Momento\Storage\StorageOperationTypes\StoragePutResponse;
use Momento\Storage\StorageOperationTypes\StoragePutError;
use Momento\Storage\StorageOperationTypes\StoragePutSuccess;
use Momento\Storage\StorageOperationTypes\StorageValueType;
use Momento\Utilities\_ErrorConverter;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use Store\_StoreDeleteRequest;
use Store\_StoreGetRequest;
use Store\_StorePutRequest;
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
            $this->logger->debug("Storage client error: {$status->details}");
            throw _ErrorConverter::convert($status, $call->getMetadata());
        }
        return $response;
    }

    /**
     * @param string $storeName
     * @param string $key
     * @param string $value
     * @return ResponseFuture<StoragePutResponse>
     */
    public function putString(string $storeName, string $key, string $value): ResponseFuture
    {
        return $this->_put($storeName, $key, $value, StorageValueType::STRING);
    }

    /**
     * @param string $storeName
     * @param string $key
     * @param int $value
     * @return ResponseFuture<StoragePutResponse>
     */
    public function putInteger(string $storeName, string $key, int $value): ResponseFuture
    {
        return $this->_put($storeName, $key, $value, StorageValueType::INTEGER);
    }

    /**
     * @param string $storeName
     * @param string $key
     * @param float $value
     * @return ResponseFuture<StoragePutResponse>
     */
    public function putDouble(string $storeName, string $key, float $value): ResponseFuture
    {
        return $this->_put($storeName, $key, $value, StorageValueType::DOUBLE);
    }

    /**
     * @param string $storeName
     * @param string $key
     * @param string $value
     * @return ResponseFuture<StoragePutResponse>
     */
    public function putBytes(string $storeName, string $key, string $value): ResponseFuture
    {
        return $this->_put($storeName, $key, $value, StorageValueType::BYTES);
    }

    /**
     * @param string $storeName
     * @param string $key
     * @param string|int|float $value
     * @param string $type
     * @return ResponseFuture<StoragePutResponse>
     */
    private function _put(string $storeName, string $key, $value, string $type): ResponseFuture
    {
        try {
            validateStoreName($storeName);
            validateNullOrEmpty($key, "Key");
            $storeValue = new _StoreValue();
            if ($type == StorageValueType::INTEGER) {
                $storeValue->setIntegerValue($value);
            } elseif ($type == StorageValueType::DOUBLE) {
                $storeValue->setDoubleValue($value);
            } elseif ($type == StorageValueType::STRING) {
                $storeValue->setStringValue($value);
            } elseif ($type == StorageValueType::BYTES) {
                $storeValue->setBytesValue($value);
            }
            $putRequest = new _StorePutRequest();
            $putRequest->setKey($key);
            $putRequest->setValue($storeValue);
            $call = $this->grpcManager->client->Put(
                $putRequest,
                ["store" => [$storeName]],
                ['timeout' => $this->timeout]
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new StoragePutError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new StoragePutError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): StoragePutResponse {
                try {
                    $this->processCall($call);
                } catch (SdkError $e) {
                    return new StoragePutError($e);
                } catch (Exception $e) {
                    return new StoragePutError(new UnknownError($e->getMessage(), 0, $e));
                }
                return new StoragePutSuccess();
            }
        );
    }

    /**
     * @param string $storeName
     * @param string $key
     * @return ResponseFuture<StorageGetResponse>
     */
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
                } catch (ItemNotFoundError $e) {
                    return new StorageGetSuccess();
                } catch (SdkError $e) {
                    return new StorageGetError($e);
                } catch (Exception $e) {
                    return new StorageGetError(new UnknownError($e->getMessage(), 0, $e));
                }
                return new StorageGetSuccess($response);
            }
        );
    }

    /**
     * @param string $storeName
     * @param string $key
     * @return ResponseFuture<StorageDeleteResponse>
     */
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
