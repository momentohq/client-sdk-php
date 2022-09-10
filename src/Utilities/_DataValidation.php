<?php

namespace Momento\Utilities;

use InvalidArgumentException;

if (!function_exists('validateTtl')) {
    function validateTtl($ttlSeconds) : void
    {
        if (!is_int($ttlSeconds) || $ttlSeconds < 0) {
            throw new InvalidArgumentException("TTL Seconds must be a non-negative integer");
        }
    }
}

if (!function_exists('validateCacheName')) {
    function validateCacheName($cacheName) : void
    {
        if (!$cacheName || !is_string($cacheName)) {
            throw new InvalidArgumentException("Cache name must be a non-empty string");
        }
    }
}
