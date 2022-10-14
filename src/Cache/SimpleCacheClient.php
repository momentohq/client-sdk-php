<?php

namespace Momento\Cache;

use Momento\Auth\ICredentialProvider;
use Momento\Cache\CacheOperationTypes\CacheDictionaryDeleteResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionarySetResponse;
use Momento\Cache\CacheOperationTypes\CacheListFetchResponse;
use Momento\Cache\CacheOperationTypes\CacheListPushBackResponse;
use Momento\Cache\CacheOperationTypes\CacheListPushFrontResponse;

class SimpleCacheClient
{

    private _ScsControlClient $controlClient;
    private _ScsDataClient $dataClient;

    /**
     * @param ICredentialProvider $authProvider : Momento credential provider
     * @param int $defaultTtlSeconds : Default Time to Live for the item in Cache
     * @param ?int $dataClientOperationTimeoutMs : msecs after which requests should be cancelled due to timeout
     */
    function __construct(
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

    public function createCache(string $cacheName): CacheOperationTypes\CreateCacheResponse
    {
        return $this->controlClient->createCache($cacheName);
    }

    public function listCaches(?string $nextToken = null): CacheOperationTypes\ListCachesResponse
    {
        return $this->controlClient->listCaches($nextToken);
    }

    public function deleteCache(string $cacheName): CacheOperationTypes\DeleteCacheResponse
    {
        return $this->controlClient->deleteCache($cacheName);
    }

    public function set(string $cacheName, string $key, string $value, int $ttlSeconds = 0): CacheOperationTypes\CacheSetResponse
    {
        return $this->dataClient->set($cacheName, $key, $value, $ttlSeconds);
    }

    public function get(string $cacheName, string $key): CacheOperationTypes\CacheGetResponse
    {
        return $this->dataClient->get($cacheName, $key);
    }

    public function delete(string $cacheName, string $key): CacheOperationTypes\CacheDeleteResponse
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

    public function dictionarySet(string $cacheName, string $dictionaryName, string $field, string $value, bool $refreshTtl, ?int $ttlSeconds = null): CacheDictionarySetResponse
    {
        return $this->dataClient->dictionarySet($cacheName, $dictionaryName, $field, $value, $refreshTtl, $ttlSeconds);
    }

    public function dictionaryDelete(string $cacheName, string $dictionaryName): CacheDictionaryDeleteResponse
    {
        return $this->dataClient->dictionaryDelete($cacheName, $dictionaryName);
    }
}
