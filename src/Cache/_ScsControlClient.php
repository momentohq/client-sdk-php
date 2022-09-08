<?php

namespace Momento\Cache;

use Control_client\_CreateCacheRequest;
use Control_client\_CreateCacheResponse;

class _ScsControlClient
{

    private _ControlGrpcManager $controlGrpcManager;

    public function __construct(string $authToken, string $endpoint)
    {
        $this->controlGrpcManager = new _ControlGrpcManager($authToken, $endpoint);
    }

    public function createCache(string $cacheName) : _CreateCacheResponse {
        $request = new _CreateCacheRequest();
        $request->setCacheName($cacheName);
        $response = $this->controlGrpcManager->client->CreateCache($request, [], ["timeout"=>1000000]);
        var_dump($response);
        return new _CreateCacheResponse();
    }

}