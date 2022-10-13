<?php
namespace Momento\Cache;

use Cache_client\_DeleteRequest;
use Cache_client\_GetRequest;
use Cache_client\_ListPushFrontRequest;
use Cache_client\_SetRequest;

use Cache_client\ECacheResult;
use Grpc\UnaryCall;
use Momento\Cache\CacheOperationTypes\CacheDeleteResponse;
use Momento\Cache\CacheOperationTypes\CacheDeleteResponseSuccess;
use Momento\Cache\CacheOperationTypes\CacheGetResponse;
use Momento\Cache\CacheOperationTypes\CacheGetResponseError;
use Momento\Cache\CacheOperationTypes\CacheGetResponseHit;
use Momento\Cache\CacheOperationTypes\CacheGetResponseMiss;
use Momento\Cache\CacheOperationTypes\CacheGetStatus;
use Momento\Cache\CacheOperationTypes\CacheSetResponse;
use Momento\Cache\CacheOperationTypes\CacheSetResponseError;
use Momento\Cache\CacheOperationTypes\CacheSetResponseSuccess;
use Momento\Cache\CacheOperationTypes\CreateCacheResponseError;
use Momento\Cache\Errors\InternalServerError;
use Momento\Cache\Errors\SdkError;
use Momento\Cache\Errors\UnknownError;
use Momento\Utilities\_ErrorConverter;
use function Momento\Utilities\validateListName;
use function Momento\Utilities\validateTtl;
use function Momento\Utilities\validateCacheName;
use function Momento\Utilities\validateOperationTimeout;

class _ScsDataClient
{

    private static int $DEFAULT_DEADLINE_SECONDS = 5;
    // TODO: is looks like PHP gRPC wants microsecond timeout values,
    // but python's wanted seconds. Need to take a closer look to make sure
    // this is accurate.
    private static int $TIMEOUT_MULTIPLIER = 1000000;
    private int $deadline_seconds;
    private int $defaultTtlSeconds;
    private string $endpoint;
    private _DataGrpcManager $grpcManager;

    public function __construct(string $authToken, string $endpoint, int $defaultTtlSeconds, ?int $operationTimeoutMs)
    {
        validateTtl($defaultTtlSeconds);
        validateOperationTimeout($operationTimeoutMs);
        $this->defaultTtlSeconds = $defaultTtlSeconds;
        $this->deadline_seconds = $operationTimeoutMs ? $operationTimeoutMs / 1000.0 : self::$DEFAULT_DEADLINE_SECONDS;
        $this->grpcManager = new _DataGrpcManager($authToken, $endpoint);
        $this->endpoint = $endpoint;
    }

    private function processCall(UnaryCall $call) : mixed
    {
        [$response, $status] = $call->wait();
        if ($status->code !== 0) {
            throw _ErrorConverter::convert($status->code, $status->details, $call->getMetadata());
        }
        return $response;
    }

    public function set(string $cacheName, string $key, string $value, int $ttlSeconds=null) : CacheSetResponse
    {
        try {
            validateCacheName($cacheName);
            $itemTtlSeconds = $ttlSeconds ? $ttlSeconds : $this->defaultTtlSeconds;
            validateTtl($itemTtlSeconds);
            $setRequest = new _SetRequest();
            $setRequest->setCacheKey($key);
            $setRequest->setCacheBody($value);
            $setRequest->setTtlMilliseconds($itemTtlSeconds * 1000);
            $call = $this->grpcManager->client->Set(
                $setRequest, ["cache"=>[$cacheName]], ["timeout"=>$this->deadline_seconds * self::$TIMEOUT_MULTIPLIER]
            );
            $response  = $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheSetResponseError($e);
        } catch (\Exception $e){
            return new CacheSetResponseError(new UnknownError($e->getMessage()));
        }
        return new CacheSetResponseSuccess($response, $key, $value);
    }

    public function get(string $cacheName, string $key) : CacheGetResponse
    {
        try {
            validateCacheName($cacheName);
            $getRequest = new _GetRequest();
            $getRequest->setCacheKey($key);
            $call = $this->grpcManager->client->Get(
                $getRequest, ["cache" => [$cacheName]], ["timeout"=>$this->deadline_seconds * self::$TIMEOUT_MULTIPLIER]
            );
            $response = $this->processCall($call);
            $ecacheResult = $response->getResult();
            if ($ecacheResult == ECacheResult::Hit) {
                return new CacheGetResponseHit($response);
            } elseif ($ecacheResult == ECacheResult::Miss) {
                return new CacheGetResponseMiss();
            } else {
                throw new InternalServerError("CacheService returned an unexpected result: $ecacheResult");
            }
        } catch (SdkError $e) {
            return new CacheGetResponseError($e);
        } catch (\Exception $e){
            return new CacheGetResponseError(new UnknownError($e->getMessage()));
        }
    }

    public function delete(string $cacheName, string $key) : CacheDeleteResponse
    {
        try {
            validateCacheName($cacheName);
            $deleteRequest = new _DeleteRequest();
            $deleteRequest->setCacheKey($key);
            $call = $this->grpcManager->client->Delete(
                $deleteRequest, ["cache" => [$cacheName]], ["timeout" => $this->deadline_seconds * self::$TIMEOUT_MULTIPLIER]
            );
            $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheDeleteResponseError($e);
        } catch (\Exception $e){
            return new CacheGetResponseError(new UnknownError($e->getMessage()));
        }
        return new CacheDeleteResponseSuccess();
    }

}
