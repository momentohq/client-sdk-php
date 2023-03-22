<?php
declare(strict_types=1);

namespace Momento\Config\Retry;

use Grpc;

class DefaultEligibilityStrategy implements IEligibilityStrategy {

    private array $retryableStatusCodes = [
        Grpc\STATUS_UNAVAILABLE=>true,
        Grpc\STATUS_INTERNAL=>true,
    ];

    private array $retryableRequestMethods = [
        "/cache_client.Scs/Set"=>true,
        "/cache_client.Scs/Get"=>true,
        "/cache_client.Scs/Delete"=>true,
        // not idempotent "/cache_client.Scs/Increment"
        "/cache_client.Scs/DictionarySet"=>true,
        // not idempotent: "/cache_client.Scs/DictionaryIncrement",
        "/cache_client.Scs/DictionaryGet"=>true,
        "/cache_client.Scs/DictionaryFetch"=>true,
        "/cache_client.Scs/DictionaryDelete"=>true,
        "/cache_client.Scs/SetUnion"=>true,
        "/cache_client.Scs/SetDifference"=>true,
        "/cache_client.Scs/SetFetch"=>true,
        // not idempotent: "/cache_client.Scs/SetIfNotExists"
        // not idempotent: "/cache_client.Scs/ListPushFront",
        // not idempotent: "/cache_client.Scs/ListPushBack",
        // not idempotent: "/cache_client.Scs/ListPopFront",
        // not idempotent: "/cache_client.Scs/ListPopBack",
        "/cache_client.Scs/ListFetch"=>true,
        // Warning: in the future, this may not be idempotent
        // Currently it supports removing all occurrences of a value.
        // In the future, we may also add "the first/last N occurrences of a value".
        // In the latter case it is not idempotent.
        "/cache_client.Scs/ListRemove"=>true,
        "/cache_client.Scs/ListLength"=>true,
        // not idempotent: "/cache_client.Scs/ListConcatenateFront",
        // not idempotent: "/cache_client.Scs/ListConcatenateBack"
    ];

    public function isEligibleForRetry(int $grpcCode, string $method, int $attemptNumber): bool {
        return (
            (
                array_key_exists($grpcCode, $this->retryableStatusCodes)
                && $this->retryableStatusCodes[$grpcCode] === true
            ) && (
                array_key_exists($method, $this->retryableRequestMethods)
                && $this->retryableRequestMethods[$method] === true
            )
        );
    }

}
