<?php
declare(strict_types=1);

namespace Momento\Config;

use Momento\Config\Transport\ITransportStrategy;
use Momento\Logging\ILoggerFactory;
use Momento\Logging\NullLoggerFactory;

class Configuration implements IConfiguration
{

    private ?ILoggerFactory $loggerFactory;
    private ?ITransportStrategy $transportStrategy;

    public function __construct(?ILoggerFactory $loggerFactory, ?ITransportStrategy $transportStrategy)
    {
        $this->loggerFactory = $loggerFactory ?? new NullLoggerFactory();
        $this->transportStrategy = $transportStrategy;
    }

    public function getLoggerFactory(): ILoggerFactory|null
    {
        return $this->loggerFactory;
    }

    public function getTransportStrategy(): ITransportStrategy|null
    {
        return $this->transportStrategy;
    }

    public function withLoggerFactory(ILoggerFactory $loggerFactory): IConfiguration
    {
        return new Configuration($loggerFactory, $this->transportStrategy);
    }

    public function withTransportStrategy(ITransportStrategy $transportStrategy): IConfiguration
    {
        return new Configuration($this->loggerFactory, $transportStrategy);
    }
}
