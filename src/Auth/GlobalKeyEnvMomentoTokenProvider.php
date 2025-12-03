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
    ) {
        if (isNullOrEmpty($endpoint)) {
            throw new InvalidArgumentError("Endpoint is empty or null.");
        }
        if (isNullOrEmpty($envVariableName)) {
            throw new InvalidArgumentError("Environment variable name is empty or null.");
        }
        if (isNullOrEmpty($_SERVER[$envVariableName] ?? null)) {
            throw new InvalidArgumentError("Environment variable $envVariableName is empty or null.");
        }

        $authToken = $_SERVER[$envVariableName];
        if (AuthUtils::isBase64Encoded($authToken)) {
            throw new InvalidArgumentError('Did not expect global API key to be base64 encoded. Are you using the correct key? Or did you mean to use `fromEnvironmentVariable()` instead?');
        }
        if (AuthUtils::isGlobalApiKey($authToken)) {
            throw new InvalidArgumentError('Provided API key is not a valid global API key. Are you using the correct key? Or did you mean to use `fromEnvironmentVariable()` instead?');
        }
        parent::__construct($authToken, $endpoint, $controlEndpoint, $cacheEndpoint, $trustedControlEndpointCertificateName, $trustedCacheEndpointCertificateName);
    }
}
