<?php
declare(strict_types=1);

namespace Momento\Utilities;

use Momento\Cache\Errors\InvalidArgumentError;
use Momento\Cache\Errors\InvalidArgumentException;

if (!function_exists('validateTtl')) {
    function validateTtl(int $ttlSeconds): void
    {
        if (!is_int($ttlSeconds) || $ttlSeconds < 0) {
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

if (!function_exists('validateCacheName')) {
    function validateCacheName(string $cacheName): void
    {
        if (isNullOrEmpty($cacheName)) {
            throw new InvalidArgumentError("Cache name must be a non-empty string");
        }
    }
}

if (!function_exists('validateKeys')) {
    function validateKeys(array $keys): void
    {
        if (empty($keys)) {
            throw new InvalidArgumentError("Keys must be a non-empty array");
        }
        foreach ($keys as $key) {
            if (isNullOrEmpty($key)) {
                throw new InvalidArgumentError("Keys must all be non-empty strings");
            }
        }
    }
}

if (!function_exists('validateListName')) {
    function validateListName(string $listName): void
    {
        if (isNullOrEmpty($listName)) {
            throw new InvalidArgumentError("List name must be a non-empty string");
        }
    }
}

if (!function_exists('validateDictionaryName')) {
    function validateDictionaryName(string $dictionaryName): void
    {
        if (isNullOrEmpty($dictionaryName)) {
            throw new InvalidArgumentError("Dictionary name must be a non-empty string");
        }
    }
}

if (!function_exists('validateFieldName')) {
    function validateFieldName(string $fieldName): void
    {
        if (isNullOrEmpty($fieldName)) {
            throw new InvalidArgumentError("Field name must be a non-empty string");
        }
    }
}

if (!function_exists('validateFields')) {
    function validateFields(array $fieldNames): void
    {
        if (empty($fieldNames)) {
            throw new InvalidArgumentError("Field names must be a non-empty array");
        }
        foreach ($fieldNames as $fieldName) {
            validateFieldName($fieldName);
        }
    }
}

if (!function_exists('validateValueName')) {
    function validateValueName(string $valueName): void
    {
        if (isNullOrEmpty($valueName)) {
            throw new InvalidArgumentError("Value name must be a non-empty string");
        }
    }
}

if (!function_exists('validateItems')) {
    function validateItems(array $items): void
    {
        if (empty($items)) {
            throw new InvalidArgumentError("Items must be a non-empty array");
        }
        foreach ($items as $item) {
            if (empty($item)) {
                throw new InvalidArgumentError("Items must be a non-empty array");
            }
        }

    }
}

if (!function_exists('validateFieldsKeys')) {
    function validateFieldsKeys(array $items): void
    {
        if (empty($items)) {
            throw new InvalidArgumentError("Items must be a non-empty array");
        }
        foreach ($items as $field => $value) {
            if (isNullOrEmpty($field) || isNullOrEmpty($value)) {
                throw new InvalidArgumentError("Each key and value must be a non-empty string");
            }
        }
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

if (!function_exists('validateRange')) {
    function validateRange(?int $beginIndex, ?int $count)
    {
        if (is_null($beginIndex) && is_null($count)) {
            return;
        }
        if (!is_null($beginIndex) xor !is_null($count)) {
            throw new InvalidArgumentError("Beginning index and count must be supplied together.");
        }
        if ($beginIndex < 0) {
            throw new InvalidArgumentError("Beginning index and count must be a positive integer.");
        }
        if ($count <= 0) {
            throw new InvalidArgumentError("Count must be greater than zero.");
        }
    }
}

if (!function_exists('validateSetName')) {
    function validateSetName(string $setName): void
    {
        if (isNullOrEmpty($setName)) {
            throw new InvalidArgumentError("Set name must be a non-empty string");
        }
    }
}

if (!function_exists('validateElement')) {
    function validateElement(string $element): void
    {
        if (isNullOrEmpty($element)) {
            throw new InvalidArgumentError("Element must be a non-empty string");
        }
    }
}

if (!function_exists('validatePsr16Key')) {
    function validatePsr16Key(string $key): void
    {
        $reserved = '/\{|\}|\(|\)|\/|\\\\|\@|\:/u';

        if (isNullOrEmpty($key)) {
            throw new InvalidArgumentException("Key must be a non-empty string");
        }

        if (preg_match($reserved, $key, $match) === 1) {
            throw new InvalidArgumentException("Key must not contain the character {$match[0]}");
        }
    }
}
