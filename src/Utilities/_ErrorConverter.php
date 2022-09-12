<?php

namespace Momento\Utilities;
use Grpc;
use Momento\Cache\Errors\InternalServerError;

class _ErrorConverter {

    public static array $rpcToError = [
        Grpc\STATUS_INVALID_ARGUMENT => \Momento\Cache\Errors\BadRequestError::class,
        Grpc\STATUS_OUT_OF_RANGE => \Momento\Cache\Errors\BadRequestError::class,
        Grpc\STATUS_UNIMPLEMENTED => \Momento\Cache\Errors\BadRequestError::class,
        Grpc\STATUS_FAILED_PRECONDITION => \Momento\Cache\Errors\BadRequestError::class,
        Grpc\STATUS_CANCELLED => \Momento\Cache\Errors\CancelledError::class,
        Grpc\STATUS_DEADLINE_EXCEEDED => \Momento\Cache\Errors\TimeoutError::class,
        Grpc\STATUS_PERMISSION_DENIED => \Momento\Cache\Errors\PermissionError::class,
        Grpc\STATUS_UNAUTHENTICATED => \Momento\Cache\Errors\AuthenticationError::class,
        Grpc\STATUS_RESOURCE_EXHAUSTED => \Momento\Cache\Errors\LimitExceededError::class,
        Grpc\STATUS_ALREADY_EXISTS => \Momento\Cache\Errors\AlreadyExistsError::class,
        Grpc\STATUS_NOT_FOUND => \Momento\Cache\Errors\NotFoundError::class,
        Grpc\STATUS_UNKNOWN => \Momento\Cache\Errors\InternalServerError::class,
        Grpc\STATUS_ABORTED => \Momento\Cache\Errors\InternalServerError::class,
        Grpc\STATUS_INTERNAL => \Momento\Cache\Errors\InternalServerError::class,
        Grpc\STATUS_UNAVAILABLE => \Momento\Cache\Errors\InternalServerError::class,
        Grpc\STATUS_DATA_LOSS => \Momento\Cache\Errors\InternalServerError::class
    ];

    public static function convert(int $status, string $details) : \Exception
    {
        if (array_key_exists($status, self::$rpcToError)) {
            $class = self::$rpcToError[$status];
            return new $class($details);
        }
        throw new \Momento\Cache\Errors\InternalServerError("CacheService failed an internal error");
    }
}