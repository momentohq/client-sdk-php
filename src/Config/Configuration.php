<?php

namespace Momento\Config;

use Momento\Config\Transport\ITransportStrategy;
use Monolog\Logger;

class Configuration implements IConfiguration
{

    private ?Logger $logger;
    private ?ITransportStrategy $transportStrategy;

    public function __construct(?Logger $logger, ?ITransportStrategy $transportStrategy)
    {
        $this->logger = $logger;
        $this->transportStrategy = $transportStrategy;
    }

    public function getLogger(): Logger|null
    {
        return $this->logger;
    }

    public function getTransportStrategy(): ITransportStrategy|null
    {
        return $this->transportStrategy;
    }

    public function withLogger(Logger $logger): IConfiguration
    {
        return new Configuration($logger, $this->transportStrategy);
    }

    public function withTransportStrategy(ITransportStrategy $transportStrategy): IConfiguration
    {
        return new Configuration($this->logger, $transportStrategy);
    }
}
