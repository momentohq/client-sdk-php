<?php
declare(strict_types=1);

namespace Momento\Auth;

use Momento\Cache\Errors\InvalidArgumentError;
use function Momento\Utilities\isNullOrEmpty;

/**
 * Reads and parses a Momento auth token stored as an environment variable.
 */
class EnvMomentoTokenProvider extends StringMomentoTokenProvider
{
    public function __construct(
        string  $envVariableName,
        ?string $controlEndpoint = null,
        ?string $cacheEndpoint = null,
        ?string $trustedControlEndpointCertificateName = null,
        ?string $trustedCacheEndpointCertificateName = null
    )
    {
        if (isNullOrEmpty($_SERVER[$envVariableName] ?? null)) {
            throw new InvalidArgumentError("Environment variable $envVariableName is empty or null.");
        }
        $authToken = $_SERVER[$envVariableName];
        parent::__construct($authToken, $controlEndpoint, $cacheEndpoint, $trustedControlEndpointCertificateName, $trustedCacheEndpointCertificateName);
    }
}
