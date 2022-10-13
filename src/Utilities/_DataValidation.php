<?php

namespace Momento\Utilities;

use Momento\Cache\Errors\InvalidArgumentError;

if (!function_exists('validateTtl'))
{
    function validateTtl(int $ttlSeconds) : void
    {
        if (!is_int($ttlSeconds) || $ttlSeconds < 0)
        {
            throw new InvalidArgumentError("TTL Seconds must be a non-negative integer");
        }
    }
}

if (!function_exists('isNullOrEmpty'))
{
    function isNullOrEmpty(string $str=null) : bool
    {
        return (is_null($str) || $str === "");
    }
}

if (!function_exists('validateCacheName'))
{
    function validateCacheName(string $cacheName) : void
    {
        if (isNullOrEmpty($cacheName))
        {
            throw new InvalidArgumentError("Cache name must be a non-empty string");
        }
    }
}

if (!function_exists('validateListName'))
{
    function validateListName(string $listName) : void
    {
        if (isNullOrEmpty($listName))
        {
            throw new InvalidArgumentError("List name must be a non-empty string");
        }
    }
}

if (!function_exists('validateOperationTimeout'))
{
    function validateOperationTimeout(?int $operationTimeout=null)
    {
        if ($operationTimeout === null)
        {
            return;
        }
        if ($operationTimeout <= 0)
        {
            throw new InvalidArgumentError("Request timeout must be greater than zero.");
        }
    }
}
