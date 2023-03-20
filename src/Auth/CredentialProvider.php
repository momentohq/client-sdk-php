<?php
declare(strict_types=1);

namespace Momento\Auth;

/**
 * Provides information that the CacheClient needs in order to establish a connection to and authenticate with
 * the Momento service.
 */
abstract class CredentialProvider
{
    /**
     * @return string Auth token provided by user, required to authenticate with the service.
     */
    public abstract function getAuthToken(): string;

    /**
     * @return string The host which the Momento client will connect to for Momento control plane operations.
     */
    public abstract function getControlEndpoint(): string;

    /**
     * @return string The host which the Momento client will connect to for Momento data plane operations.
     */
    public abstract function getCacheEndpoint(): string;

    public abstract function getTrustedControlEndpointCertificateName(): string|null;

    public abstract function getTrustedCacheEndpointCertificateName(): string|null;
}
