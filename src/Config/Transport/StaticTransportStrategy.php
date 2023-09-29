<?php
declare(strict_types=1);

namespace Momento\Config\Transport;

use Momento\Logging\ILoggerFactory;

class StaticTransportStrategy implements ITransportStrategy
{
    private IGrpcConfiguration $grpcConfig;
    private ?ILoggerFactory $loggerFactory;
    private ?int $maxIdleMillis;

    public function __construct(
        IGrpcConfiguration $grpcConfig,
        ?ILoggerFactory     $loggerFactory = null,
        ?int                $maxIdleMillis = null,
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

    public function withLoggerFactory(ILoggerFactory $loggerFactory): StaticTransportStrategy
    {
        return new StaticTransportStrategy(
            $this->grpcConfig, $loggerFactory, $this->maxIdleMillis
        );
    }

    public function withGrpcConfig(IGrpcConfiguration $grpcConfig): StaticTransportStrategy
    {
        return new StaticTransportStrategy(
            $grpcConfig, $this->loggerFactory, $this->maxIdleMillis
        );
    }

    public function withMaxIdleMillis(int $maxIdleMillis): StaticTransportStrategy
    {
        return new StaticTransportStrategy(
            $this->grpcConfig, $this->loggerFactory, $maxIdleMillis
        );
    }

    public function withClientTimeout(int $clientTimeout): StaticTransportStrategy
    {
        return new StaticTransportStrategy(
            $this->grpcConfig->withDeadlineMilliseconds($clientTimeout),
            $this->loggerFactory,
            $this->maxIdleMillis
        );
    }
}
