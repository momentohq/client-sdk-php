<?php
declare(strict_types=1);

namespace Momento\Config\Configurations;

use Momento\Config\Configuration;
use Momento\Config\ReadConcern;
use Momento\Config\Transport\StaticGrpcConfiguration;
use Momento\Config\Transport\StaticStorageTransportStrategy;
use Momento\Logging\ILoggerFactory;
use Momento\Logging\NullLoggerFactory;

/**
 * Lambda config provides recommended configuration settings for a lambda environment.
 */
class Lambda extends Configuration
{
    /**
     * Provides the latest recommended configuration for a Lambda environment.
     * This configuration may change in future releases to take advantage of
     * improvements we identify for default configurations.
     * @param ILoggerFactory|null $loggerFactory
     * @return Lambda
     */
    public static function latest(?ILoggerFactory $loggerFactory = null): Lambda
    {
        return self::v1($loggerFactory);
    }

    /**
     * Provides the version 1 configuration for a Lambda environment. This configuration is guaranteed not to change
     * in future releases of the SDK.
     * @param ILoggerFactory|null $loggerFactory
     * @return Lambda
     */
    public static function v1(?ILoggerFactory $loggerFactory = null): Lambda
    {
        $loggerFactory = $loggerFactory ?? new NullLoggerFactory();
        $grpcConfig = new StaticGrpcConfiguration(1100);
        $transportStrategy = new StaticStorageTransportStrategy($grpcConfig, $loggerFactory, self::$maxIdleMillis);
        $readConcern = ReadConcern::BALANCED;
        return new Lambda($loggerFactory, $transportStrategy, $readConcern);
    }
}
