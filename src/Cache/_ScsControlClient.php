<?php

namespace Momento\Cache;

use Control_client\_CreateCacheRequest;
use Control_client\_DeleteCacheRequest;
use Control_client\_ListCachesRequest;
use Momento\Cache\CacheOperationTypes\CreateCacheResponse;
use Momento\Cache\CacheOperationTypes\DeleteCacheResponse;
use Momento\Cache\CacheOperationTypes\ListCachesResponse;
use Momento\Utilities\_ErrorConverter;
use function Momento\Utilities\validateCacheName;

class _ScsControlClient
{

    private _ControlGrpcManager $grpcManager;

    public function __construct(string $authToken, string $endpoint)
    {
        $this->grpcManager = new _ControlGrpcManager($authToken, $endpoint);
    }

    private function checkCallStatus(object $status) : void {
        if ($status->code !== 0) {
            throw _ErrorConverter::convert($status->code, $status->details);
        }
    }

    public function createCache(string $cacheName) : CreateCacheResponse
    {
        validateCacheName($cacheName);
        try {
            $request = new _CreateCacheRequest();
            $request->setCacheName($cacheName);
            $call = $this->grpcManager->client->CreateCache($request);
            [$response, $status] = $call->wait();
        } catch (\Exception $e) {
            // TODO: error converter and exception-less handling
            throw $e;
        }
        $this->checkCallStatus($status);
        return new CreateCacheResponse($response);
    }

    public function deleteCache(string $cacheName) : DeleteCacheResponse
    {
        validateCacheName($cacheName);
        try {
            $request = new _DeleteCacheRequest();
            $request->setCacheName($cacheName);
            $call = $this->grpcManager->client->DeleteCache($request);
            [$response, $status] = $call->wait();
        } catch (\Exception $e) {
            // TODO: error converter and exception-less handling
            throw $e;
        }
        $this->checkCallStatus($status);
        return new DeleteCacheResponse($response);
    }

    public function listCaches(?string $nextToken=null): ListCachesResponse
    {
        try {
            $request = new _ListCachesRequest();
            $request->setNextToken($nextToken ? $nextToken : "");
            $call = $this->grpcManager->client->ListCaches($request);
            [$response, $status] = $call->wait();
        } catch (\Exception $e) {
            // TODO: error converter and exception-less handling
            throw $e;
        }
        $this->checkCallStatus($status);
        return new ListCachesResponse($response);
    }

}
