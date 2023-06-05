<?php
declare(strict_types=1);

namespace Momento\Config;

use Momento\Config\Transport\ITransportStrategy;
use Momento\Logging\ILoggerFactory;

/**
 * Contract for SDK configurables. A configuration must have a logger factory and a transport strategy.
 */
interface IConfiguration
{
    /**
     * @return ILoggerFactory The currently active logger factory
     */
    public function getLoggerFactory(): ILoggerFactory;

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
     * Creates a new instance of the Configuration object, updated to use the specified "force_new" value
     * when creating a gRPC channel
     *
     * @param bool $forceNewChannel If set to boolean "true" value, gRPC channels will be constructed with
     *   "force_new" set to true
     * @return IConfiguration Configuration object with the specified force new channel setting
     */
    public function withForceNewChannel(bool $forceNewChannel): IConfiguration;
}
