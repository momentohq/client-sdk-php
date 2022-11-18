<?php

namespace Momento\Logging;

use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Factory class for returning the PSR-3 logger passed to its constructor.
 */
class PassthroughLoggerFactory implements ILoggerFactory
{

    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function getLogger(?string $name): LoggerInterface
    {
        return $this->logger;
    }

}
