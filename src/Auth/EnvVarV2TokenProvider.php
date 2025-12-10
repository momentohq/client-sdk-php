<?php

declare(strict_types=1);

namespace Momento\Auth;

use Momento\Cache\Errors\InvalidArgumentError;
use function Momento\Utilities\isNullOrEmpty;

/**
 * Reads and parses a Momento auth token stored as an environment variable.
 */
class EnvVarV2TokenProvider extends ApiKeyV2TokenProvider
{
    public function __construct(
        string  $apiKeyEnvVar,
        string $endpointEnvVar,
        ?string $controlEndpoint = null,
        ?string $cacheEndpoint = null,
        ?string $trustedControlEndpointCertificateName = null,
        ?string $trustedCacheEndpointCertificateName = null
    ) {
        if (isNullOrEmpty($endpointEnvVar)) {
            throw new InvalidArgumentError("Endpoint environment variable name is empty or null.");
        }
        if (isNullOrEmpty($_SERVER[$endpointEnvVar] ?? null)) {
            throw new InvalidArgumentError("Environment variable $endpointEnvVar is empty or null.");
        }
        $endpoint = $_SERVER[$endpointEnvVar];

        if (isNullOrEmpty($apiKeyEnvVar)) {
            throw new InvalidArgumentError("API key environment variable name is empty or null.");
        }
        if (isNullOrEmpty($_SERVER[$apiKeyEnvVar] ?? null)) {
            throw new InvalidArgumentError("Environment variable $apiKeyEnvVar is empty or null.");
        }
        $authToken = $_SERVER[$apiKeyEnvVar];

        if (!AuthUtils::isV2ApiKey($authToken)) {
            throw new InvalidArgumentError('Received an invalid v2 API key. Are you using the correct key? Or did you mean to use `fromEnvironmentVariable()` with a legacy key instead?');
        }
        parent::__construct($authToken, $endpoint, $controlEndpoint, $cacheEndpoint, $trustedControlEndpointCertificateName, $trustedCacheEndpointCertificateName);
    }
}
