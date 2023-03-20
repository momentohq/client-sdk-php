<?php
declare(strict_types=1);

namespace Momento\Config;

use Momento\Config\Retry\IRetryStrategy;
use Momento\Config\Transport\ITransportStrategy;
use Momento\Logging\ILoggerFactory;

interface IConfiguration
{
    public function getLoggerFactory(): ?ILoggerFactory;

    public function getTransportStrategy(): ?ITransportStrategy;

    public function getClientTimeout(): int;

    public function getRetryStrategy(): IRetryStrategy;

    public function withTransportStrategy(ITransportStrategy $transportStrategy): IConfiguration;

    public function withClientTimeout(int $clientTimeout): IConfiguration;

    public function withRetryStrategy(IRetryStrategy $retryStrategy): IConfiguration;
}
