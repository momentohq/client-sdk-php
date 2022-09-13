<?php
namespace Momento\Cache\CacheOperationTypes;

use Cache_client\_GetResponse;
use Cache_client\ECacheResult;
use Control_client\_ListCachesResponse;
use Cache_client\_SetResponse;
use Momento\Cache\Errors\InternalServerError;

class CreateCacheResponse {}

class DeleteCacheResponse {}

class ListCachesResponse
{

    private string $nextToken;
    private array $caches = [];

    public function __construct(_ListCachesResponse $response) {
        $this->nextToken = $response->getNextToken() ? $response->getNextToken() : "";
        foreach ($response->getCache() as $cache) {
            array_push($this->caches , new CacheInfo($cache));
        }
    }

    public function caches() : array
    {
        return $this->caches;
    }

    public function nextToken() : string
    {
        return $this->nextToken;
    }
}

class CacheInfo
{
    private string $name;

    public function __construct($grpcListedCache) {
        $this->name = $grpcListedCache->getCacheName();
    }

    public function name() : string {
        return $this->name;
    }
}

class CacheSetResponse
{

    private string $key;
    private string $value;

    public function __construct(_SetResponse $grpcSetResponse, string $key, string $value)
    {
        $this->key = $key;
        $this->value = $value;
    }

    public function key() : string {
        return $this->key;
    }

    public function value() : string {
        return $this->value;
    }

}

enum CacheGetStatus {
    case HIT;
    case MISS;
}

class CacheGetResponse {

    private string $value;
    private CacheGetStatus $status;

    public function __construct(_GetResponse $grpcGetResponse) {
        $this->value = $grpcGetResponse->getCacheBody();
        $ecacheResult = $grpcGetResponse->getResult();
        if ($ecacheResult == ECacheResult::Hit) {
            $this->status = CacheGetStatus::HIT;
        } else if ($ecacheResult == ECacheResult::Miss) {
            $this->status = CacheGetStatus::MISS;
        } else {
            throw new InternalServerError("CacheService returned an unexpected result: $ecacheResult");
        }
    }

    public function value() : string
    {
        return $this->value;
    }

    public function status() : string
    {
        return $this->status->name;
    }

}
