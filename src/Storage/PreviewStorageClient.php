<?php
declare(strict_types=1);

namespace Momento\Storage;

use Momento\Auth\ICredentialProvider;
use Momento\Cache\CacheOperationTypes\ResponseFuture;
use Momento\Cache\Internal\ScsControlClient;
use Momento\Config\IStorageConfiguration;
use Momento\Logging\ILoggerFactory;
use Momento\Storage\Internal\StorageDataClient;
use Momento\Storage\StorageOperationTypes\CreateStoreResponse;
use Momento\Storage\StorageOperationTypes\DeleteStoreResponse;
use Momento\Storage\StorageOperationTypes\ListStoresResponse;
use Momento\Storage\StorageOperationTypes\StorageDeleteResponse;
use Momento\Storage\StorageOperationTypes\StorageGetResponse;
use Momento\Storage\StorageOperationTypes\StorageSetResponse;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Client to perform operations against the Momento storage service.
 */
class PreviewStorageClient implements LoggerAwareInterface
{
    protected IStorageConfiguration $configuration;
    protected ILoggerFactory $loggerFactory;
    protected LoggerInterface $logger;
    private ScsControlClient $controlClient;
    private StorageDataClient $dataClient;

    /**
     * @param IStorageConfiguration $configuration Storage configuration to use for transport.
     * @param ICredentialProvider $credentialProvider Momento authentication provider.
     */
    public function __construct(IStorageConfiguration $configuration, ICredentialProvider $credentialProvider)
    {
        $this->configuration = $configuration;
        $this->loggerFactory = $configuration->getLoggerFactory();
        $this->setLogger($this->loggerFactory->getLogger(get_class($this)));
        $this->controlClient = new ScsControlClient($this->loggerFactory, $credentialProvider);
        $this->dataClient = new StorageDataClient($configuration, $credentialProvider);
    }

    /**
     * Close the client and free up all associated resources. NOTE: the client object will not be usable after calling
     * this method.
     */
    public function close(): void
    {
        $this->controlClient->close();
        $this->dataClient->close();
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
     * Set the value in the store.
     *
     * @param string $storeName Name of the store in which to set the value.
     * @param string $key The key to set.
     * @param string|int|float $value The value to be stored.
     * @return ResponseFuture<StorageSetResponse> A waitable future which will provide
     * the result of the set operation upon a blocking call to wait:<br />
     * <code>$response = $responseFuture->wait();</code><br />
     * The response represents the result of the set operation. This result is
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
    public function setAsync(string $storeName, string $key, $value): ResponseFuture
    {
        return $this->dataClient->set($storeName, $key, $value);
    }

    /**
     * Set the value in the store.
     *
     * @param string $storeName Name of the store in which to set the value.
     * @param string $key The key to set.
     * @param string|int|float $value The value to be stored.
     * @return StorageSetResponse Represents the result of the set operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * StorageSetSuccess<br>
     * * StorageSetError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>
     * if ($error = $response->asError()) {
     *   // handle error condition
     * }
     * </code>
     */
    public function set(string $storeName, string $key, $value): StorageSetResponse
    {
        return $this->setAsync($storeName, $key, $value)->wait();
    }

    /**
     * Gets the value stored for a given key.
     *
     * @param string $storeName Name of the store to perform the lookup in.
     * @param string $key The key to look up.
     * @return ResponseFuture<StorageGetResponse> A waitable future which will provide
     * the result of the set operation upon a blocking call to wait:<br />
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
        return $this->dataClient->get($storeName, $key);
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
        return $this->dataClient->delete($storeName, $key);
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
