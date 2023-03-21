<?php
declare(strict_types=1);

namespace Momento\Cache\Interceptors;

use Grpc\Interceptor;

class AgentInterceptor extends Interceptor
{

    private const AGENT = "php:0.6.0";
    private bool $isFirstRequest = true;

    public function interceptUnaryUnary($method, $argument, $deserialize, $continuation, array $metadata = [], array $options = [])
    {
        if ($this->isFirstRequest) {
            $metadata["agent"] = [self::AGENT];
            $this->isFirstRequest = false;
        }
        return $continuation($method, $argument, $deserialize, $metadata, $options);
    }

}
