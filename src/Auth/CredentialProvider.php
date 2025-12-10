<?php

declare(strict_types=1);

namespace Momento\Auth;

use Momento\Cache\Errors\InvalidArgumentError;

/**
 * Base class for credential provider types.
 */
abstract class CredentialProvider implements ICredentialProvider
{
    /**
     * @return string Auth token provided by user, required to authenticate with the service.
     */
    public abstract function getAuthToken(): string;

    /**
     * @return string The host which the Momento client will connect to for Momento control plane operations.
     */
    public abstract function getControlEndpoint(): string;

    /**
     * @return string The host which the Momento client will connect to for Momento data plane operations.
     */
    public abstract function getCacheEndpoint(): string;

    /**
     * @return string|null Used for routing gRPC calls through a proxy server
     */
    public abstract function getTrustedControlEndpointCertificateName(): ?string;

    /**
     * @return string|null Used for routing gRPC calls through a proxy server
     */
    public abstract function getTrustedCacheEndpointCertificateName(): ?string;

    /**
     * Convenience method for reading and parsing a JWT token stored as a string.
     * @param string $authToken The JWT token.
     * @return StringMomentoTokenProvider
     * @throws InvalidArgumentError
     * 
     * @deprecated since version 1.18.0, use fromApiKeyV2() or fromDisposableToken() instead.
     */
    public static function fromString(string $authToken): ICredentialProvider
    {
        return new StringMomentoTokenProvider($authToken);
    }

    /**
     * Convenience method for reading and parsing a JWT token stored as an environment variable.
     * @param string $envVariableName Name of the environment variable that contains the JWT token.
     * @return EnvMomentoTokenProvider
     * @throws InvalidArgumentError
     * 
     * @deprecated since version 1.18.0, use fromEnvVarV2() instead.
     */
    public static function fromEnvironmentVariable(string $envVariableName): ICredentialProvider
    {
        return new EnvMomentoTokenProvider($envVariableName);
    }

    /**
     * Convenience method for constructing a CredentialProvider from an endpoint
     * and a v2 api key provided as strings.
     * @param string $apiKey The v2 api key.
     * @param string $endpoint The Momento service endpoint.
     * @return ApiKeyV2TokenProvider
     * @throws InvalidArgumentError
     */
    public static function fromApiKeyV2(string $apiKey, string $endpoint): ICredentialProvider
    {
        return new ApiKeyV2TokenProvider($apiKey, $endpoint);
    }

    /**
     * Convenience method for constructing a CredentialProvider from a Momento service endpoint
     * and a v2 api key stored as environment variables.
     * @param string $apiKeyEnvVar The name of the environment variable containing the v2 api key.
     * @param string $endpointEnvVar The name of the environment variable containing the Momento service endpoint.
     * @return EnvVarV2TokenProvider
     * @throws InvalidArgumentError
     */
    public static function fromEnvVarV2(string $apiKeyEnvVar, string $endpointEnvVar): ICredentialProvider
    {
        return new EnvVarV2TokenProvider($apiKeyEnvVar, $endpointEnvVar);
    }

    /**
     * Convenience method for constructing a CredentialProvider from a disposable token.
     * @param string $authToken The Momento disposable token.
     * @return DisposableTokenProvider
     * @throws InvalidArgumentError
     */
    public static function fromDisposableToken(string $authToken): ICredentialProvider
    {
        return new DisposableTokenProvider($authToken);
    }
}
