<?php
declare(strict_types=1);

namespace Momento\Auth;

use Momento\Cache\Errors\InvalidArgumentError;

/**
 * Reads and parses a JWT token stored as a string.
 */
class StringMomentoTokenProvider extends CredentialProvider
{
    public function __construct(
        string  $authToken,
        ?string $controlEndpoint = null,
        ?string $cacheEndpoint = null,
        ?string $trustedControlEndpointCertificateName = null,
        ?string $trustedCacheEndpointCertificateName = null
    )
    {
        parent::__construct($authToken, $controlEndpoint, $cacheEndpoint, $trustedControlEndpointCertificateName, $trustedCacheEndpointCertificateName);
    }

    /**
     * Convenience method for reading and parsing a JWT token stored as a string.
     * @param string $authToken The JWT token.
     * @return StringMomentoTokenProvider
     * @throws InvalidArgumentError
     */
    public static function fromString(string $authToken): StringMomentoTokenProvider
    {
        return new StringMomentoTokenProvider($authToken);
    }
}
