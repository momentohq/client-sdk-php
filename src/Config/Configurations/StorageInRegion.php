<?php
declare(strict_types=1);

namespace Momento\Config\Configurations;

use Momento\Config\StorageConfiguration;
use Momento\Config\Transport\StaticGrpcConfiguration;
use Momento\Config\Transport\StaticTransportStrategy;
use Momento\Logging\ILoggerFactory;
use Momento\Logging\NullLoggerFactory;

/**
 * StorageInRegion provides defaults suitable for an environment where your client is running
 * in the same region as the Momento service.  It has more aggressive timeouts and retry behavior
 * than the StorageLaptop config.
 */
class StorageInRegion extends StorageConfiguration
{
    /**
     * Provides the latest recommended configuration for an InRegion environment.
     * This configuration may change in future releases to take advantage of
     * improvements we identify for default configurations.
     *
     * @param ILoggerFactory|null $loggerFactory
     * @return StorageInRegion
     */
    public static function latest(?ILoggerFactory $loggerFactory = null): StorageInRegion
    {
        return self::v1($loggerFactory);
    }

    /**
     * Provides the version 1 configuration for an InRegion environment. This configuration is guaranteed not to change
     * in future releases of the SDK.
     *
     * TODO: this is marked private now to hide it from customers until we're ready for a v1
     *
     * @param ILoggerFactory|null $loggerFactory
     * @return StorageInRegion
     */
    private static function v1(?ILoggerFactory $loggerFactory = null): StorageInRegion
    {
        $loggerFactory = $loggerFactory ?? new NullLoggerFactory();
        $grpcConfig = new StaticGrpcConfiguration(1100);
        $transportStrategy = new StaticTransportStrategy($grpcConfig, $loggerFactory, self::$maxIdleMillis);
        return new self($loggerFactory, $transportStrategy);
    }
}
