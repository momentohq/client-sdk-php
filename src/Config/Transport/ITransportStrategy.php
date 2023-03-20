<?php
declare(strict_types=1);

namespace Momento\Config\Transport;

use Momento\Logging\ILoggerFactory;

interface ITransportStrategy
{
    public function getMaxConcurrentRequests(): ?int;

    public function getGrpcConfig(): ?IGrpcConfiguration;

    public function withLoggerFactory(ILoggerFactory $loggerFactory);

    public function withGrpcConfig(IGrpcConfiguration $grpcConfig);

    public function withClientTimeout(int $clientTimeout);

    public function getClientTimeout(): int;
}
