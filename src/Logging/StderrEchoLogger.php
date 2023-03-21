<?php
declare(strict_types=1);

namespace Momento\Logging;

use Psr\Log\AbstractLogger;

/**
 * A simple PSR-3 logger class that writes log messages of any log level to
 * stderr, prepended with an optional channel name.
 */
class StderrEchoLogger extends AbstractLogger
{
    private ?string $name;

    public function __construct(?string $name=null)
    {
        $this->name = $name;
    }

    private function interpolate(string $message, array $context = [])
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
