<?php
declare(strict_types=1);

namespace Momento\Config\Transport;

use Momento\Logging\ILoggerFactory;

class StaticTransportStrategy implements ITransportStrategy
{
    private ?int $maxConcurrentRequests;
    private ?IGrpcConfiguration $grpcConfig;
    private ?ILoggerFactory $loggerFactory;

    public function __construct(
        ?int                $maxConcurrentRequests = null,
        ?IGrpcConfiguration $grpcConfig = null,
        ?ILoggerFactory     $loggerFactory = null
    )
    {
        $this->maxConcurrentRequests = $maxConcurrentRequests;
        $this->grpcConfig = $grpcConfig;
        $this->loggerFactory = $loggerFactory;
    }

    public function getMaxConcurrentRequests(): ?int
    {
        return $this->maxConcurrentRequests;
    }

    public function getGrpcConfig(): IGrpcConfiguration|null
    {
        return $this->grpcConfig;
    }

    public function withLoggerFactory(ILoggerFactory $loggerFactory): StaticTransportStrategy
    {
        return new StaticTransportStrategy($this->maxConcurrentRequests, $this->grpcConfig, $loggerFactory);
    }

    public function withGrpcConfig(IGrpcConfiguration $grpcConfig): StaticTransportStrategy
    {
        return new StaticTransportStrategy($this->maxConcurrentRequests, $grpcConfig, $this->loggerFactory);
    }

    public function withClientTimeout(int $clientTimeout): StaticTransportStrategy
    {
        return new StaticTransportStrategy(
            $this->maxConcurrentRequests, $this->grpcConfig->withDeadlineMilliseconds($clientTimeout), $this->loggerFactory
        );
    }
}
