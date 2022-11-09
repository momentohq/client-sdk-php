<?php
declare(strict_types=1);

namespace Momento\Config;

use Momento\Config\Transport\ITransportStrategy;
use Monolog\Logger;

interface IConfiguration
{
    public function getLogger(): ?Logger;

    public function getTransportStrategy(): ?ITransportStrategy;
}