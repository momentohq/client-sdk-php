<?php
namespace Momento\Auth;

use Momento\Auth\AuthUtils;
use Momento\Cache\Errors\InvalidArgumentError;
use function \Momento\Utilities\isNullOrEmpty;


class EnvMomentoTokenProvider implements ICredentialProvider
{
    private string $authToken;
    private string $controlEndpoint;
    private string $cacheEndpoint;

    public function __construct(string $envVariableName)
    {
        $authToken = getenv($envVariableName);
        if (isNullOrEmpty($authToken)) {
            throw new InvalidArgumentError("Environment variable $envVariableName is empty or null.");
        }
        $payload = AuthUtils::parseAuthToken($authToken);
        $this->authToken = $authToken;
        $this->controlEndpoint = $payload["cp"];
        $this->cacheEndpoint = $payload["c"];
    }

    public function getAuthToken() : string
    {
        return $this->authToken;
    }

    public function getCacheEndpoint() : string
    {
        return $this->cacheEndpoint;
    }

    public function getControlEndpoint() : string
    {
        return $this->controlEndpoint;
    }
}
