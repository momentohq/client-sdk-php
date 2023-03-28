<?php
declare(strict_types=1);

namespace Momento\Logging;

use Psr\Log\AbstractLogger;
use Psr\Log\LogLevel;

/**
 * A simple PSR-3 logger class that writes log messages of any log level to
 * stderr, prepended with an optional channel name.
 */
class StderrLogger extends AbstractLogger
{
    private ?string $name;
    private ?string $logLevel;

    private array $logLevelMap = [
        LogLevel::EMERGENCY => 80,
        LogLevel::ALERT => 70,
        LogLevel::CRITICAL => 60,
        LogLevel::ERROR => 50,
        LogLevel::WARNING => 40,
        LogLevel::NOTICE => 30,
        LogLevel::INFO => 20,
        LogLevel::DEBUG => 10,
    ];

    private function shouldLog(string $requestedLevel, string $myLevel): bool
    {
        return $this->logLevelMap[$requestedLevel] >= $this->logLevelMap[$myLevel];
    }

    public function __construct(?string $name, ?string $logLevel)
    {
        $this->name = $name;
        $this->logLevel = $logLevel;
    }

    private function interpolate(string $message, array $context = []): string
    {
        $replace = [];
        foreach ($context as $key => $value) {
            if (!is_array($value) && (!is_object($value) || method_exists($value, '__toString'))) {
                $replace["{" . $key . "}"] = $value;
            }
        }
        return strtr($message, $replace);
    }

    public function log($level, \Stringable|string $message, array $context = [])
    {
        if (!$this->shouldLog($level, $this->logLevel)) {
            return;
        }
        if (!empty($context)) {
            $message = $this->interpolate($message, $context);
        }
        if (!str_ends_with("$message", "\n")) {
            $message = "$message\n";
        }
        if ($this->name) {
            $message = "[{$this->name}]: $message";
        }
        fwrite(STDERR, "$message");
    }
}
