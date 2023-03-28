<?php
declare(strict_types=1);

namespace Momento\Logging;

use Psr\Log\LoggerInterface;

interface ILoggerFactory
{
    public function getLogger(?string $name=null): LoggerInterface;
}
