<?php

namespace Momento\Cache;

class SimpleCacheClient
{

    private _ScsControlClient $controlClient;
    private _ScsDataClient $dataClient;
    /**
     * @param string $authToken: momento JWT
     * @param int $defaultTtlSeconds: Default Time to Live for the item in Cache
     */
    function __construct(string $authToken, int $defaultTtlSeconds, int $dataClientOperationTimeoutMs=0)
    {
        $payload = $this->parseAuthToken($authToken);
        $this->controlClient = new _ScsControlClient($authToken, $payload["cp"]);
        $this->dataClient = new _ScsDataClient(
            $authToken, $payload["c"], $defaultTtlSeconds, $dataClientOperationTimeoutMs
        );
    }

    private function parseAuthToken(string $authToken) : array {
        list($header, $payload, $signature) = explode (".", $authToken);
        return json_decode(base64_decode($payload), true);
    }

    public function createCache(string $cacheName) : CacheOperationTypes\CreateCacheResponse
    {
        return $this->controlClient->createCache($cacheName);
    }

    public function listCaches(?string $nextToken=null) : CacheOperationTypes\ListCachesResponse
    {
        return $this->controlClient->listCaches($nextToken);
    }

    public function deleteCache(string $cacheName) : CacheOperationTypes\DeleteCacheResponse
    {
        return $this->controlClient->deleteCache($cacheName);
    }

    public function set(string $cacheName, string $key, string $value, int $ttlSeconds=0) : CacheOperationTypes\CacheSetResponse
    {
        return $this->dataClient->set($cacheName, $key, $value, $ttlSeconds);
    }

    public function get(string $cacheName, string $key) : CacheOperationTypes\CacheGetResponse
    {
        return $this->dataClient->get($cacheName, $key);
    }
}
