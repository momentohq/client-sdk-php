<?php

namespace Momento\Cache;

use Control_client\_CreateCacheRequest;
use Control_client\_CreateCacheResponse;
use Control_client\_DeleteCacheRequest;
use Control_client\_ListCachesRequest;
use Grpc\UnaryCall;

class _ScsControlClient
{

    private _ControlGrpcManager $grpcManager;

    public function __construct(string $authToken, string $endpoint)
    {
        $this->grpcManager = new _ControlGrpcManager($authToken, $endpoint);
    }

    public function createCache(string $cacheName) : array
    {
        $request = new _CreateCacheRequest();
        $request->setCacheName($cacheName);
        $call = $this->grpcManager->client->CreateCache($request, [], ["timeout"=>10000000]);
        [$response, $status] = $call->wait();
        return [$response, $status];
    }

    public function deleteCache(string $cacheName) : array
    {
        $request = new _DeleteCacheRequest();
        $request->setCacheName($cacheName);
        $call = $this->grpcManager->client->DeleteCache($request, [], ["timeout"=>10000000]);
        [$response, $status] = $call->wait();
        return [$response, $status];
    }

    public function listCaches(?string $nextToken=null): array
    {
        $request = new _ListCachesRequest();
        $request->setNextToken($nextToken ? $nextToken : "");
        $call = $this->grpcManager->client->ListCaches($request, [], ["timeout"=>10000000]);
        [$response, $status] = $call->wait();
        return [$response, $status];
    }

}