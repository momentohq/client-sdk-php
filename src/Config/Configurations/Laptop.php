<?php
declare(strict_types=1);

namespace Momento\Config\Configurations;

use Momento\Config\Configuration;
use Momento\Config\Transport\StaticGrpcConfiguration;
use Momento\Config\Transport\StaticTransportStrategy;
use Momento\Logging\ILoggerFactory;
use Momento\Logging\NullLoggerFactory;

class Laptop extends Configuration
{

    public static function latest(?ILoggerFactory $loggerFactory = null): Laptop
    {
        $loggerFactory = $loggerFactory ?? new NullLoggerFactory();
        $grpcConfig = new StaticGrpcConfiguration(5000);
        $transportStrategy = new StaticTransportStrategy(null, $grpcConfig, $loggerFactory);
        return new Laptop($loggerFactory, $transportStrategy);
    }

    public static function loadgen(): Laptop
    {
        $ret = new Laptop();
        $ret->forceNew = true;
        return $ret;
    }

}
