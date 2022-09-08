<?php

namespace Momento\Cache;

//use Cache_client\ScsClient;
//use Grpc\ChannelCredentials;

use Control_client\_CreateCacheResponse;
use Momento\Cache\_ScsControlClient;

class CacheClient
{

    private _ScsControlClient $controlClient;

    /**
     * @param string $authToken: momento JWT
     * @param int $defaultTtlSeconds: Default Time to Live for the item in Cache
     */
    function __construct(string $authToken, int $defaultTtlSeconds)
    {
        list($header, $payload, $signature) = explode (".", $authToken);
        $payload = base64_decode($payload);
        $payload = json_decode($payload);
        $this->controlClient = new _ScsControlClient($authToken, $payload->cp);
    }

    function foo() : string
    {
        $this->createCache();
        return 'Hello';
    }

    function createCache() : void
    {
        $response = $this->controlClient->createCache("phptest");
        print $response->serializeToJsonString();
    }
}
