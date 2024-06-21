<?php
declare(strict_types=1);

namespace Momento\Utilities;

use Grpc;
use Momento\Cache\Errors\AlreadyExistsError;
use Momento\Cache\Errors\AuthenticationError;
use Momento\Cache\Errors\BadRequestError;
use Momento\Cache\Errors\CacheNotFoundError;
use Momento\Cache\Errors\CancelledError;
use Momento\Cache\Errors\FailedPreconditionError;
use Momento\Cache\Errors\InternalServerError;
use Momento\Cache\Errors\InvalidArgumentError;
use Momento\Cache\Errors\ItemNotFoundError;
use Momento\Cache\Errors\LimitExceededError;
use Momento\Cache\Errors\NotFoundError;
use Momento\Cache\Errors\PermissionError;
use Momento\Cache\Errors\SdkError;
use Momento\Cache\Errors\ServerUnavailableError;
use Momento\Cache\Errors\StoreNotFoundError;
use Momento\Cache\Errors\TimeoutError;
use Momento\Cache\Errors\UnknownError;
use Momento\Cache\Errors\UnknownServiceError;

class _ErrorConverter
{

    public static array $rpcToError = [
        Grpc\STATUS_INVALID_ARGUMENT => InvalidArgumentError::class,
        Grpc\STATUS_OUT_OF_RANGE => BadRequestError::class,
        Grpc\STATUS_UNIMPLEMENTED => BadRequestError::class,
        Grpc\STATUS_FAILED_PRECONDITION => FailedPreconditionError::class,
        Grpc\STATUS_CANCELLED => CancelledError::class,
        Grpc\STATUS_DEADLINE_EXCEEDED => TimeoutError::class,
        Grpc\STATUS_PERMISSION_DENIED => PermissionError::class,
        Grpc\STATUS_UNAUTHENTICATED => AuthenticationError::class,
        Grpc\STATUS_RESOURCE_EXHAUSTED => LimitExceededError::class,
        Grpc\STATUS_ALREADY_EXISTS => AlreadyExistsError::class,
        Grpc\STATUS_NOT_FOUND => NotFoundError::class,
        Grpc\STATUS_UNKNOWN => UnknownServiceError::class,
        Grpc\STATUS_ABORTED => InternalServerError::class,
        Grpc\STATUS_INTERNAL => InternalServerError::class,
        Grpc\STATUS_UNAVAILABLE => ServerUnavailableError::class,
        Grpc\STATUS_DATA_LOSS => InternalServerError::class
    ];

    public static function convert($grpcStatus, ?array $metadata = null): SdkError
    {
        $status = $grpcStatus->code;
        $details = $grpcStatus->details;
        if (array_key_exists($status, self::$rpcToError)) {
            // If the status code is STATUS_NOT_FOUND, we need to check the details to determine if it was a
            // cache, store, or item that was not found.
            if ($status === Grpc\STATUS_NOT_FOUND) {
                if (!array_key_exists("err", $grpcStatus->metadata)) {
                    $class = CacheNotFoundError::class;
                } elseif ($grpcStatus->metadata["err"][0] == "store_not_found") {
                    $class = StoreNotFoundError::class;
                } elseif ($grpcStatus->metadata["err"][0] == "element_not_found") {
                    $class = ItemNotFoundError::class;
                } else {
                    $class = CacheNotFoundError::class;
                }
            } else {
                $class = self::$rpcToError[$status];
            }
            return new $class($details, $status, null, $metadata);
        }

        return new UnknownError(
            "CacheService failed due to an internal error", 0, null, $metadata
        );
    }
}
