<?php
declare(strict_types=1);

namespace Momento\Utilities;

use Momento\Cache\Errors\InvalidArgumentError;
use Momento\Cache\Errors\InvalidArgumentException;

if (!function_exists('validateTtl')) {
    function validateTtl(int $ttlSeconds): void
    {
        if ($ttlSeconds < 0) {
            throw new InvalidArgumentError("TTL Seconds must be a non-negative integer");
        }
    }
}

if (!function_exists('isNullOrEmpty')) {
    function isNullOrEmpty(string $str = null): bool
    {
        return (is_null($str) || $str === "");
    }
}

if (!function_exists('validateNullOrEmpty')) {
    function validateNullOrEmpty(string $str, string $label) : void
    {
        if (isNullOrEmpty($str)) {
            throw new InvalidArgumentError("$label must be a non-empty string");
        }
    }
}

if (!function_exists('validateNullOrEmptyList')) {
    function validateNullOrEmptyList(array $strs, string $label) : void
    {
        if (empty($strs)) {
            throw new InvalidArgumentError("$label must be a non-empty array");
        }
        foreach ($strs as $str) {
            if (isNullOrEmpty($str)) {
                throw new InvalidArgumentError("$label must all be non-empty strings");
            }
        }
    }
}

if (!function_exists('validateCacheName')) {
    function validateCacheName(string $cacheName): void
    {
        validateNullOrEmpty($cacheName, "Cache name");
    }
}

if (!function_exists('validateKeys')) {
    function validateKeys(array $keys): void
    {
        if (empty($keys)) {
            throw new InvalidArgumentError("Keys must be a non-empty array");
        }
        foreach ($keys as $key) {
            // Explicitly test type of key. If someone passes us a ["a", "b", "c"] style list upstream
            // instead of a ["key"=>"val"] dict, the "keys" will be integers and we want to reject the payload.
            if (!is_string($key) || isNullOrEmpty($key)) {
                throw new InvalidArgumentError("Keys must all be non-empty strings");
            }
        }
    }
}

if (!function_exists('validateListName')) {
    function validateListName(string $listName): void
    {
        validateNullOrEmpty($listName, "List name");
    }
}

if (!function_exists('validateDictionaryName')) {
    function validateDictionaryName(string $dictionaryName): void
    {
        validateNullOrEmpty($dictionaryName, "Dictionary name");
    }
}

if (!function_exists('validateFieldName')) {
    function validateFieldName(string $fieldName): void
    {
        validateNullOrEmpty($fieldName, "Field name");
    }
}

if (!function_exists('validateFields')) {
    function validateFields(array $fieldNames): void
    {
        validateNullOrEmptyList($fieldNames, "Field names");
    }
}

if (!function_exists('validateValueName')) {
    function validateValueName(string $valueName): void
    {
        validateNullOrEmpty($valueName, "Value name");
    }
}

if (!function_exists('validateOperationTimeout')) {
    function validateOperationTimeout(?int $operationTimeout = null)
    {
        if ($operationTimeout === null) {
            return;
        }
        if ($operationTimeout <= 0) {
            throw new InvalidArgumentError("Request timeout must be greater than zero.");
        }
    }
}

if (!function_exists('validateTruncateSize')) {
    function validateTruncateSize(?int $truncateSize = null)
    {
        if ($truncateSize === null) {
            return;
        }
        if ($truncateSize <= 0) {
            throw new InvalidArgumentError("Truncate size must be greater than zero.");
        }
    }
}

if (!function_exists('validateSetName')) {
    function validateSetName(string $setName): void
    {
        validateNullOrEmpty($setName, "Set name");
    }
}

if (!function_exists('validateElement')) {
    function validateElement(string $element): void
    {
        validateNullOrEmpty($element, "Element");
    }
}

if (!function_exists('validatePsr16Key')) {
    function validatePsr16Key(string $key): void
    {
        $reserved = '/[{}()\/@:\\\]/u';

        if (isNullOrEmpty($key)) {
            throw new InvalidArgumentException("Key must be a non-empty string");
        }

        if (preg_match($reserved, $key, $match) === 1) {
            throw new InvalidArgumentException("Key must not contain the character {$match[0]}");
        }
    }
}
