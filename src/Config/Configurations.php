<?php
declare(strict_types=1);

namespace Momento\Config;

use Momento\Config\Configurations\Laptop;
use Momento\Config\Transport\StaticGrpcConfiguration;
use Momento\Config\Transport\StaticTransportStrategy;
use Momento\Utilities\LoggingHelper;
use Psr\Log\LoggerInterface;

class Configurations
{

    public static function laptop(?LoggerInterface $logger = null): IConfiguration
    {
        $logger = $logger ?? LoggingHelper::getNullLogger("null");
        $grpcConfig = new StaticGrpcConfiguration(5000);
        $transportStrategy = new StaticTransportStrategy(200, $grpcConfig, $logger);
        return new Laptop($logger, $transportStrategy);
    }

}