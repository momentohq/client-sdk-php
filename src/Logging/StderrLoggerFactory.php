<?php
declare(strict_types=1);

namespace Momento\Logging;

use Psr\Log\LoggerInterface;

/**
 * Factory class for returning a StderrEchoLogger instance.
 */
class StderrLoggerFactory implements ILoggerFactory
{

    public function getLogger(?string $name): LoggerInterface
    {
        return new StderrEchoLogger($name);
    }
}
