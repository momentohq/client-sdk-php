<?php
declare(strict_types=1);

namespace Momento\Config\Transport;

use Momento\Logging\ILoggerFactory;

class StaticStorageTransportStrategy implements ITransportStrategy
{
    private IGrpcConfiguration $grpcConfig;
    private ?ILoggerFactory $loggerFactory;
    private ?int $maxIdleMillis;

    public function __construct(
        IGrpcConfiguration $grpcConfig,
        ?ILoggerFactory     $loggerFactory = null,
        ?int                $maxIdleMillis = null
    )
    {
        $this->grpcConfig = $grpcConfig;
        $this->loggerFactory = $loggerFactory;
        $this->maxIdleMillis = $maxIdleMillis;
    }

    public function getGrpcConfig(): ?IGrpcConfiguration
    {
        return $this->grpcConfig;
    }

    public function getMaxIdleMillis(): ?int
    {
        return $this->maxIdleMillis;
    }

    public function withLoggerFactory(ILoggerFactory $loggerFactory): StaticStorageTransportStrategy
    {
        return new StaticStorageTransportStrategy(
            $this->grpcConfig, $loggerFactory, $this->maxIdleMillis
        );
    }

    public function withGrpcConfig(IGrpcConfiguration $grpcConfig): StaticStorageTransportStrategy
    {
        return new StaticStorageTransportStrategy(
            $grpcConfig, $this->loggerFactory, $this->maxIdleMillis
        );
    }

    public function withMaxIdleMillis(int $maxIdleMillis): StaticStorageTransportStrategy
    {
        return new StaticStorageTransportStrategy(
            $this->grpcConfig, $this->loggerFactory, $maxIdleMillis
        );
    }

    public function withClientTimeout(int $clientTimeout): StaticStorageTransportStrategy
    {
        return new StaticStorageTransportStrategy(
            $this->grpcConfig->withDeadlineMilliseconds($clientTimeout),
            $this->loggerFactory,
            $this->maxIdleMillis
        );
    }
}
