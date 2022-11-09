<?php
declare(strict_types=1);

namespace Momento\Utilities;

use Monolog\Formatter\LineFormatter;
use Monolog\Handler\NullHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

class LoggingHelper
{
    public static function getStreamLogger(
        string  $name,
        string  $stream,
        ?string $logLevel = LogLevel::DEBUG,
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

    public static function getMinimalLogger(string $name): Logger
    {
        $outputFormat = "%channel%: %message%\n";
        $stream = "php://stderr";
        return self::getStreamLogger($name, $stream, outputFormat: $outputFormat);
    }

    public static function getNullLogger(string $name): LoggerInterface
    {
        return new NullLogger();
    }

}
