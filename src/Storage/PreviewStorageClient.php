<?php
declare(strict_types=1);

namespace Momento\Storage;

use Momento\Auth\ICredentialProvider;
use Momento\Cache\CacheOperationTypes\ResponseFuture;
use Momento\Cache\Errors\InvalidArgumentError;
use Momento\Cache\Internal\IdleStorageDataClientWrapper;
use Momento\Cache\Internal\ScsControlClient;
use Momento\Config\IStorageConfiguration;
use Momento\Logging\ILoggerFactory;
use Momento\Storage\Internal\StorageDataClient;
use Momento\Storage\StorageOperationTypes\CreateStoreResponse;
use Momento\Storage\StorageOperationTypes\DeleteStoreResponse;
use Momento\Storage\StorageOperationTypes\ListStoresResponse;
use Momento\Storage\StorageOperationTypes\StorageDeleteResponse;
use Momento\Storage\StorageOperationTypes\StorageGetResponse;
use Momento\Storage\StorageOperationTypes\StoragePutResponse;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Preview client to perform operations against the Momento storage service.
 * WARNING: the API for this client is not yet stable and may change without notice.
 * Please contact Momento if you would like to try this preview.
 */
class PreviewStorageClient implements LoggerAwareInterface
{
    protected IStorageConfiguration $configuration;
    protected ILoggerFactory $loggerFactory;
    protected LoggerInterface $logger;
    private ScsControlClient $controlClient;

    /**
     * @var StorageDataClient[]
     */
    private array $dataClients;
    private int $nextDataClientIndex = 0;

    /**
     * @param IStorageConfiguration $configuration Storage configuration to use for transport.
     * @param ICredentialProvider $credentialProvider Momento authentication provider.
     * @throws InvalidArgumentError If the configuration is invalid.
     */
    public function __construct(IStorageConfiguration $configuration, ICredentialProvider $credentialProvider)
    {
        $this->configuration = $configuration;
        $this->loggerFactory = $configuration->getLoggerFactory();
        $this->setLogger($this->loggerFactory->getLogger(get_class($this)));
        $this->controlClient = new ScsControlClient($this->loggerFactory, $credentialProvider);
        $dataClientFactory = new \stdClass();
        $dataClientFactory->callback = function() use ($configuration, $credentialProvider) {
            return new StorageDataClient($configuration, $credentialProvider);
        };
        $this->dataClients = [];

        $numGrpcChannels = $configuration->getTransportStrategy()->getGrpcConfig()->getNumGrpcChannels();
        $forceNewChannels = $configuration->getTransportStrategy()->getGrpcConfig()->getForceNewChannel();
        if (($numGrpcChannels > 1) && (! $forceNewChannels)) {
            throw new InvalidArgumentError("When setting NumGrpcChannels > 1, you must also set ForceNewChannel to true, or else the gRPC library will re-use the same channel.");
        }
        for ($i = 0; $i < $numGrpcChannels; $i++) {
            $this->dataClients[] = new IdleStorageDataClientWrapper($dataClientFactory, $this->configuration);
        }
    }

    private function getNextDataClient(): StorageDataClient
    {
        $client = $this->dataClients[$this->nextDataClientIndex]->getClient();
        $this->nextDataClientIndex = ($this->nextDataClientIndex + 1) % count($this->dataClients);
        return $client;
    }

    /**
     * Close the client and free up all associated resources. NOTE: the client object will not be usable after calling
     * this method.
     */
    public function close(): void
    {
        $this->controlClient->close();
        foreach ($this->dataClients as $dataClient) {
            $dataClient->close();
        }
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

    /**
     * Creates a store if it doesn't exist.
     * @param string $storeName Name of the store to create
     * @return CreateStoreResponse Represents the result of the create store operation. This result is
     *  resolved to a type-safe object of one of the following types:<br>
     *  * CreateStoreSuccess<br>
     *  * CreateStoreError<br>
     *  Pattern matching can be to operate on the appropriate subtype:<br>
     *  <code>
     *  if ($error = $response->asError()) {
     *    // handle error condition<br>
     *  }
     *  </code>
     */
    public function createStore(string $storeName): CreateStoreResponse
    {
        return $this->controlClient->createStore($storeName);
    }

    /**
     * List existing stores.
     *
     * @return ListStoresResponse Represents the result of the list stores operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * ListStoresSuccess<br>
     * * ListStoresError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>
     * if ($error = $response->asError()) {
     *   // handle error condition
     * }
     * </code>
     */
    public function listStores(): ListStoresResponse
    {
        // `nextToken` isn't used at this point, and is just here as a reminder.
        $nextToken = null;
        return $this->controlClient->listStores($nextToken);
    }

    /**
     * Delete a store.
     *
     * @param string $storeName Name of the store to delete.
     * @return DeleteStoreResponse Represents the result of the delete store operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * DeleteStoreSuccess<br>
     * * DeleteStoreError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>
     * if ($error = $response->asError()) {
     *   // handle error condition
     * }
     * </code>
     */
    public function deleteStore(string $storeName): DeleteStoreResponse
    {
        return $this->controlClient->deleteStore($storeName);
    }

    /**
     * Put the value in the store. The value's type is inferred from the PHP type.
     * WARNING: because PHP does not have a native bytes type, this method cannot be used
     * to store bytes data. Use putBytes instead to store PHP strings as bytes.
     *
     * @param string $storeName Name of the store in which to put the value.
     * @param string $key The key to put.
     * @param int|float|string $value The value to be stored.
     * @return ResponseFuture<StoragePutResponse> A waitable future which will provide
     * the result of the put operation upon a blocking call to wait:<br />
     * <code>$response = $responseFuture->wait();</code><br />
     * The response represents the result of the put operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * StorageSetSuccess<br>
     * * StorageSetError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>
     * if ($error = $response->asError()) {
     *   // handle error condition
     * }
     * </code>
     * If inspection of the response is not required, one need not call wait as
     * we implicitly wait for completion of the request on destruction of the
     * response future.
     */
    public function putAsync(string $storeName, string $key, $value): ResponseFuture
    {
        return $this->getNextDataClient()->put($storeName, $key, $value);
    }

    /**
     * Put the value in the store. The value's type is inferred from the PHP type.
     * WARNING: because PHP does not have a native bytes type, this method cannot be used
     * to store bytes data. Use putBytes instead to store PHP strings as bytes.
     *
     * @param string $storeName Name of the store in which to put the value.
     * @param string $key The key to put.
     * @param int|float|string $value The value to be stored.
     * @return StoragePutResponse Represents the result of the put operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * StoragePutSuccess<br>
     * * StoragePutError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>
     * if ($error = $response->asError()) {
     *   // handle error condition
     * }
     * </code>
     */
    public function put(string $storeName, string $key, $value): StoragePutResponse
    {
        return $this->putAsync($storeName, $key, $value)->wait();
    }

    /**
     * Put the string value in the store.
     *
     * @param string $storeName Name of the store in which to put the value.
     * @param string $key The key to put.
     * @param string $value The value to be stored.
     * @return ResponseFuture<StoragePutResponse> A waitable future which will provide
     * the result of the put operation upon a blocking call to wait:<br />
     * <code>$response = $responseFuture->wait();</code><br />
     * The response represents the result of the put operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * StoragePutSuccess<br>
     * * StoragePutError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>
     * if ($error = $response->asError()) {
     *   // handle error condition
     * }
     * </code>
     * If inspection of the response is not required, one need not call wait as
     * we implicitly wait for completion of the request on destruction of the
     * response future.
     */
    public function putStringAsync(string $storeName, string $key, string $value): ResponseFuture
    {
        return $this->getNextDataClient()->putString($storeName, $key, $value);
    }

    /**
     * Put the string value in the store.
     *
     * @param string $storeName Name of the store in which to put the value.
     * @param string $key The key to put.
     * @param string $value The value to be stored.
     * @return StoragePutResponse Represents the result of the put operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * StoragePutSuccess<br>
     * * StoragePutError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>
     * if ($error = $response->asError()) {
     *   // handle error condition
     * }
     * </code>
     */
    public function putString(string $storeName, string $key, string $value): StoragePutResponse
    {
        return $this->putStringAsync($storeName, $key, $value)->wait();
    }

    /**
     * Put the bytes value in the store.
     *
     * @param string $storeName Name of the store in which to put the value.
     * @param string $key The key to put.
     * @param string $value The value to be stored.
     * @return ResponseFuture<StoragePutResponse> A waitable future which will provide
     * the result of the put operation upon a blocking call to wait:<br />
     * <code>$response = $responseFuture->wait();</code><br />
     * The response represents the result of the put operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * StoragePutSuccess<br>
     * * StoragePutError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>
     * if ($error = $response->asError()) {
     *   // handle error condition
     * }
     * </code>
     * If inspection of the response is not required, one need not call wait as
     * we implicitly wait for completion of the request on destruction of the
     * response future.
     */
    public function putBytesAsync(string $storeName, string $key, string $value): ResponseFuture
    {
        return $this->getNextDataClient()->putBytes($storeName, $key, $value);
    }

    /**
     * Put the bytes value in the store.
     *
     * @param string $storeName Name of the store in which to put the value.
     * @param string $key The key to put.
     * @param string $value The value to be stored.
     * @return StoragePutResponse Represents the result of the put operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * StoragePutSuccess<br>
     * * StoragePutError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>
     * if ($error = $response->asError()) {
     *   // handle error condition
     * }
     * </code>
     */
    public function putBytes(string $storeName, string $key, string $value): StoragePutResponse
    {
        return $this->putBytesAsync($storeName, $key, $value)->wait();
    }

    /**
     * Put the integer value in the store.
     *
     * @param string $storeName Name of the store in which to put the value.
     * @param string $key The key to put.
     * @param int $value The value to be stored.
     * @return ResponseFuture<StoragePutResponse> A waitable future which will provide
     * the result of the put operation upon a blocking call to wait:<br />
     * <code>$response = $responseFuture->wait();</code><br />
     * The response represents the result of the put operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * StoragePutSuccess<br>
     * * StoragePutError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>
     * if ($error = $response->asError()) {
     *   // handle error condition
     * }
     * </code>
     * If inspection of the response is not required, one need not call wait as
     * we implicitly wait for completion of the request on destruction of the
     * response future.
     */
    public function putIntegerAsync(string $storeName, string $key, int $value): ResponseFuture
    {
        return $this->getNextDataClient()->putInteger($storeName, $key, $value);
    }

    /**
     * Put the integer value in the store.
     *
     * @param string $storeName Name of the store in which to put the value.
     * @param string $key The key to put.
     * @param int $value The value to be stored.
     * @return StoragePutResponse Represents the result of the put operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * StoragePutSuccess<br>
     * * StoragePutError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>
     * if ($error = $response->asError()) {
     *   // handle error condition
     * }
     * </code>
     */
    public function putInteger(string $storeName, string $key, int $value): StoragePutResponse
    {
        return $this->putIntegerAsync($storeName, $key, $value)->wait();
    }

    /**
     * Put the bytes value in the store.
     *
     * @param string $storeName Name of the store in which to put the value.
     * @param string $key The key to put.
     * @param float $value The value to be stored.
     * @return ResponseFuture<StoragePutResponse> A waitable future which will provide
     * the result of the put operation upon a blocking call to wait:<br />
     * <code>$response = $responseFuture->wait();</code><br />
     * The response represents the result of the put operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * StoragePutSuccess<br>
     * * StoragePutError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>
     * if ($error = $response->asError()) {
     *   // handle error condition
     * }
     * </code>
     * If inspection of the response is not required, one need not call wait as
     * we implicitly wait for completion of the request on destruction of the
     * response future.
     */
    public function putDoubleAsync(string $storeName, string $key, float $value): ResponseFuture
    {
        return $this->getNextDataClient()->putDouble($storeName, $key, $value);
    }

    /**
     * Put the bytes value in the store.
     *
     * @param string $storeName Name of the store in which to put the value.
     * @param string $key The key to put.
     * @param float $value The value to be stored.
     * @return StoragePutResponse Represents the result of the put operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * StoragePutSuccess<br>
     * * StoragePutError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>
     * if ($error = $response->asError()) {
     *   // handle error condition
     * }
     * </code>
     */
    public function putDouble(string $storeName, string $key, float $value): StoragePutResponse
    {
        return $this->putDoubleAsync($storeName, $key, $value)->wait();
    }

    /**
     * Gets the value stored for a given key.
     *
     * @param string $storeName Name of the store to perform the lookup in.
     * @param string $key The key to look up.
     * @return ResponseFuture<StorageGetResponse> A waitable future which will provide
     * the result of the get operation upon a blocking call to wait:<br />
     * <code>$response = $responseFuture->wait();</code><br />
     * The response represents the result of the get operation and stores the
     * retrieved value. This result is resolved to a type-safe object of one of
     * the following types:<br>
     * * StorageGetSuccess<br>
     * * StorageGetError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>
     * if ($hit = $response->asSuccess()) {
     *   // retrieve the data type of the value and extract it as appropriate
     *   $type = $hit->type();
     *   if ($type == StorageValueType::STRING) {
     *      $value = $hit->tryGetString();
     *   } elseif ($type == StorageValueType::INTEGER) {
     *      $value = $hit->tryGetInteger();
     *   } elseif ($type == StorageValueType::DOUBLE) {
     *      $value = $hit->tryGetDouble();
     *   }
     * } elseif ($error = $response->asError()) {
     *   // handle error response
     * }
     * </code>
     * If inspection of the response is not required, one need not call wait as
     * we implicitly wait for completion of the request on destruction of the
     * response future.
     */
    public function getAsync(string $storeName, string $key): ResponseFuture
    {
        return $this->getNextDataClient()->get($storeName, $key);
    }

    /**
     * Gets the value stored for a given key.
     *
     * @param string $storeName Name of the store to perform the lookup in.
     * @param string $key The key to look up.
     * @return StorageGetResponse Represents the result of the get operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * StorageGetSuccess<br>
     * * StorageGetError<br>
     * Pattern matching can be to operate on the appropriate subtype and to retrieve the value:<br>
     * <code>
     * if ($hit = $response->asSuccess()) {
     *   // retrieve the data type of the value and extract it as appropriate
     *   $type = $hit->type();
     *   if ($type == StorageValueType::STRING) {
     *      $value = $hit->tryGetString();
     *   } elseif ($type == StorageValueType::INTEGER) {
     *      $value = $hit->tryGetInteger();
     *   } elseif ($type == StorageValueType::DOUBLE) {
     *      $value = $hit->tryGetDouble();
     *   }
     * } elseif ($error = $response->asError()) {
     *   // handle error response
     * }
     * </code>
     */
    public function get(string $storeName, string $key): StorageGetResponse
    {
        return $this->getAsync($storeName, $key)->wait();
    }

    /**
     * Removes the key from the store.
     *
     * @param string $storeName Name of the store from which to remove the key
     * @param string $key The key to remove
     * @return ResponseFuture<StorageDeleteResponse> A waitable future which will
     * provide the result of the set operation upon a blocking call to wait:<br />
     * <code>$response = $responseFuture->wait();</code><br />
     * The response represents the result of the delete operation. This result
     * is resolved to a type-safe object of one of the following types:<br>
     * * StorageDeleteSuccess<br>
     * * StorageDeleteError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>
     * if ($error = $response->asError()) {
     *   // handle error condition<br>
     * }
     * </code>
     * If inspection of the response is not required, one need not call wait as
     * we implicitly wait for completion of the request on destruction of the
     * response future.
     */
    public function deleteAsync(string $storeName, string $key): ResponseFuture
    {
        return $this->getNextDataClient()->delete($storeName, $key);
    }

    /**
     * Removes the key from the store.
     *
     * @param string $storeName Name of the store from which to remove the key
     * @param string $key The key to remove
     * @return StorageDeleteResponse Represents the result of the delete operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * StorageDeleteSuccess<br>
     * * StorageDeleteError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>
     * if ($error = $response->asError()) {
     *   // handle error condition<br>
     * }
     * </code>
     */
    public function delete(string $storeName, string $key): StorageDeleteResponse
    {
        return $this->deleteAsync($storeName, $key)->wait();
    }
}
