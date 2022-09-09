<?php

namespace Momento\Cache;

use Control_client\_CreateCacheRequest;
use Control_client\_CreateCacheResponse;
use Grpc\UnaryCall;

class _ScsControlClient
{

    private _ControlGrpcManager $controlGrpcManager;

    public function __construct(string $authToken, string $endpoint)
    {
        $this->controlGrpcManager = new _ControlGrpcManager($authToken, $endpoint);
    }

    public function createCache(string $cacheName) : UnaryCall {
        $request = new _CreateCacheRequest();
        $request->setCacheName($cacheName);
        return $this->controlGrpcManager->client->CreateCache($request, [], ["timeout"=>10000000]);
    }

}