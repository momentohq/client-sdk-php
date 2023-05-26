<?php
declare(strict_types=1);

namespace Momento\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

/**
 * Factory class for returning the PSR-3 NullLogger, which will swallow all
 * log messages passed to it.
 */
class NullLoggerFactory extends LoggerFactoryBase
{
    public function getLogger(?string $name=null): LoggerInterface
    {
        return new NullLogger();
    }
}
