<?php
declare(strict_types=1);

namespace Momento\Auth\AuthOperationTypes;

use Token\_GenerateDisposableTokenResponse;

/**
 * Contains information from the successful request that generated it.
 */
class GenerateDisposableTokenSuccess extends GenerateDisposableTokenResponse
{
    private string $authToken;
    private string $endpoint;
    private int $expiresAt;

    public function __construct(_GenerateDisposableTokenResponse $response)
    {
        parent::__construct();
        $this->endpoint = $response->getEndpoint();
        $this->authToken = base64_encode(json_encode(
            ["endpoint" => $this->endpoint, "api_key" => $response->getApiKey()],
            JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        ));
        $this->expiresAt = (int)$response->getValidUntil();
    }

    /**
     * @return string The disposable token, in the form accepted by CredentialProvider::fromDisposableToken() and the other Momento SDKs.
     */
    public function authToken(): string
    {
        return $this->authToken;
    }

    /**
     * @return string The Momento service endpoint the token is valid for.
     */
    public function endpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * @return int The time at which the token expires, in seconds since the Unix epoch.
     */
    public function expiresAt(): int
    {
        return $this->expiresAt;
    }
}
