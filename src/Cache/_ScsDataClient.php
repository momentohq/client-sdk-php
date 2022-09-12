<?php
namespace Momento\Cache;

use Cache_client\_GetRequest;
use Cache_client\_SetRequest;
use Cache_client\_SetResponse;

use Momento\Cache\CacheOperationTypes\CacheGetResponse;
use Momento\Cache\CacheOperationTypes\CacheSetResponse;
use Momento\Utilities\_ErrorConverter;
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

    // TODO: DRY this out. It's a duplicate of the one in _ScsControlClient
    private function checkCallStatus(object $status) : void {
        if ($status->code !== 0) {
            throw _ErrorConverter::convert($status->code, $status->details);
        }
    }

    public function set(string $cacheName, string $key, string $value, int $ttlSeconds=null) : CacheSetResponse
    {
        validateCacheName($cacheName);
        try {
            $itemTtlSeconds = $ttlSeconds ? $ttlSeconds : $this->defaultTtlSeconds;
            validateTtl($itemTtlSeconds);
            $setRequest = new _SetRequest();
            $setRequest->setCacheKey($key);
            $setRequest->setCacheBody($value);
            $setRequest->setTtlMilliseconds($itemTtlSeconds * 1000);
            $call = $this->grpcManager->client->Set($setRequest, ["cache" => [$cacheName]]);
            [$response, $status] = $call->wait();
        } catch (\Exception $e) {
            throw $e;
        }
        $this->checkCallStatus($status);
        return new CacheSetResponse($response, $key, $value);
    }

    public function get(string $cacheName, string $key) : CacheGetResponse
    {
        validateCacheName($cacheName);
        try {
            $getRequest = new _GetRequest();
            $getRequest->setCacheKey($key);
            $call = $this->grpcManager->client->Get($getRequest, ["cache" => [$cacheName]]);
            [$response, $status] = $call->wait();
        } catch (\Exception $e) {
            throw $e;
        }
        $this->checkCallStatus($status);
        return new CacheGetResponse($response);
    }

}
