<?php
namespace Momento\Cache\Errors;

class SdkError extends \Exception
{
    public function __construct(string $message, int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
