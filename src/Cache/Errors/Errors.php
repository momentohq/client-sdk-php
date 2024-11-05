<?php
declare(strict_types=1);

namespace Momento\Cache\Errors;

abstract class MomentoErrorCode
{
    /**
     * Invalid argument passed to Momento client
     */
    public const INVALID_ARGUMENT_ERROR = "INVALID_ARGUMENT_ERROR";
    /**
     * Service returned an unknown response
     */
    public const UNKNOWN_SERVICE_ERROR = "UNKNOWN_SERVICE_ERROR";
    /**
     * Cache with specified name already exists
     */
    public const ALREADY_EXISTS_ERROR = "ALREADY_EXISTS_ERROR";
    /**
     * Cache with specified name doesn't exist
     * @deprecated Use CacheNotFoundError instead
     */
    public const NOT_FOUND_ERROR = "NOT_FOUND_ERROR";
    /**
     * Cache with specified name doesn't exist
     */
    public const CACHE_NOT_FOUND_ERROR = "NOT_FOUND_ERROR";
    /**
     * Store with specified name doesn't exist
     */
    public const STORE_NOT_FOUND_ERROR = "STORE_NOT_FOUND_ERROR";
    /**
     * Item with specified name doesn't exist
     */
    public const ITEM_NOT_FOUND_ERROR = "ITEM_NOT_FOUND_ERROR";
    /**
     * An unexpected error occurred while trying to fulfill the request
     */
    public const INTERNAL_SERVER_ERROR = "INTERNAL_SERVER_ERROR";
    /**
     * Insufficient permissions to perform operation
     */
    public const PERMISSION_ERROR = "PERMISSION_ERROR";
    /**
     * Invalid authentication credentials to connect to cache service
     */
    public const AUTHENTICATION_ERROR = "AUTHENTICATION_ERROR";
    /**
     * Request was cancelled by the server
     */
    public const CANCELLED_ERROR = "CANCELLED_ERROR";
    /**
     * Request rate exceeded the limits for the account
     */
    public const LIMIT_EXCEEDED_ERROR = "LIMIT_EXCEEDED_ERROR";
    /**
     * Request was invalid
     */
    public const BAD_REQUEST_ERROR = "BAD_REQUEST_ERROR";
    /**
     * Client's configured timeout was exceeded
     */
    public const TIMEOUT_ERROR = "TIMEOUT_ERROR";
    /**
     * Server was unable to handle the request
     */
    public const SERVER_UNAVAILABLE = "SERVER_UNAVAILABLE";
    /**
     * A client resource (most likely memory) was exhausted
     */
    public const CLIENT_RESOURCE_EXHAUSTED = "CLIENT_RESOURCE_EXHAUSTED";
    /**
     * System is not in a state required for the operation's execution
     */
    public const FAILED_PRECONDITION_ERROR = "FAILED_PRECONDITION_ERROR";
    /**
     * Unknown error has occurred
     */
    public const UNKNOWN_ERROR = "UNKNOWN_ERROR";
}

/**
 * Captures low-level information about an error, at the gRPC level.  Hopefully
 * this is only needed in rare cases, by Momento engineers, for debugging.
 */
class MomentoGrpcErrorDetails
{
    /**
     * @var int The gRPC status code of the error response
     */
    public int $code;
    /**
     * @var string Detailed information about the error
     */
    public string $details;
    /**
     * @var mixed|null Headers and other information about the error response
     */
    public $metadata;

    public function __construct(int $code, string $details, $metadata = null)
    {
        $this->code = $code;
        $this->details = $details;
        $this->metadata = $metadata;
    }
}

/**
 *  Container for low-level error information, including details from the transport layer.
 */
class MomentoErrorTransportDetails
{
    public MomentoGrpcErrorDetails $grpc;

    public function __construct(MomentoGrpcErrorDetails $grpc)
    {
        $this->grpc = $grpc;
    }
}

/**
 * Base class for all Momento SDK errors
 */
abstract class SdkError extends \Exception
{
    /**
     * @var string error code corresponding to one of the values of MomentoErrorCode
     */
    public string $errorCode;
    /**
     * @var MomentoErrorTransportDetails Low-level error details, from the transport layer.  Hopefully only needed
     * in rare cases, by Momento engineers, for debugging.
     */
    public MomentoErrorTransportDetails $transportDetails;
    /**
     * @var string Prefix with basic information about the error class; this will be appended
     * with specific information about the individual error instance at runtime.
     */
    public string $messageWrapper;

    public function __construct(
        string $message, int $code = 0, ?\Throwable $previous = null, $metadata = null
    )
    {
        parent::__construct($message, $code, $previous);
        $this->setTransportDetails($code, $message, $metadata);
    }

    public function setTransportDetails(int $code, string $details, $metadata)
    {
        $grpcDetails = new MomentoGrpcErrorDetails($code, $details, $metadata);
        $this->transportDetails = new MomentoErrorTransportDetails($grpcDetails);
    }
}

/**
 * Cache with specified name already exists
 */
class AlreadyExistsError extends SdkError
{
    public string $errorCode = MomentoErrorCode::ALREADY_EXISTS_ERROR;
    public string $messageWrapper = 'A cache with the specified name already exists.  To resolve this error, either delete the existing cache and make a new one, or use a different name';
}

/**
 * Invalid authentication credentials to connect to cache service
 */
class AuthenticationError extends SdkError
{
    public string $errorCode = MomentoErrorCode::AUTHENTICATION_ERROR;
    public string $messageWrapper = 'Invalid authentication credentials to connect to cache service';
}

/**
 * Request was invalid
 */
class BadRequestError extends SdkError
{
    public string $errorCode = MomentoErrorCode::BAD_REQUEST_ERROR;
    public string $messageWrapper = 'The request was invalid; please contact us at support@momentohq.com';
}

/**
 * Request was cancelled by the server
 */
class CancelledError extends SdkError
{
    public string $errorCode = MomentoErrorCode::CANCELLED_ERROR;
    public string $messageWrapper = 'The request was cancelled by the server; please contact us at support@momentohq.com';
}

/**
 * System is not in a state required for the operation's execution
 */
class FailedPreconditionError extends SdkError
{
    public string $errorCode = MomentoErrorCode::FAILED_PRECONDITION_ERROR;
    public string $messageWrapper = 'System is not in a state required for the operation\'s execution';
}

/**
 * An unexpected error occurred while trying to fulfill the request
 */
class InternalServerError extends SdkError
{
    public string $errorCode = MomentoErrorCode::INTERNAL_SERVER_ERROR;
    public string $messageWrapper = 'An unexpected error occurred while trying to fulfill the request; please contact us at support@momentohq.com';
}

/**
 * Invalid argument passed to Momento client
 */
class InvalidArgumentError extends SdkError
{
    public string $errorCode = MomentoErrorCode::INVALID_ARGUMENT_ERROR;
    public string $messageWrapper = 'Invalid argument passed to Momento client';
}

/**
 * Occurs when request rate, bandwidth, or object size exceeded the limits for the account.
 */
class LimitExceededError extends SdkError
{
    public string $errorCode = MomentoErrorCode::LIMIT_EXCEEDED_ERROR;
    public string $messageWrapper = LimitExceededMessageWrapper::UNKNOWN_LIMIT_EXCEEDED;

    public function __construct(
        string $message, int $code = 0, ?\Throwable $previous = null, ?array $metadata = null
    )
    {
        parent::__construct($message, $code, $previous);
        $this->setMessageWrapper($message, $metadata);
    }

    private function setMessageWrapper(string $message, ?array $metadata = null) {
        // If provided, we use the `err` metadata value to determine the most
        // appropriate error message to set.
        if (array_key_exists("err", $metadata)) {
            $errCause = $metadata["err"][0];
            if (!is_null($errCause)) {
                $this->messageWrapper = LimitExceededMessageWrapper::$errorCauseToLimitExceededMessage[$errCause];
                return;
            }
        }

        // If `err` metadata is unavailable, try to use the error details field
        // to set an appropriate error message.
        $lowerCasedMessage = strtolower($message);
        if (str_contains($lowerCasedMessage, "subscribers")) {
            $this->messageWrapper = LimitExceededMessageWrapper::TOPIC_SUBSCRIPTIONS_LIMIT_EXCEEDED;
        } elseif (str_contains($lowerCasedMessage, "operations")) {
            $this->messageWrapper = LimitExceededMessageWrapper::OPERATIONS_RATE_LIMIT_EXCEEDED;
        } elseif (str_contains($lowerCasedMessage, "throughput")) {
            $this->messageWrapper = LimitExceededMessageWrapper::THROUGHPUT_RATE_LIMIT_EXCEEDED;
        } elseif (str_contains($lowerCasedMessage, "request limit")) {
            $this->messageWrapper = LimitExceededMessageWrapper::REQUEST_SIZE_LIMIT_EXCEEDED;
        } elseif (str_contains($lowerCasedMessage, "item size")) {
            $this->messageWrapper = LimitExceededMessageWrapper::ITEM_SIZE_LIMIT_EXCEEDED;
        } elseif (str_contains($lowerCasedMessage, "element size")) {
            $this->messageWrapper = LimitExceededMessageWrapper::ELEMENT_SIZE_LIMIT_EXCEEDED;
        } else {
            // If all else fails, set a generic "limit exceeded" message
            $this->messageWrapper = LimitExceededMessageWrapper::UNKNOWN_LIMIT_EXCEEDED;
        }
    }
}

abstract class LimitExceededMessageWrapper {
    public const TOPIC_SUBSCRIPTIONS_LIMIT_EXCEEDED = "Topic subscriptions limit exceeded for this account";
    public const OPERATIONS_RATE_LIMIT_EXCEEDED = "Request rate limit exceeded for this account";
    public const THROUGHPUT_RATE_LIMIT_EXCEEDED = "Bandwidth limit exceeded for this account";
    public const REQUEST_SIZE_LIMIT_EXCEEDED = "Request size limit exceeded for this account";
    public const ITEM_SIZE_LIMIT_EXCEEDED = "Item size limit exceeded for this account";
    public const ELEMENT_SIZE_LIMIT_EXCEEDED = "Element size limit exceeded for this account";
    public const UNKNOWN_LIMIT_EXCEEDED = "Limit exceeded for this account";

    public static array $errorCauseToLimitExceededMessage = [
        "topic_subscriptions_limit_exceeded" => LimitExceededMessageWrapper::TOPIC_SUBSCRIPTIONS_LIMIT_EXCEEDED,
        "operations_rate_limit_exceeded" => LimitExceededMessageWrapper::OPERATIONS_RATE_LIMIT_EXCEEDED,
        "throughput_rate_limit_exceeded" => LimitExceededMessageWrapper::THROUGHPUT_RATE_LIMIT_EXCEEDED,
        "request_size_limit_exceeded" => LimitExceededMessageWrapper::REQUEST_SIZE_LIMIT_EXCEEDED,
        "item_size_limit_exceeded" => LimitExceededMessageWrapper::ITEM_SIZE_LIMIT_EXCEEDED,
        "element_size_limit_exceeded" => LimitExceededMessageWrapper::ELEMENT_SIZE_LIMIT_EXCEEDED,
    ];
}

/**
 * Cache with specified name doesn't exist
 * @deprecated Use CacheNotFoundError instead
 */
class NotFoundError extends SdkError
{
    public string $errorCode = MomentoErrorCode::NOT_FOUND_ERROR;
    public string $messageWrapper = 'A cache with the specified name does not exist.  To resolve this error, make sure you have created the cache before attempting to use it';
}

/**
 * Cache with specified name doesn't exist
 */
class CacheNotFoundError extends SdkError
{
    public string $errorCode = MomentoErrorCode::CACHE_NOT_FOUND_ERROR;
    public string $messageWrapper = 'A cache with the specified name does not exist.  To resolve this error, make sure you have created the cache before attempting to use it';
}

/**
 * Store with specified name doesn't exist
 */
class StoreNotFoundError extends SdkError
{
    public string $errorCode = MomentoErrorCode::STORE_NOT_FOUND_ERROR;
    public string $messageWrapper = 'A store with the specified name does not exist.  To resolve this error, make sure you have created the store before attempting to use it';
}

/**
 * Item with specified name doesn't exist
 */
class ItemNotFoundError extends SdkError
{
    public string $errorCode = MomentoErrorCode::ITEM_NOT_FOUND_ERROR;
    public string $messageWrapper = 'An item with the specified name does not exist.  To resolve this error, make sure you have created the item before attempting to use it';
}

/**
 * Insufficient permissions to perform operation
 */
class PermissionError extends SdkError
{
    public string $errorCode = MomentoErrorCode::PERMISSION_ERROR;
    public string $messageWrapper = 'Insufficient permissions to perform an operation on a cache';
}

/**
 * Server was unable to handle the request
 */
class ServerUnavailableError extends SdkError
{
    public string $errorCode = MomentoErrorCode::SERVER_UNAVAILABLE;
    public string $messageWrapper = 'The server was unable to handle the request; consider retrying.  If the error persists, please contact us at support@momentohq.com';
}

/**
 * Client's configured timeout was exceeded
 */
class TimeoutError extends SdkError
{
    public string $errorCode = MomentoErrorCode::TIMEOUT_ERROR;
    public string $messageWrapper = 'The client\'s configured timeout was exceeded; you may need to use a Configuration with more lenient timeouts';
}

/**
 * Unknown error has occurred
 */
class UnknownError extends SdkError
{
    public string $errorCode = MomentoErrorCode::UNKNOWN_ERROR;
    public string $messageWrapper = 'Unknown error has occurred';
}

/**
 * Service returned an unknown response
 */
class UnknownServiceError extends SdkError
{
    public string $errorCode = MomentoErrorCode::UNKNOWN_SERVICE_ERROR;
    public string $messageWrapper = 'Service returned an unknown response; please contact us at support@momentohq.com';
}

// PSR-16 Exceptions

class CacheException extends \RuntimeException implements \Psr\SimpleCache\CacheException
{
}

class InvalidArgumentException extends \InvalidArgumentException implements \Psr\SimpleCache\InvalidArgumentException
{
}

class NotImplementedException extends CacheException
{
}
