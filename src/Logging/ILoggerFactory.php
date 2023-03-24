<?php
declare(strict_types=1);

namespace Momento\Logging;

use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

interface ILoggerFactory
{
    public function getLogger(?string $name=null): LoggerInterface;

    public function getLogLevel(): string|null;
}
