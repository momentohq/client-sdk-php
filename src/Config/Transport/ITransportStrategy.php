<?php
declare(strict_types=1);

namespace Momento\Config\Transport;

use Momento\Logging\ILoggerFactory;

/**
 * Contract for transport layer configurables.
 *
 * This interface may change as options are added to the configuration.
 */
interface ITransportStrategy
{
    public function getGrpcConfig(): ?IGrpcConfiguration;

    public function getMaxIdleMillis(): ?int;

    public function withLoggerFactory(ILoggerFactory $loggerFactory);

    public function withGrpcConfig(IGrpcConfiguration $grpcConfig);

    public function withMaxIdleMillis(int $maxIdleMillis);

    public function withClientTimeout(int $clientTimeout);
}
