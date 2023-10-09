<?php
declare(strict_types=1);

namespace Momento\Auth;

/**
 * Provides information that the CacheClient needs in order to establish a connection to and authenticate with
 * the Momento service.
 */
interface ICredentialProvider
{
    /**
     * @return string Auth token provided by user, required to authenticate with the service.
     */
    public function getAuthToken(): string;

    /**
     * @return string The host which the Momento client will connect to for Momento control plane operations.
     */
    public function getControlEndpoint(): string;

    /**
     * @return string The host which the Momento client will connect to for Momento data plane operations.
     */
    public function getCacheEndpoint(): string;

    /**
     * @return string|null Used for routing gRPC calls through a proxy server
     */
    public function getTrustedControlEndpointCertificateName(): ?string;

    /**
     * @return string|null Used for routing gRPC calls through a proxy server
     */
    public function getTrustedCacheEndpointCertificateName(): ?string;
}
