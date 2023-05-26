<?php
declare(strict_types=1);

namespace Momento\Config\Configurations;

use Momento\Config\Configuration;
use Momento\Config\Transport\StaticGrpcConfiguration;
use Momento\Config\Transport\StaticTransportStrategy;
use Momento\Logging\ILoggerFactory;
use Momento\Logging\NullLoggerFactory;

/**
 * Laptop config provides defaults suitable for a medium-to-high-latency dev environment.  Permissive timeouts, retries, and
 * relaxed latency and throughput targets.
 */
class Laptop extends Configuration
{
    /**
     * Provides the latest recommended configuration for a Laptop environment.
     *
     * @param ILoggerFactory|null $loggerFactory
     * @return Laptop
     */
    public static function latest(?ILoggerFactory $loggerFactory = null): Laptop
    {
        return self::v1($loggerFactory);
    }

    /**
     * Provides version 1 configuration for a Laptop environment. This configuration is guaranteed not to change in
     * future releases of the SDK.
     *
     * @param ILoggerFactory|null $loggerFactory
     * @return Laptop
     */
    public static function v1(?ILoggerFactory $loggerFactory = null): Laptop
    {
        $loggerFactory = $loggerFactory ?? new NullLoggerFactory();
        $grpcConfig = new StaticGrpcConfiguration(5000);
        $transportStrategy = new StaticTransportStrategy(null, $grpcConfig, $loggerFactory, self::$maxIdleMillis);
        return new Laptop($loggerFactory, $transportStrategy);
    }
}
