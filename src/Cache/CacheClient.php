<?php

namespace Momento\Cache;

use Cache_client\ScsClient;

class CacheClient
{

    /**
     * @param string $authToken: momento JWT
     * @param int $defaultTtlSeconds: Default Time to Live for the item in Cache
     */
    function __construct(string $authToken, int $defaultTtlSeconds)
    {
        $foo = new ScsClient("https://foo.com", []);
        return 'Hello';
    }
}