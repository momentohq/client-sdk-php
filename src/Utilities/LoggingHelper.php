<?php
declare(strict_types=1);

namespace Momento\Utilities;

use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

class LoggingHelper
{
    public static function getMinimalLogger(): LoggerInterface
    {
        return new StderrEchoLogger();
    }

    public static function getNullLogger(): LoggerInterface
    {
        return new NullLogger();
    }

}
