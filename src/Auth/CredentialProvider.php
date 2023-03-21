<?php
declare(strict_types=1);

namespace Momento\Auth;

use Momento\Cache\Errors\InvalidArgumentError;

abstract class CredentialProvider implements ICredentialProvider
{
    public abstract function getAuthToken(): string;

    public abstract function getControlEndpoint(): string;

    public abstract function getCacheEndpoint(): string;

    public abstract function getTrustedControlEndpointCertificateName(): string|null;

    public abstract function getTrustedCacheEndpointCertificateName(): string|null;

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
