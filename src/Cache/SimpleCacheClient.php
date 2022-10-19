<?php

namespace Momento\Cache;

use Momento\Auth\ICredentialProvider;
use Momento\Cache\CacheOperationTypes\CacheDeleteResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryDeleteResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryFetchResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryGetResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionarySetBatchResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionarySetResponse;
use Momento\Cache\CacheOperationTypes\CacheGetResponse;
use Momento\Cache\CacheOperationTypes\CacheListFetchResponse;
use Momento\Cache\CacheOperationTypes\CacheListLengthResponse;
use Momento\Cache\CacheOperationTypes\CacheListPopBackResponse;
use Momento\Cache\CacheOperationTypes\CacheListPopFrontResponse;
use Momento\Cache\CacheOperationTypes\CacheListPushBackResponse;
use Momento\Cache\CacheOperationTypes\CacheListPushFrontResponse;
use Momento\Cache\CacheOperationTypes\CacheListRemoveValueResponse;
use Momento\Cache\CacheOperationTypes\CacheSetResponse;
use Momento\Cache\CacheOperationTypes\CreateCacheResponse;
use Momento\Cache\CacheOperationTypes\DeleteCacheResponse;
use Momento\Cache\CacheOperationTypes\ListCachesResponse;

class SimpleCacheClient
{

    private _ScsControlClient $controlClient;
    private _ScsDataClient $dataClient;

    /**
     * @param ICredentialProvider $authProvider : Momento credential provider
     * @param int $defaultTtlSeconds : Default Time to Live for the item in Cache
     * @param ?int $dataClientOperationTimeoutMs : msecs after which requests should be cancelled due to timeout
     */
    public function __construct(
        ICredentialProvider $authProvider, int $defaultTtlSeconds, ?int $dataClientOperationTimeoutMs = null
    )
    {
        $this->controlClient = new _ScsControlClient($authProvider->getAuthToken(), $authProvider->getControlEndpoint());
        $this->dataClient = new _ScsDataClient(
            $authProvider->getAuthToken(),
            $authProvider->getCacheEndpoint(),
            $defaultTtlSeconds,
            $dataClientOperationTimeoutMs
        );
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

    public function dictionarySet(string $cacheName, string $dictionaryName, string $field, string $value, bool $refreshTtl, ?int $ttlSeconds = null): CacheDictionarySetResponse
    {
        return $this->dataClient->dictionarySet($cacheName, $dictionaryName, $field, $value, $refreshTtl, $ttlSeconds);
    }

    public function dictionaryGet(string $cacheName, string $dictionaryName, string $field): CacheDictionaryGetResponse
    {
        return $this->dataClient->dictionaryGet($cacheName, $dictionaryName, $field);
    }

    public function dictionaryDelete(string $cacheName, string $dictionaryName): CacheDictionaryDeleteResponse
    {
        return $this->dataClient->dictionaryDelete($cacheName, $dictionaryName);
    }

    public function dictionaryFetch(string $cacheName, string $dictionaryName): CacheDictionaryFetchResponse
    {
        return $this->dataClient->dictionaryFetch($cacheName, $dictionaryName);
    }

    public function dictionarySetBatch(string $cacheName, string $dictionaryName, array $items, bool $refreshTtl, ?int $ttlSeconds = null): CacheDictionarySetBatchResponse
    {
        return $this->dataClient->dictionarySetBatch($cacheName, $dictionaryName, $items, $refreshTtl, $ttlSeconds);
    }
}
