<?php
declare(strict_types=1);

namespace Momento\Utilities;

use Momento\Cache\Errors\InvalidArgumentError;
use Momento\Cache\Errors\InvalidArgumentException;

if (!function_exists('validateTtl')) {
    function validateTtl($ttlSeconds): void
    {
        if (!(is_int($ttlSeconds) || is_float($ttlSeconds)) || $ttlSeconds < 0) {
            throw new InvalidArgumentError("TTL Seconds must be a non-negative number");
        }
    }
}

if (!function_exists('isNullOrEmpty')) {
    function isNullOrEmpty($value): bool
    {
        // if the value is not null, check if the string value is empty. This covers strings that have been
        // automatically converted into integers. Scalars cover int, float, string, and bool.
        return is_null($value) || (is_scalar($value) && strval($value) === "");
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
            if (isNullOrEmpty($key)) {
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

if (!function_exists('validateElements')) {
    function validateElements(array $elements): void
    {
        validateNullOrEmptyList($elements, "Elements");
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

if (!function_exists('validateSortedSetName')) {
    function validateSortedSetName(string $sortedSetName): void
    {
        validateNullOrEmpty($sortedSetName, "Sorted set name");
    }
}

if (!function_exists('validateSortedSetRanks')) {
    function validateSortedSetRanks(?int $startRank, ?int $endRank): void
    {
        if (is_null($startRank) || is_null($endRank)) {
            return;
        }
        if ($startRank > 0 && $endRank > 0 && $startRank > $endRank) {
            throw new InvalidArgumentError("startRank must be less than endRank");
        }
        if ($startRank < 0 && $endRank < 0 && $startRank >= $endRank) {
            throw new InvalidArgumentError("negative start rank must be less than negative end rank");
        }
    }
}

if (!function_exists('validateSortedSetElements')) {
    function validateSortedSetElements(array $elements): void
    {
        foreach ($elements as $value => $score) {
            validateSortedSetScore($score);

            validateNullOrEmpty($value, "Sorted set value");
        }
    }
}

if (!function_exists('validateSortedSetValues')) {
    function validateSortedSetValues(array $values): void
    {
        if (empty($values)) {
            throw new InvalidArgumentError("sorted set values must be a non-empty array");
        }
        foreach ($values as $value) {
            if (isNullOrEmpty($value)) {
                throw new InvalidArgumentError("sorted set values must all be non-empty strings");
            }
        }
    }
}

if (!function_exists('validateSortedSetScore')) {
    function validateSortedSetScore($score): void
    {
        if (is_null($score) || (!is_int($score) && !is_float($score))) {
            throw new InvalidArgumentError("sorted set score must be an int or float");
        }
    }
}

if (!function_exists('validateSortedSetScores')) {
    function validateSortedSetScores($minScore, $maxScore): void
    {
        if (!is_null($minScore)) {
            validateSortedSetScore($minScore);
        }
        if (!is_null($maxScore)) {
            validateSortedSetScore($maxScore);
        }

        if (!is_null($minScore) && !is_null($maxScore)) {
            if ($minScore > $maxScore) {
                throw new InvalidArgumentError("minScore must be less than or equal to maxScore");
            }
        }
    }
}

if (!function_exists('validateSortedSetOrder')) {
    function validateSortedSetOrder(int $order): void
    {
        if ($order != SORT_ASC && $order != SORT_DESC) {
            throw new InvalidArgumentError("Sorted set sort order must be SORT_ASC or SORT_DESC");
        }
    }
}
