<?php
declare(strict_types=1);

namespace Momento\Auth;

use Momento\Cache\Errors\InvalidArgumentError;
use function Momento\Utilities\isNullOrEmpty;

/**
 * Reads and parses a JWT token stored as a string.
 */
class StringMomentoTokenProvider extends CredentialProvider
{
    protected string  $authToken;
    protected ?string $controlEndpoint = null;
    protected ?string $cacheEndpoint = null;
    protected ?string $trustedControlEndpointCertificateName = null;
    protected ?string $trustedCacheEndpointCertificateName = null;

    public function __construct(
        string  $authToken,
        ?string $controlEndpoint = null,
        ?string $cacheEndpoint = null,
        ?string $trustedControlEndpointCertificateName = null,
        ?string $trustedCacheEndpointCertificateName = null
    )
    {
        if (isNullOrEmpty($authToken)) {
            throw new InvalidArgumentError("String $authToken is empty or null.");
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
}
