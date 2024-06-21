<?php
declare(strict_types=1);

namespace Momento\Config;

use Momento\Config\Transport\ITransportStrategy;
use Momento\Logging\ILoggerFactory;

/**
 * Contract for SDK configurables. A configuration must have a logger factory and a transport strategy.
 */
interface IStorageConfiguration
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
     * Creates a new instance of the StorageConfiguration object, updated to use the specified transport strategy.
     *
     * @param ITransportStrategy $transportStrategy This is responsible for configuring network tunables.
     * @return IStorageConfiguration StorageConfiguration object with specified transport strategy
     */
    public function withTransportStrategy(ITransportStrategy $transportStrategy): IStorageConfiguration;

    /**
     * Creates a new instance of the StorageConfiguration object, updated to use the specified client timeout
     *
     * @param int $clientTimeoutSecs The amount of time in seconds to wait before cancelling the request.
     * @return IStorageConfiguration StorageConfiguration object with specified client timeout
     */
    public function withClientTimeout(int $clientTimeoutSecs): IStorageConfiguration;
}
