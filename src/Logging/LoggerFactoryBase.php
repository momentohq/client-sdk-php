<?php
declare(strict_types=1);

namespace Momento\Logging;

abstract class LoggerFactoryBase implements ILoggerFactory
{
    protected string $logLevel;

    public function getLogLevel(): string|null
    {
        return $this->logLevel;
    }
}
