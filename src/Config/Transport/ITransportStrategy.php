<?php
declare(strict_types=1);

namespace Momento\Config\Transport;

use Momento\Config\Transport\IGrpcConfiguration;
use Momento\Logging\ILoggerFactory;
use Monolog\Logger;

interface ITransportStrategy
{
    public function getMaxConcurrentRequests(): ?int;

    public function getGrpcConfig(): ?IGrpcConfiguration;

    public function withLoggerFactory(ILoggerFactory $loggerFactory);

    public function withGrpcConfig(IGrpcConfiguration $grpcConfig);

    public function withClientTimeout(int $clientTimeout);
}