<?php
declare(strict_types=1);

namespace Momento\Config\Transport;

use Momento\Logging\ILoggerFactory;

interface ITransportStrategy
{
    public function getMaxConcurrentRequests(): ?int;

    public function getGrpcConfig(): ?IGrpcConfiguration;

    public function getMaxIdleMillis(): ?int;

    public function withLoggerFactory(ILoggerFactory $loggerFactory);

    public function withGrpcConfig(IGrpcConfiguration $grpcConfig);

    public function withMaxIdleMillis(int $maxIdleMillis);

    public function withClientTimeout(int $clientTimeout);
}
