<?php
declare(strict_types=1);

namespace Momento\Config\Transport;

use Momento\Config\Transport\IGrpcConfiguration;
use Monolog\Logger;

interface ITransportStrategy
{
    public function getMaxConcurrentRequests(): ?int;

    public function getGrpcConfig(): ?IGrpcConfiguration;

    public function withLogger(Logger $logger);

    public function withGrpcConfig(IGrpcConfiguration $grpcConfig);

    public function withClientTimeout(int $clientTimeout);
}