<?php
declare(strict_types=1);

namespace Momento\Auth;

use Momento\Cache\Errors\InvalidArgumentError;
use function Momento\Utilities\isNullOrEmpty;

/**
 * Reads and parses a Momento auth token stored as an environment variable.
 */
class GlobalKeyEnvMomentoTokenProvider extends GlobalKeyStringMomentoTokenProvider
{
    public function __construct(
        string  $envVariableName,
        string $endpoint,
        ?string $controlEndpoint = null,
        ?string $cacheEndpoint = null,
        ?string $trustedControlEndpointCertificateName = null,
        ?string $trustedCacheEndpointCertificateName = null
    )
    {
        if (isNullOrEmpty($endpoint)) {
            throw new InvalidArgumentError("String $endpoint is empty or null.");
        }
        if (isNullOrEmpty($envVariableName)) {
            throw new InvalidArgumentError("String $envVariableName is empty or null.");
        }
        if (isNullOrEmpty($_SERVER[$envVariableName] ?? null)) {
            throw new InvalidArgumentError("Environment variable $envVariableName is empty or null.");
        }
        $authToken = $_SERVER[$envVariableName];
        parent::__construct($authToken, $endpoint, $controlEndpoint, $cacheEndpoint, $trustedControlEndpointCertificateName, $trustedCacheEndpointCertificateName);
    }
}
