<?php
declare(strict_types=1);

namespace Momento\Config;

use Momento\Config\Transport\ITransportStrategy;
use Momento\Logging\ILoggerFactory;

interface IConfiguration
{
    public function getLoggerFactory(): ?ILoggerFactory;

    public function getTransportStrategy(): ?ITransportStrategy;
}
