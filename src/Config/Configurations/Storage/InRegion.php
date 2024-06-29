<?php
declare(strict_types=1);

namespace Momento\Config\Configurations\Storage;

use Momento\Config\StorageConfiguration;
use Momento\Config\Transport\StaticStorageGrpcConfiguration;
use Momento\Config\Transport\StaticStorageTransportStrategy;
use Momento\Logging\ILoggerFactory;
use Momento\Logging\NullLoggerFactory;

/**
 * StorageInRegion provides defaults suitable for an environment where your client is running
 * in the same region as the Momento service.  It has more aggressive timeouts and retry behavior
 * than the StorageLaptop config.
 */
class InRegion extends StorageConfiguration
{
    /**
     * Provides the latest recommended configuration for an InRegion environment.
     * This configuration may change in future releases to take advantage of
     * improvements we identify for default configurations.
     *
     * @param ILoggerFactory|null $loggerFactory
     * @return InRegion
     */
    public static function latest(?ILoggerFactory $loggerFactory = null): InRegion
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
     * @return InRegion
     */
    private static function v1(?ILoggerFactory $loggerFactory = null): InRegion
    {
        $loggerFactory = $loggerFactory ?? new NullLoggerFactory();
        $grpcConfig = new StaticStorageGrpcConfiguration(1100);
        $transportStrategy = new StaticStorageTransportStrategy($grpcConfig, $loggerFactory, self::$maxIdleMillis);
        return new self($loggerFactory, $transportStrategy);
    }
}
