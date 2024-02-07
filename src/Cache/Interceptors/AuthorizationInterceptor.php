<?php
declare(strict_types=1);

namespace Momento\Cache\Interceptors;

use Grpc\Interceptor;

class AuthorizationInterceptor extends Interceptor
{

    private string $authToken;

    public function __construct($authToken)
    {
        $this->authToken = $authToken;
    }

    public function interceptUnaryUnary($method, $argument, $deserialize, $continuation, array $metadata = [], array $options = [])
    {
        $metadata["authorization"] = [$this->authToken];
        return parent::interceptUnaryUnary($method, $argument, $deserialize, $continuation, $metadata, $options);
    }

    public function interceptUnaryStream($method, $argument, $deserialize, $continuation, array $metadata = [], array $options = [])
    {
        $metadata["authorization"] = [$this->authToken];
        return parent::interceptUnaryStream($method, $argument, $deserialize, $continuation, $metadata, $options);
    }
}
