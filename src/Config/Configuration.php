<?php
declare(strict_types=1);

namespace Momento\Config;

use Momento\Config\Retry\IRetryStrategy;
use Momento\Config\Transport\ITransportStrategy;
use Momento\Logging\ILoggerFactory;
use Momento\Logging\NullLoggerFactory;

class Configuration implements IConfiguration
{

    private ?ILoggerFactory $loggerFactory;
    private ?ITransportStrategy $transportStrategy;
    private ?IRetryStrategy $retryStrategy;

    public function __construct(
        ?ILoggerFactory $loggerFactory, ?ITransportStrategy $transportStrategy, ?IRetryStrategy $retryStrategy
    ) {
        $this->loggerFactory = $loggerFactory ?? new NullLoggerFactory();
        $this->transportStrategy = $transportStrategy;
        $this->retryStrategy = $retryStrategy;
    }

    public function getLoggerFactory(): ILoggerFactory|null
    {
        return $this->loggerFactory;
    }

    public function getTransportStrategy(): ITransportStrategy|null
    {
        return $this->transportStrategy;
    }

    public function getClientTimeout(): int {
        return $this->transportStrategy->getClientTimeout();
    }

    public function getRetryStrategy() : IRetryStrategy {
        return $this->retryStrategy;
    }

    public function withLoggerFactory(ILoggerFactory $loggerFactory): IConfiguration
    {
        return new Configuration($loggerFactory, $this->transportStrategy, $this->retryStrategy);
    }

    public function withTransportStrategy(ITransportStrategy $transportStrategy): IConfiguration
    {
        return new Configuration($this->loggerFactory, $transportStrategy, $this->retryStrategy);
    }

    public function withClientTimeout(int $clientTimeout): IConfiguration {
        $newTransportStrategy = $this->transportStrategy->withClientTimeout($clientTimeout);
        return $this->withTransportStrategy($newTransportStrategy);
    }

    public function withRetryStrategy(IRetryStrategy $retryStrategy) : IConfiguration {
        return new Configuration($this->loggerFactory, $this->transportStrategy, $retryStrategy);
    }

}
