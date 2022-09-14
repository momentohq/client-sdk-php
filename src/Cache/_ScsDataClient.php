<?php
namespace Momento\Cache;

use Cache_client\_DeleteRequest;
use Cache_client\_GetRequest;
use Cache_client\_SetRequest;
use Cache_client\_SetResponse;

use Momento\Cache\CacheOperationTypes\CacheDeleteResponse;
use Momento\Cache\CacheOperationTypes\CacheGetResponse;
use Momento\Cache\CacheOperationTypes\CacheSetResponse;
use Momento\Utilities\_ErrorConverter;
use function Momento\Utilities\validateTtl;
use function Momento\Utilities\validateCacheName;
use function Momento\Utilities\validateOperationTimeout;

class _ScsDataClient
{

    private static int $DEFAULT_DEADLINE = 500000; // I believe this maps to 5 sec.
    private int $deadline_seconds;
    private int $defaultTtlSeconds;
    private string $endpoint;
    private _DataGrpcManager $grpcManager;

    public function __construct(string $authToken, string $endpoint, int $defaultTtlSeconds, ?int $operationTimeoutMs)
    {
        validateTtl($defaultTtlSeconds);
        validateOperationTimeout($operationTimeoutMs);
        $this->defaultTtlSeconds = $defaultTtlSeconds;
        $this->deadline_seconds = $operationTimeoutMs ? $operationTimeoutMs / 1000.0 : self::$DEFAULT_DEADLINE;
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
            $call = $this->grpcManager->client->Set($setRequest, ["cache"=>[$cacheName]], ["timeout"=>$this->deadline_seconds]);
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
            $call = $this->grpcManager->client->Get($getRequest, ["cache" => [$cacheName]], ["timeout"=>$this->deadline_seconds]);
            [$response, $status] = $call->wait();
        } catch (\Exception $e) {
            throw $e;
        }
        $this->checkCallStatus($status);
        return new CacheGetResponse($response);
    }

    public function delete(string $cacheName, string $key) : CacheDeleteResponse
    {
        validateCacheName($cacheName);
        try {
            $deleteRequest = new _DeleteRequest();
            $deleteRequest->setCacheKey($key);
            $call = $this->grpcManager->client->Delete($deleteRequest, ["cache" => [$cacheName]], ["timeout" => $this->deadline_seconds]);
            [$response, $status] = $call->wait();
        } catch (Exception $e) {
            throw $e;
        }
        $this->checkCallStatus($status);
        return new CacheDeleteResponse();
    }

}
