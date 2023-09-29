<?php
declare(strict_types=1);

namespace Momento\Auth;

use Momento\Cache\Errors\InvalidArgumentError;
use function Momento\Utilities\isNullOrEmpty;

/**
 * Reads and parses a Momento auth token stored as a string.
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
        $payload = AuthUtils::parseAuthToken($authToken);
        $this->authToken = $payload->authToken;
        $this->controlEndpoint = $controlEndpoint ?? $payload->cp;
        $this->cacheEndpoint = $cacheEndpoint ?? $payload->c;
        $this->trustedControlEndpointCertificateName = $trustedControlEndpointCertificateName;
        $this->trustedCacheEndpointCertificateName = $trustedCacheEndpointCertificateName;
    }

    /**
     * @return string Auth token provided by user, required to authenticate with the service.
     */
    public function getAuthToken(): string
    {
        return $this->authToken;
    }

    /**
     * @return string The host which the Momento client will connect to for Momento data plane operations.
     */
    public function getCacheEndpoint(): string
    {
        return $this->cacheEndpoint;
    }

    /**
     * @return string The host which the Momento client will connect to for Momento control plane operations.
     */
    public function getControlEndpoint(): string
    {
        return $this->controlEndpoint;
    }

    /**
     * @return string|null Used for routing gRPC calls through a proxy server
     */
    public function getTrustedControlEndpointCertificateName(): ?string
    {
        return $this->trustedControlEndpointCertificateName;
    }

    /**
     * @return string|null Used for routing gRPC calls through a proxy server
     */
    public function getTrustedCacheEndpointCertificateName(): ?string
    {
        return $this->trustedCacheEndpointCertificateName;
    }
}
