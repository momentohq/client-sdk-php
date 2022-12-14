<?php
declare(strict_types=1);

namespace Momento\Cache\Errors;

use Grpc\Status;

abstract class MomentoErrorCode
{
    /// Invalid argument passed to Momento client
    public const INVALID_ARGUMENT_ERROR = "INVALID_ARGUMENT_ERROR";
    /// Service returned an unknown response
    public const UNKNOWN_SERVICE_ERROR = "UNKNOWN_SERVICE_ERROR";
    /// Cache with specified name already exists
    public const ALREADY_EXISTS_ERROR = "ALREADY_EXISTS_ERROR";
    /// Cache with specified name doesn't exist
    public const NOT_FOUND_ERROR = "NOT_FOUND_ERROR";
    /// An unexpected error occurred while trying to fulfill the request
    public const INTERNAL_SERVER_ERROR = "INTERNAL_SERVER_ERROR";
    /// Insufficient permissions to perform operation
    public const PERMISSION_ERROR = "PERMISSION_ERROR";
    /// Invalid authentication credentials to connect to cache service
    public const AUTHENTICATION_ERROR = "AUTHENTICATION_ERROR";
    /// Request was cancelled by the server
    public const CANCELLED_ERROR = "CANCELLED_ERROR";
    /// Request rate exceeded the limits for the account
    public const LIMIT_EXCEEDED_ERROR = "LIMIT_EXCEEDED_ERROR";
    /// Request was invalid
    public const BAD_REQUEST_ERROR = "BAD_REQUEST_ERROR";
    /// Client's configured timeout was exceeded
    public const TIMEOUT_ERROR = "TIMEOUT_ERROR";
    /// Server was unable to handle the request
    public const SERVER_UNAVAILABLE = "SERVER_UNAVAILABLE";
    /// A client resource (most likely memory) was exhausted
    public const CLIENT_RESOURCE_EXHAUSTED = "CLIENT_RESOURCE_EXHAUSTED";
    /// System is not in a state required for the operation's execution
    public const FAILED_PRECONDITION_ERROR = "FAILED_PRECONDITION_ERROR";
    /// Unknown error has occurred
    public const UNKNOWN_ERROR = "UNKNOWN_ERROR";
}

class MomentoGrpcErrorDetails
{
    public int $code;
    public string $details;
    public mixed $metadata;

    public function __construct(int $code, string $details, mixed $metadata = null)
    {
        $this->code = $code;
        $this->details = $details;
        $this->metadata = $metadata;
    }
}

class MomentoErrorTransportDetails
{
    public MomentoGrpcErrorDetails $grpc;

    public function __construct(MomentoGrpcErrorDetails $grpc)
    {
        $this->grpc = $grpc;
    }
}

abstract class SdkError extends \Exception
{
    public string $errorCode;
    public MomentoErrorTransportDetails $transportDetails;
    public string $messageWrapper;

    public function __construct(
        string $message, int $code = 0, ?\Throwable $previous = null, mixed $metadata = null
    )
    {
        parent::__construct($message, $code, $previous);
        $this->setTransportDetails($code, $message, $metadata);
    }

    public function setTransportDetails(int $code, string $details, mixed $metadata)
    {
        $grpcDetails = new MomentoGrpcErrorDetails($code, $details, $metadata);
        $this->transportDetails = new MomentoErrorTransportDetails($grpcDetails);
    }
}

class AlreadyExistsError extends SdkError
{
    public string $errorCode = MomentoErrorCode::ALREADY_EXISTS_ERROR;
    public string $messageWrapper = 'A cache with the specified name already exists.  To resolve this error, either delete the existing cache and make a new one, or use a different name';
}

class AuthenticationError extends SdkError
{
    public string $errorCode = MomentoErrorCode::AUTHENTICATION_ERROR;
    public string $messageWrapper = 'Invalid authentication credentials to connect to cache service';
}

class BadRequestError extends SdkError
{
    public string $errorCode = MomentoErrorCode::BAD_REQUEST_ERROR;
    public string $messageWrapper = 'The request was invalid; please contact us at support@momentohq.com';
}

class CancelledError extends SdkError
{
    public string $errorCode = MomentoErrorCode::CANCELLED_ERROR;
    public string $messageWrapper = 'The request was cancelled by the server; please contact us at support@momentohq.com';
}

class FailedPreconditionError extends SdkError
{
    public string $errorCode = MomentoErrorCode::FAILED_PRECONDITION_ERROR;
    public string $messageWrapper = 'System is not in a state required for the operation\'s execution';
}

class InternalServerError extends SdkError
{
    public string $errorCode = MomentoErrorCode::INTERNAL_SERVER_ERROR;
    public string $messageWrapper = 'An unexpected error occurred while trying to fulfill the request; please contact us at support@momentohq.com';
}

class InvalidArgumentError extends SdkError
{
    public string $errorCode = MomentoErrorCode::INVALID_ARGUMENT_ERROR;
    public string $messageWrapper = 'Invalid argument passed to Momento client';
}

class LimitExceededError extends SdkError
{
    public string $errorCode = MomentoErrorCode::LIMIT_EXCEEDED_ERROR;
    public string $messageWrapper = 'Request rate exceeded the limits for this account.  To resolve this error, reduce your request rate, or contact us at support@momentohq.com to request a limit increase';
}

class NotFoundError extends SdkError
{
    public string $errorCode = MomentoErrorCode::NOT_FOUND_ERROR;
    public string $messageWrapper = 'A cache with the specified name does not exist.  To resolve this error, make sure you have created the cache before attempting to use it';
}

class PermissionError extends SdkError
{
    public string $errorCode = MomentoErrorCode::PERMISSION_ERROR;
    public string $messageWrapper = 'Insufficient permissions to perform an operation on a cache';
}

class ServerUnavailableError extends SdkError
{
    public string $errorCode = MomentoErrorCode::SERVER_UNAVAILABLE;
    public string $messageWrapper = 'The server was unable to handle the request; consider retrying.  If the error persists, please contact us at support@momentohq.com';
}

class TimeoutError extends SdkError
{
    public string $errorCode = MomentoErrorCode::TIMEOUT_ERROR;
    public string $messageWrapper = 'The client\'s configured timeout was exceeded; you may need to use a Configuration with more lenient timeouts';
}

class UnknownError extends SdkError
{
    public string $errorCode = MomentoErrorCode::UNKNOWN_ERROR;
    public string $messageWrapper = 'Unknown error has occurred';
}

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
