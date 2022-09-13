<?php
namespace Momento\Cache\Errors;

class SdkError extends \Exception
{
    public function __construct(string $message, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}

class ClientSdkError extends \Exception {}

class MomentoServiceError extends SdkError {}

class AlreadyExistsError extends MomentoServiceError {}

class AuthenticationError extends MomentoServiceError {}

class BadRequestError extends MomentoServiceError {}

class CancelledError extends MomentoServiceError {}

class InternalServerError extends MomentoServiceError {}

class InvalidArgumentError extends ClientSdkError {}

class LimitExceededError extends MomentoServiceError {}

class NotFoundError extends MomentoServiceError {}

class PermissionError extends MomentoServiceError {}

class TimeoutError extends MomentoServiceError {}
