<?php
declare(strict_types=1);

namespace Momento\Auth;

use Momento\Cache\Errors\InvalidArgumentError;
use function \Momento\Utilities\isNullOrEmpty;


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
        $this->authToken = $authToken;

        $endpointArgs = [
            $controlEndpoint, $cacheEndpoint, $trustedCacheEndpointCertificateName, $trustedControlEndpointCertificateName
        ];
        if ($this->anyAreDefined($endpointArgs)) {
            if (!$this->allAreDefined($endpointArgs)) {
                throw new InvalidArgumentError(
                    "If any of controlEndpoint, cacheEndpoint, trustedCacheEndpointCertificateName, or " .
                    "trustedControlEndpointCertificateName are provided, they must all be.");
            }
            $this->controlEndpoint = $controlEndpoint;
            $this->cacheEndpoint = $cacheEndpoint;
            $this->trustedControlEndpointCertificateName = $trustedControlEndpointCertificateName;
            $this->trustedCacheEndpointCertificateName = $trustedCacheEndpointCertificateName;
        } else {
            $payload = AuthUtils::parseAuthToken($authToken);
            $this->controlEndpoint = $payload->cp;
            $this->cacheEndpoint = $payload->c;
        }
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

    private function anyAreDefined(array $input): bool
    {
        return in_array(true, $input);
    }

    private function allAreDefined(array $input): bool
    {
        return !in_array(false, $input);
    }
}
