<?php
declare(strict_types=1);

namespace Momento\Auth;

use Momento\Auth\AccessControl\DisposableTokenScope;
use Momento\Auth\AuthOperationTypes\GenerateDisposableTokenResponse;
use Momento\Auth\Internal\ScsTokenClient;
use Momento\Config\IConfiguration;

/**
 * Client to perform operations against Momento's token service.
 */
class AuthClient
{
    private ScsTokenClient $tokenClient;

    /**
     * @param IConfiguration $configuration Configuration to use for transport.
     * @param ITokenCredentialProvider $authProvider Momento authentication provider.
     */
    public function __construct(IConfiguration $configuration, ITokenCredentialProvider $authProvider)
    {
        $this->tokenClient = new ScsTokenClient($configuration, $authProvider);
    }

    /**
     * Close the client and free up all associated resources. NOTE: the client object will not be usable after calling
     * this method.
     */
    public function close(): void
    {
        $this->tokenClient->close();
    }

    /**
     * Generates a short-lived token with the given permissions, suitable for handing to an untrusted client such as a browser.
     *
     * @param DisposableTokenScope $scope The permissions to grant to the token.
     * @param int $validForSeconds The number of seconds after which the token expires.
     * @param string|null $tokenId An optional identifier to embed in the token, at most 64 characters.
     * @return GenerateDisposableTokenResponse Represents the result of the generate disposable token operation. This
     * result is resolved to a type-safe object of one of the following types:<br>
     * * GenerateDisposableTokenSuccess<br>
     * * GenerateDisposableTokenError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>
     * if ($success = $response->asSuccess()) {
     *   return $success->authToken();
     * } elseif ($error = $response->asError()) {
     *   // handle error condition
     * }
     * </code>
     */
    public function generateDisposableToken(DisposableTokenScope $scope, int $validForSeconds, ?string $tokenId = null): GenerateDisposableTokenResponse
    {
        return $this->tokenClient->generateDisposableToken($scope, $validForSeconds, $tokenId);
    }
}
