<?php
declare(strict_types=1);

namespace Momento\Config\Transport;

use Momento\Config\Transport\IGrpcConfiguration;
use Monolog\Logger;
use Psr\Log\LoggerInterface;

class StaticTransportStrategy implements ITransportStrategy
{
    private ?int $maxConcurrentRequests;
    private ?IGrpcConfiguration $grpcConfig;
    private ?LoggerInterface $logger;

    public function __construct(
        ?int $maxConcurrentRequests = null, ?IGrpcConfiguration $grpcConfig = null, ?LoggerInterface $logger = null
    )
    {
        $this->maxConcurrentRequests = $maxConcurrentRequests;
        $this->grpcConfig = $grpcConfig;
        $this->logger = $logger;
    }

    public function getMaxConcurrentRequests(): ?int
    {
        return $this->maxConcurrentRequests;
    }

    public function getGrpcConfig(): IGrpcConfiguration|null
    {
        return $this->grpcConfig;
    }

    public function withLogger(Logger $logger): StaticTransportStrategy
    {
        return new StaticTransportStrategy($this->maxConcurrentRequests, $this->grpcConfig, $logger);
    }

    public function withGrpcConfig(IGrpcConfiguration $grpcConfig): StaticTransportStrategy
    {
        return new StaticTransportStrategy($this->maxConcurrentRequests, $grpcConfig, $this->logger);
    }

    public function withClientTimeout(int $clientTimeout)
    {
        return new StaticTransportStrategy(
            $this->maxConcurrentRequests, $this->grpcConfig->withDeadline($clientTimeout), $this->logger
        );
    }
}
