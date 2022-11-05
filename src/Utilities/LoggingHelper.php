<?php
declare(strict_types=1);

namespace Momento\Utilities;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;

class LoggingHelper
{
    public static function getStreamLogger(
        string  $name,
        string  $stream,
        Level   $logLevel,
        ?string $outputFormat = null
    ): Logger
    {
        $logger = new Logger($name);
        $streamHandler = new StreamHandler($stream, $logLevel);
        if ($outputFormat) {
            $formatter = new LineFormatter($outputFormat);
            $streamHandler->setFormatter($formatter);
        }
        $logger->pushHandler($streamHandler);
        return $logger;
    }

    public static function getStderrLogger(
        string $name, string $outputFormat = "[%datetime%] %channel%.%level_name%: %message%\n"
    ): Logger
    {
        $stream = "php://stderr";
        return self::getStreamLogger($name, $stream, Level::Debug, $outputFormat);
    }

    public static function getFileLogger(
        string  $name,
        string  $filename,
        ?Level  $logLevel = Level::Debug,
        ?string $outputFormat = null
    ): Logger
    {
        return self::getStreamLogger($name, $filename, $logLevel, $outputFormat);
    }

    public static function getMinimalLogger(string $name): Logger
    {
        $outputFormat = "%channel%: %message%\n";
        return self::getStderrLogger($name, $outputFormat);
    }

}
