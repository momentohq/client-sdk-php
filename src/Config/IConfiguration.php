<?php
declare(strict_types=1);

namespace Momento\Config;

use Momento\Config\Transport\ITransportStrategy;
use Psr\Log\LoggerInterface;

interface IConfiguration
{
    public function getLogger(): ?LoggerInterface;

    public function getTransportStrategy(): ?ITransportStrategy;
}
