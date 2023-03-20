<?php
declare(strict_types=1);

namespace Momento\Auth;

use Momento\Cache\Errors\InvalidArgumentError;
use function \Momento\Utilities\isNullOrEmpty;

/**
 * Reads and parses a JWT token stored as an environment variable.
 */
class EnvMomentoTokenProvider implements ICredentialProvider
{
    private string $authToken;
    private string $controlEndpoint;
    private string $cacheEndpoint;
    private ?string $trustedControlEndpointCertificateName = null;
    private ?string $trustedCacheEndpointCertificateName = null;

    public function __construct(
        string  $envVariableName,
        ?string $controlEndpoint = null,
        ?string $cacheEndpoint = null,
        ?string $trustedControlEndpointCertificateName = null,
        ?string $trustedCacheEndpointCertificateName = null
    )
    {
        $authToken = getenv($envVariableName);
        if ($authToken === false || isNullOrEmpty($authToken)) {
            throw new InvalidArgumentError("Environment variable $envVariableName is empty or null.");
        }
        if ($trustedControlEndpointCertificateName xor $trustedCacheEndpointCertificateName) {
            throw new InvalidArgumentError(
                "If either of trustedCacheEndpointCertificateName or trustedControlEndpointCertificateName " .
                "are provided, they must both be."
            );
        }
        $this->authToken = $authToken;
        $payload = AuthUtils::parseAuthToken($authToken);
        $this->controlEndpoint = $controlEndpoint ?? $payload->cp;
        $this->cacheEndpoint = $cacheEndpoint ?? $payload->c;
        $this->trustedControlEndpointCertificateName = $trustedControlEndpointCertificateName;
        $this->trustedCacheEndpointCertificateName = $trustedCacheEndpointCertificateName;
    }

    public function getAuthToken(): string
    {
        return $this->authToken;
    }

    public function getCacheEndpoint(): string
    {
        return $this->cacheEndpoint;
    }

    public function getControlEndpoint(): string
    {
        return $this->controlEndpoint;
    }

    public function getTrustedControlEndpointCertificateName(): string|null
    {
        return $this->trustedControlEndpointCertificateName;
    }

    public function getTrustedCacheEndpointCertificateName(): string|null
    {
        return $this->trustedCacheEndpointCertificateName;
    }

    /**
     * Convenience method for reading and parsing a JWT token stored as an environment variable.
     * @param string $envVariableName Name of the environment variable that contains the JWT token.
     * @return EnvMomentoTokenProvider
     * @throws InvalidArgumentError
     */
    public static function fromEnvironmentVariable(string $envVariableName): EnvMomentoTokenProvider {
        return new EnvMomentoTokenProvider($envVariableName);
    }
}
