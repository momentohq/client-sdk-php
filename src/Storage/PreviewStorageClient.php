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

class PreviewStorageClient implements LoggerAwareInterface
{
    protected IStorageConfiguration $configuration;
    protected ILoggerFactory $loggerFactory;
    protected LoggerInterface $logger;
    private ScsControlClient $controlClient;
    private StorageDataClient $dataClient;

    public function __construct(IStorageConfiguration $configuration, ICredentialProvider $credentialProvider)
    {
        $this->configuration = $configuration;
        $this->loggerFactory = $configuration->getLoggerFactory();
        $this->setLogger($this->loggerFactory->getLogger(get_class($this)));
        $this->controlClient = new ScsControlClient($this->loggerFactory, $credentialProvider);
        $this->dataClient = new StorageDataClient($configuration, $credentialProvider);
    }

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

    public function createStore(string $storeName): CreateStoreResponse
    {
        return $this->controlClient->createStore($storeName);
    }

    public function listStores(?string $nextToken=null): ListStoresResponse
    {
        return $this->controlClient->listStores($nextToken);
    }

    public function deleteStore(string $storeName): DeleteStoreResponse
    {
        return $this->controlClient->deleteStore($storeName);
    }

    /**
     * @param string $storeName
     * @param string $key
     * @param int|double|string $value
     * @return ResponseFuture<StorageSetResponse>
     */
    public function setAsync(string $storeName, string $key, $value): ResponseFuture
    {
        return $this->dataClient->set($storeName, $key, $value);
    }

    public function set(string $storeName, string $key, $value): StorageSetResponse
    {
        return $this->setAsync($storeName, $key, $value)->wait();
    }

    public function getAsync(string $storeName, string $key): ResponseFuture
    {
        return $this->dataClient->get($storeName, $key);
    }

    public function get(string $storeName, string $key): StorageGetResponse
    {
        return $this->getAsync($storeName, $key)->wait();
    }

    public function deleteAsync(string $storeName, string $key): ResponseFuture
    {
        return $this->dataClient->delete($storeName, $key);
    }

    public function delete(string $storeName, string $key): StorageDeleteResponse
    {
        return $this->deleteAsync($storeName, $key)->wait();
    }
}
