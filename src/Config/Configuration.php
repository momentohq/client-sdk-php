<?php
declare(strict_types=1);

namespace Momento\Config;

use Momento\Config\Transport\ITransportStrategy;
use Momento\Logging\ILoggerFactory;
use Momento\Logging\NullLoggerFactory;

/**
 * Contract for SDK configurables. A configuration must have a logger factory and a transport strategy.
 */
class Configuration implements IConfiguration
{

    private ?ILoggerFactory $loggerFactory;
    private ?ITransportStrategy $transportStrategy;
    private string $readConcern;
    protected static int $maxIdleMillis = 4 * 60 * 1000;

    public function __construct(?ILoggerFactory $loggerFactory, ?ITransportStrategy $transportStrategy, ?string $readConcern = null)
    {
        $this->loggerFactory = $loggerFactory ?? new NullLoggerFactory();
        $this->transportStrategy = $transportStrategy;
        $this->readConcern = $readConcern ?? ReadConcern::BALANCED;
    }

    /**
     * @return ILoggerFactory The currently active logger factory
     */
    public function getLoggerFactory(): ILoggerFactory {
        return $this->loggerFactory;
    }

    /**
     * @return ITransportStrategy The currently active transport strategy
     */
    public function getTransportStrategy(): ITransportStrategy
    {
        return $this->transportStrategy;
    }

    /**
     * @return string The currently active read consistency configuration
     */
    public function getReadConcern(): string
    {
        return $this->readConcern;
    }

    /**
     * Creates a new instance of the Configuration object, updated to use the specified transport strategy.
     *
     * @param ITransportStrategy $transportStrategy This is responsible for configuring network tunables.
     * @return IConfiguration Configuration object with specified transport strategy
     */
    public function withTransportStrategy(ITransportStrategy $transportStrategy): IConfiguration
    {
        return new Configuration($this->loggerFactory, $transportStrategy);
    }

    /**
     * Creates a new instance of the Configuration object, updated to use the specified client timeout
     *
     * @param int $clientTimeoutSecs The amount of time in seconds to wait before cancelling the request.
     * @return IConfiguration Configuration object with specified client timeout
     */
    public function withClientTimeout(int $clientTimeoutSecs): IConfiguration
    {
        return new Configuration($this->loggerFactory, $this->transportStrategy->withClientTimeout($clientTimeoutSecs));
    }

    /**
     * Creates a new instance of the Configuration object, updated to use the specified read consistency.
     *
     * @param ReadConcern $readConcern The read consistency configuration to use.
     * @return IConfiguration Configuration object with specified read concern.
     */
    public function withReadConcern(string $readConcern): IConfiguration
    {
        return new Configuration($this->loggerFactory, $this->transportStrategy, $readConcern);
    }
}
