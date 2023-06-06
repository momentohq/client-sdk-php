<?php
declare(strict_types=1);

namespace Momento\Config\Configurations;

use Momento\Config\Configuration;
use Momento\Config\Transport\StaticGrpcConfiguration;
use Momento\Config\Transport\StaticTransportStrategy;
use Momento\Logging\ILoggerFactory;
use Momento\Logging\NullLoggerFactory;

/**
 * InRegion provides defaults suitable for an environment where your client is running in the same region as the Momento
 * service.  It has more aggressive timeouts and retry behavior than the Laptop config.
 */
class InRegion extends Configuration
{
    /**
     * Provides the latest recommended configuration for an InRegion environment.
     * This configuration may change in future releases to take advantage of
     * improvements we identify for default configurations.
     *
     * @param ILoggerFactory|null $loggerFactory
     * @return InRegion
     */
    public static function latest(?ILoggerFactory $loggerFactory = null): Laptop
    {
        return self::v1($loggerFactory);
    }

    /**
     * Provides the version 1 configuration for an InRegion environment. This configuration is guaranteed not to change
     * in future releases of the SDK.
     *
     * @param ILoggerFactory|null $loggerFactory
     * @return InRegion
     */
    public static function v1(?ILoggerFactory $loggerFactory = null): InRegion
    {
        $loggerFactory = $loggerFactory ?? new NullLoggerFactory();
        $grpcConfig = new StaticGrpcConfiguration(1100);
        $transportStrategy = new StaticTransportStrategy($grpcConfig, $loggerFactory, self::$maxIdleMillis);
        return new self($loggerFactory, $transportStrategy);
    }
}
