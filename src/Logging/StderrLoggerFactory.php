<?php
declare(strict_types=1);

namespace Momento\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

/**
 * Factory class for returning a StderrLogger instance.
 */
class StderrLoggerFactory extends LoggerFactoryBase
{
    public function __construct(?string $logLevel=LogLevel::INFO) {
        $this->logLevel = $logLevel;
    }

    public function getLogger(?string $name=null): LoggerInterface
    {
        return new StderrLogger($name, $this->logLevel);
    }
}
