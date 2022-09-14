<?php

namespace Momento\Cache;

use Momento\Cache\Errors\InvalidArgumentError;

class SimpleCacheClient
{

    private _ScsControlClient $controlClient;
    private _ScsDataClient $dataClient;
    /**
     * @param string $authToken: momento JWT
     * @param int $defaultTtlSeconds: Default Time to Live for the item in Cache
     */
    function __construct(string $authToken, int $defaultTtlSeconds, ?int $dataClientOperationTimeoutMs=null)
    {
        $payload = $this->parseAuthToken($authToken);
        $this->controlClient = new _ScsControlClient($authToken, $payload["cp"]);
        $this->dataClient = new _ScsDataClient(
            $authToken, $payload["c"], $defaultTtlSeconds, $dataClientOperationTimeoutMs
        );
    }

    private function throwBadAuthToken() {
        throw new InvalidArgumentError('Invalid Auth token.');
    }

    private function parseAuthToken(string $authToken) : array {
        $exploded = explode (".", $authToken);
        if (count($exploded) != 3) {
            $this->throwBadAuthToken();
        }
        list($header, $payload, $signature) = $exploded;
        $token = json_decode(base64_decode($payload), true);
        if ($token === null) {
            $this->throwBadAuthToken();
        }
        return $token;
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

    public function delete(string $cacheName, string $key) : CacheOperationTypes\CacheDeleteResponse
    {
        return $this->dataClient->delete($cacheName, $key);
    }
}
