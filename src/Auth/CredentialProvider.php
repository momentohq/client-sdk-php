<?php
declare(strict_types=1);

namespace Momento\Auth;

use Momento\Cache\Errors\InvalidArgumentError;

/**
 * Base class for credential provider types.
 */
abstract class CredentialProvider implements ICredentialProvider
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

    /**
     * @return string|null Used for routing gRPC calls through a proxy server
     */
    public abstract function getTrustedControlEndpointCertificateName(): ?string;

    /**
     * @return string|null Used for routing gRPC calls through a proxy server
     */
    public abstract function getTrustedCacheEndpointCertificateName(): ?string;

    /**
     * Convenience method for reading and parsing a JWT token stored as a string.
     * @param string $authToken The JWT token.
     * @return StringMomentoTokenProvider
     * @throws InvalidArgumentError
     */
    public static function fromString(string $authToken): ICredentialProvider
    {
        return new StringMomentoTokenProvider($authToken);
    }

    /**
     * Convenience method for reading and parsing a JWT token stored as an environment variable.
     * @param string $envVariableName Name of the environment variable that contains the JWT token.
     * @return EnvMomentoTokenProvider
     * @throws InvalidArgumentError
     */
    public static function fromEnvironmentVariable(string $envVariableName): ICredentialProvider
    {
        return new EnvMomentoTokenProvider($envVariableName);
    }
}
