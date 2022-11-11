<?php
declare(strict_types=1);

namespace Momento\Config;

use Momento\Config\Transport\ITransportStrategy;
use Psr\Log\LoggerInterface;

class Configuration implements IConfiguration
{

    private ?LoggerInterface $logger;
    private ?ITransportStrategy $transportStrategy;

    public function __construct(?LoggerInterface $logger, ?ITransportStrategy $transportStrategy)
    {
        $this->logger = $logger;
        $this->transportStrategy = $transportStrategy;
    }

    public function getLogger(): LoggerInterface|null
    {
        return $this->logger;
    }

    public function getTransportStrategy(): ITransportStrategy|null
    {
        return $this->transportStrategy;
    }

    public function withLogger(LoggerInterface $logger): IConfiguration
    {
        return new Configuration($logger, $this->transportStrategy);
    }

    public function withTransportStrategy(ITransportStrategy $transportStrategy): IConfiguration
    {
        return new Configuration($this->logger, $transportStrategy);
    }
}
