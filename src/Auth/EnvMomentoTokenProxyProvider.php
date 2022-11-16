<?php
declare(strict_types=1);

namespace Momento\Auth;

use Momento\Cache\Errors\InvalidArgumentError;
use function Momento\Utilities\isNullOrEmpty;

class EnvMomentoTokenProxyProvider implements ICredentialProvider
{

    private string $authToken;
    private string $controlProxyEndpoint;
    private string $cacheProxyEndpoint;
    private string $controlEndpoint;
    private string $cacheEndpoint;

    public function __construct(
        string $envVariableName,
        string $controlProxyEndpoint,
        string $cacheProxyEndpoint
    )
    {
        $this->controlProxyEndpoint = $controlProxyEndpoint;
        $this->cacheProxyEndpoint = $cacheProxyEndpoint;
        $authToken = getenv($envVariableName);
        if ($authToken === false || isNullOrEmpty($authToken)) {
            throw new InvalidArgumentError("Environment variable $envVariableName is empty or null.");
        }
        $payload = AuthUtils::parseAuthToken($authToken);
        $this->authToken = $authToken;
        $this->controlEndpoint = $payload->cp;
        $this->cacheEndpoint = $payload->c;
    }

    public function getAuthToken(): string
    {
        return $this->authToken;
    }

    public function getControlProxyEndpoint(): string|null
    {
        return $this->controlProxyEndpoint;
    }

    public function getCacheProxyEndpoint(): string|null
    {
        return $this->cacheProxyEndpoint;
    }

    public function getControlEndpoint(): string
    {
        return $this->controlEndpoint;
    }

    public function getCacheEndpoint(): string
    {
        return $this->cacheEndpoint;
    }
}
