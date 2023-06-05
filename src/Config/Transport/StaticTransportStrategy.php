<?php
declare(strict_types=1);

namespace Momento\Config\Transport;

use Momento\Logging\ILoggerFactory;

class StaticTransportStrategy implements ITransportStrategy
{
    private ?int $maxConcurrentRequests;
    private ?IGrpcConfiguration $grpcConfig;
    private ?ILoggerFactory $loggerFactory;
    private ?int $maxIdleMillis;

    public function __construct(
        ?int                $maxConcurrentRequests = null,
        ?IGrpcConfiguration $grpcConfig = null,
        ?ILoggerFactory     $loggerFactory = null,
        ?int                $maxIdleMillis = null,
    )
    {
        $this->maxConcurrentRequests = $maxConcurrentRequests;
        $this->grpcConfig = $grpcConfig;
        $this->loggerFactory = $loggerFactory;
        $this->maxIdleMillis = $maxIdleMillis;
    }

    public function getMaxConcurrentRequests(): ?int
    {
        return $this->maxConcurrentRequests;
    }

    public function getGrpcConfig(): IGrpcConfiguration|null
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
            $this->maxConcurrentRequests, $this->grpcConfig, $loggerFactory, $this->maxIdleMillis
        );
    }

    public function withGrpcConfig(IGrpcConfiguration $grpcConfig): StaticTransportStrategy
    {
        return new StaticTransportStrategy(
            $this->maxConcurrentRequests, $grpcConfig, $this->loggerFactory, $this->maxIdleMillis
        );
    }

    public function withMaxIdleMillis(int $maxIdleMillis): StaticTransportStrategy
    {
        return new StaticTransportStrategy(
             $this->maxConcurrentRequests, $this->grpcConfig, $this->loggerFactory, $maxIdleMillis
        );
    }

    public function withClientTimeout(int $clientTimeout): StaticTransportStrategy
    {
        return new StaticTransportStrategy(
            $this->maxConcurrentRequests,
            $this->grpcConfig->withDeadlineMilliseconds($clientTimeout),
            $this->loggerFactory,
            $this->maxIdleMillis
        );
    }

    public function withForceNewChannel(bool $forceNewChannel): StaticTransportStrategy
    {
        return new StaticTransportStrategy(
            $this->maxConcurrentRequests,
            $this->grpcConfig->withForceNewChannel($forceNewChannel),
            $this->loggerFactory,
            $this->maxIdleMillis
        );
    }
}
