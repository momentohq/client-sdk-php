<?php
declare(strict_types=1);

namespace Momento\Auth;

use Momento\Cache\Errors\InvalidArgumentError;

/**
 * Reads and parses a JWT token stored as an environment variable.
 */
class EnvMomentoTokenProvider extends CredentialProvider
{
    public function __construct(
        string  $envVariableName,
        ?string $controlEndpoint = null,
        ?string $cacheEndpoint = null,
        ?string $trustedControlEndpointCertificateName = null,
        ?string $trustedCacheEndpointCertificateName = null
    )
    {
        parent::__construct($envVariableName, $controlEndpoint, $cacheEndpoint, $trustedControlEndpointCertificateName, $trustedCacheEndpointCertificateName);
    }

    /**
     * Convenience method for reading and parsing a JWT token stored as an environment variable.
     * @param string $envVariableName Name of the environment variable that contains the JWT token.
     * @return EnvMomentoTokenProvider
     * @throws InvalidArgumentError
     */
    public static function fromEnvironmentVariable(string $envVariableName): EnvMomentoTokenProvider
    {
        return new EnvMomentoTokenProvider($envVariableName);
    }
}
