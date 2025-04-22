<?php
declare(strict_types=1);

namespace Momento\Config;

use Momento\Config\Transport\ITransportStrategy;
use Momento\Logging\ILoggerFactory;

/**
 * Contract for SDK configurables. A configuration must have a logger factory and a transport strategy.
 *
 * This interface may change as options are added to the configuration.
 */
interface IConfiguration
{
    /**
     * @return ILoggerFactory The currently active logger factory
     */
    public function getLoggerFactory(): ILoggerFactory;

    /**
     * @return ITransportStrategy The currently active transport strategy
     */
    public function getTransportStrategy(): ITransportStrategy;

    /**
     * @return string The currently active read consistency configuration
     */
    public function getReadConcern(): string;

    /**
     * Creates a new instance of the Configuration object, updated to use the specified transport strategy.
     *
     * @param ITransportStrategy $transportStrategy This is responsible for configuring network tunables.
     * @return IConfiguration Configuration object with specified transport strategy
     */
    public function withTransportStrategy(ITransportStrategy $transportStrategy): IConfiguration;

    /**
     * Creates a new instance of the Configuration object, updated to use the specified client timeout
     *
     * @param int $clientTimeoutSecs The amount of time in seconds to wait before cancelling the request.
     * @return IConfiguration Configuration object with specified client timeout
     */
    public function withClientTimeout(int $clientTimeoutSecs): IConfiguration;

    /**
     * Creates a new instance of the Configuration object, updated to use the specified read consistency.
     *
     * @param ReadConcern $readConcern The read consistency configuration to use.
     * @return IConfiguration Configuration object with specified read concern.
     */
    public function withReadConcern(string $readConcern): IConfiguration;
}
