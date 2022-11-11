<?php
declare(strict_types=1);

namespace Momento\Config\Configurations;

use Momento\Config\Configuration;
use Momento\Config\Transport\StaticGrpcConfiguration;
use Momento\Config\Transport\StaticTransportStrategy;
use Momento\Utilities\LoggingHelper;
use Psr\Log\LoggerInterface;

class Laptop extends Configuration
{

    public static function latest(?LoggerInterface $logger = null): Laptop
    {
        $logger = $logger ?? LoggingHelper::getNullLogger("null");
        $grpcConfig = new StaticGrpcConfiguration(5000);
        $transportStrategy = new StaticTransportStrategy(null, $grpcConfig, $logger);
        return new Laptop($logger, $transportStrategy);
    }

}
