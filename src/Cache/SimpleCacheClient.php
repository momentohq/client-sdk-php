<?php
declare(strict_types=1);

namespace Momento\Cache;

use Momento\Auth\ICredentialProvider;
use Momento\Cache\CacheOperationTypes\CacheDeleteResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryDeleteResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryFetchResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryGetFieldResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryGetFieldsResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryIncrementResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryRemoveFieldResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryRemoveFieldsResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionarySetFieldResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionarySetFieldsResponse;
use Momento\Cache\CacheOperationTypes\CacheGetResponse;
use Momento\Cache\CacheOperationTypes\CacheListFetchResponse;
use Momento\Cache\CacheOperationTypes\CacheListLengthResponse;
use Momento\Cache\CacheOperationTypes\CacheListPopBackResponse;
use Momento\Cache\CacheOperationTypes\CacheListPopFrontResponse;
use Momento\Cache\CacheOperationTypes\CacheListPushBackResponse;
use Momento\Cache\CacheOperationTypes\CacheListPushFrontResponse;
use Momento\Cache\CacheOperationTypes\CacheListRemoveValueResponse;
use Momento\Cache\CacheOperationTypes\CacheSetAddElementResponse;
use Momento\Cache\CacheOperationTypes\CacheSetFetchResponse;
use Momento\Cache\CacheOperationTypes\CacheSetRemoveElementResponse;
use Momento\Cache\CacheOperationTypes\CacheSetResponse;
use Momento\Cache\CacheOperationTypes\CreateCacheResponse;
use Momento\Cache\CacheOperationTypes\DeleteCacheResponse;
use Momento\Cache\CacheOperationTypes\ListCachesResponse;
use Momento\Config\IConfiguration;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

class SimpleCacheClient implements LoggerAwareInterface
{

    protected IConfiguration $configuration;
    protected LoggerInterface $logger;
    private _ScsControlClient $controlClient;
    private _ScsDataClient $dataClient;

    /**
     * @param IConfiguration $configuration
     * @param ICredentialProvider $authProvider
     * @param int $defaultTtlSeconds
     */
    public function __construct(
        IConfiguration $configuration, ICredentialProvider $authProvider, int $defaultTtlSeconds
    )
    {
        $this->configuration = $configuration;
        $this->setLogger($this->configuration->getLogger());
        $this->controlClient = new _ScsControlClient($this->logger, $authProvider);
        $this->dataClient = new _ScsDataClient(
            $this->configuration,
            $authProvider,
            $defaultTtlSeconds
        );
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    public function createCache(string $cacheName): CreateCacheResponse
    {
        return $this->controlClient->createCache($cacheName);
    }

    public function listCaches(?string $nextToken = null): ListCachesResponse
    {
        return $this->controlClient->listCaches($nextToken);
    }

    public function deleteCache(string $cacheName): DeleteCacheResponse
    {
        return $this->controlClient->deleteCache($cacheName);
    }

    public function set(string $cacheName, string $key, string $value, int $ttlSeconds = 0): CacheSetResponse
    {
        return $this->dataClient->set($cacheName, $key, $value, $ttlSeconds);
    }

    public function get(string $cacheName, string $key): CacheGetResponse
    {
        return $this->dataClient->get($cacheName, $key);
    }

    public function delete(string $cacheName, string $key): CacheDeleteResponse
    {
        return $this->dataClient->delete($cacheName, $key);
    }

    public function listFetch(string $cacheName, string $listName): CacheListFetchResponse
    {
        return $this->dataClient->listFetch($cacheName, $listName);
    }

    public function listPushFront(
        string $cacheName, string $listName, string $value, bool $refreshTtl, ?int $ttlSeconds = null, ?int $truncateBackToSize = null
    ): CacheListPushFrontResponse
    {
        return $this->dataClient->listPushFront($cacheName, $listName, $value, $refreshTtl, $truncateBackToSize, $ttlSeconds);
    }

    public function listPushBack(
        string $cacheName, string $listName, string $value, bool $refreshTtl, ?int $ttlSeconds = null, ?int $truncateFrontToSize = null
    ): CacheListPushBackResponse
    {
        return $this->dataClient->listPushBack($cacheName, $listName, $value, $refreshTtl, $truncateFrontToSize, $ttlSeconds);
    }

    public function listPopFront(string $cacheName, string $listName): CacheListPopFrontResponse
    {
        return $this->dataClient->listPopFront($cacheName, $listName);
    }

    public function listPopBack(string $cacheName, string $listName): CacheListPopBackResponse
    {
        return $this->dataClient->listPopBack($cacheName, $listName);
    }

    public function listRemoveValue(string $cacheName, string $listName, string $value): CacheListRemoveValueResponse
    {
        return $this->dataClient->listRemoveValue($cacheName, $listName, $value);
    }

    public function listLength(string $cacheName, string $listName): CacheListLengthResponse
    {
        return $this->dataClient->listLength($cacheName, $listName);
    }

    public function listErase(string $cacheName, string $listName, ?int $beginIndex = null, ?int $count = null)
    {
        return $this->dataClient->listErase($cacheName, $listName, $beginIndex, $count);
    }

    public function dictionarySetField(string $cacheName, string $dictionaryName, string $field, string $value, bool $refreshTtl, ?int $ttlSeconds = null): CacheDictionarySetFieldResponse
    {
        return $this->dataClient->dictionarySetField($cacheName, $dictionaryName, $field, $value, $refreshTtl, $ttlSeconds);
    }

    public function dictionaryGetField(string $cacheName, string $dictionaryName, string $field): CacheDictionaryGetFieldResponse
    {
        return $this->dataClient->dictionaryGetField($cacheName, $dictionaryName, $field);
    }

    public function dictionaryDelete(string $cacheName, string $dictionaryName): CacheDictionaryDeleteResponse
    {
        return $this->dataClient->dictionaryDelete($cacheName, $dictionaryName);
    }

    public function dictionaryFetch(string $cacheName, string $dictionaryName): CacheDictionaryFetchResponse
    {
        return $this->dataClient->dictionaryFetch($cacheName, $dictionaryName);
    }

    public function dictionarySetFields(string $cacheName, string $dictionaryName, array $items, bool $refreshTtl, ?int $ttlSeconds = null): CacheDictionarySetFieldsResponse
    {
        return $this->dataClient->dictionarySetFields($cacheName, $dictionaryName, $items, $refreshTtl, $ttlSeconds);
    }

    public function dictionaryGetFields(string $cacheName, string $dictionaryName, array $fields): CacheDictionaryGetFieldsResponse
    {
        return $this->dataClient->dictionaryGetFields($cacheName, $dictionaryName, $fields);
    }

    public function dictionaryIncrement(
        string $cacheName, string $dictionaryName, string $field, bool $refreshTtl, int $amount = 1, ?int $ttlSeconds = null
    ): CacheDictionaryIncrementResponse
    {
        return $this->dataClient->dictionaryIncrement($cacheName, $dictionaryName, $field, $refreshTtl, $amount, $ttlSeconds);
    }

    public function dictionaryRemoveField(string $cacheName, string $dictionaryName, string $field): CacheDictionaryRemoveFieldResponse
    {
        return $this->dataClient->dictionaryRemoveField($cacheName, $dictionaryName, $field);
    }

    public function dictionaryRemoveFields(string $cacheName, string $dictionaryName, array $fields): CacheDictionaryRemoveFieldsResponse
    {
        return $this->dataClient->dictionaryRemoveFields($cacheName, $dictionaryName, $fields);
    }

    public function setAddElement(string $cacheName, string $setName, string $element, bool $refreshTtl, ?int $ttlSeconds = null): CacheSetAddElementResponse
    {
        return $this->dataClient->setAddElement($cacheName, $setName, $element, $refreshTtl, $ttlSeconds);
    }

    public function setFetch(string $cacheName, string $setName): CacheSetFetchResponse
    {
        return $this->dataClient->setFetch($cacheName, $setName);
    }

    public function setRemoveElement(string $cacheName, string $setName, string $element): CacheSetRemoveElementResponse 
    {
        return $this->dataClient->setRemoveElement($cacheName, $setName, $element);
    }
}
