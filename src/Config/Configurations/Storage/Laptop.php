<?php
declare(strict_types=1);

namespace Momento\Config\Configurations\Storage;

use Momento\Config\StorageConfiguration;
use Momento\Config\Transport\StaticStorageGrpcConfiguration;
use Momento\Config\Transport\StaticStorageTransportStrategy;
use Momento\Logging\ILoggerFactory;
use Momento\Logging\NullLoggerFactory;

/**
 * StorageLaptop config provides defaults suitable for a medium-to-high-latency dev environment.  Permissive timeouts, retries, and
 * relaxed latency and throughput targets.
 */
class Laptop extends StorageConfiguration
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
     * TODO: this is marked private now to hide it from customers until we're ready for a v1
     *
     * @param ILoggerFactory|null $loggerFactory
     * @return Laptop
     */
    private static function v1(?ILoggerFactory $loggerFactory = null): Laptop
    {
        $loggerFactory = $loggerFactory ?? new NullLoggerFactory();
        $grpcConfig = new StaticStorageGrpcConfiguration(5000);
        $transportStrategy = new StaticStorageTransportStrategy($grpcConfig, $loggerFactory, self::$maxIdleMillis);
        return new Laptop($loggerFactory, $transportStrategy);
    }
}
