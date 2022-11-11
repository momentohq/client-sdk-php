<?php
declare(strict_types=1);

namespace Momento\Utilities;

use Psr\Log\AbstractLogger;

class StderrEchoLogger extends AbstractLogger
{
    public function log($level, \Stringable|string $message, array $context = [])
    {
        if (!str_ends_with("$message", "\n")) {
            $message = "$message\n";
        }
        fwrite(STDERR, "$message");
    }
}
