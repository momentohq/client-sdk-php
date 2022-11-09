<?php

namespace Momento\Config\Configurations;

use Momento\Config\Configuration;
use Momento\Config\Transport\StaticGrpcConfiguration;
use Momento\Config\Transport\StaticTransportStrategy;
use Monolog\Logger;

class Laptop extends Configuration
{

    public static function latest(?Logger $logger = null): Laptop
    {
        $grpcConfig = new StaticGrpcConfiguration(5000);
        $transportStrategy = new StaticTransportStrategy(null, $grpcConfig, $logger);
        return new Laptop($logger, $transportStrategy);
    }

}