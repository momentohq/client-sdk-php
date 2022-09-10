<?php
namespace Momento\Cache;

use Cache_client\_GetRequest;
use Cache_client\_SetRequest;
use Cache_client\_SetResponse;

use function Momento\Utilities\validateTtl;
use function Momento\Utilities\validateCacheName;

class _ScsDataClient
{

    private static int $DEFAULT_DEADLINE_SECONDS = 5;
    private int $deadline_seconds;
    private int $defaultTtlSeconds;
    private string $endpoint;
    private _DataGrpcManager $grpcManager;

    public function __construct(string $authToken, string $endpoint, int $defaultTtlSeconds, int $operationTimeoutMs=null)
    {
        validateTtl($defaultTtlSeconds);
        $this->defaultTtlSeconds = $defaultTtlSeconds;
        $this->deadline_seconds = $operationTimeoutMs ? $operationTimeoutMs / 1000.0 : self::$DEFAULT_DEADLINE_SECONDS;
        $this->grpcManager = new _DataGrpcManager($authToken, $endpoint);
        $this->endpoint = $endpoint;
    }

    public function set(string $cacheName, string $key, string $value, int $ttlSeconds=null) : array
    {
        $itemTtlSeconds = $ttlSeconds ? $ttlSeconds : $this->defaultTtlSeconds;
        validateTtl($itemTtlSeconds);
        $setRequest = new _SetRequest();
        $setRequest->setCacheKey($key);
        $setRequest->setCacheBody($value);
        $setRequest->setTtlMilliseconds($itemTtlSeconds * 1000);
        $call = $this->grpcManager->client->Set($setRequest, ["cache"=>[$cacheName]]);
        [$response, $status] = $call->wait();
        return [$response, $status];
    }

    public function get(string $cacheName, string $key) : array
    {
        validateCacheName($cacheName);
        $getRequest = new _GetRequest();
        $getRequest->setCacheKey($key);
        $call = $this->grpcManager->client->Get($getRequest, ["cache"=>[$cacheName]]);
        [$response, $status] = $call->wait();
        return [$response, $status];
    }

}
