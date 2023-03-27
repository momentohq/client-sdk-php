<?php
declare(strict_types=1);

namespace Momento\Logging;

use Psr\Log\LogLevel;

abstract class LoggerFactoryBase implements ILoggerFactory
{
    protected string $logLevel;

    public function getLogLevel(): string|null
    {
        return $this->logLevel;
    }
}
