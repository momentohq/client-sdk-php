<?php
declare(strict_types=1);

namespace Momento\Config\Configurations;

use Momento\Config\Configuration;
use Momento\Config\Retry\DefaultEligibilityStrategy;
use Momento\Config\Retry\FixedCountRetryStrategy;
use Momento\Config\Transport\StaticGrpcConfiguration;
use Momento\Config\Transport\StaticTransportStrategy;
use Momento\Logging\ILoggerFactory;
use Momento\Logging\NullLoggerFactory;

class InRegion extends Configuration
{

    public static function latest(?ILoggerFactory $loggerFactory = null): Laptop
    {
        $loggerFactory = $loggerFactory ?? new NullLoggerFactory();
        $grpcConfig = new StaticGrpcConfiguration(1100);
        $transportStrategy = new StaticTransportStrategy(null, $grpcConfig, $loggerFactory);
        $eligibilityStrategy = new DefaultEligibilityStrategy();
        $retryStrategy = new FixedCountRetryStrategy(
            $eligibilityStrategy,
            3,
            $loggerFactory->getLogger("retry-strategy")
        );
        return new Laptop($loggerFactory, $transportStrategy, $retryStrategy);
    }

}
