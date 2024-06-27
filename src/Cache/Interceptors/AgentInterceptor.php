<?php
declare(strict_types=1);

namespace Momento\Cache\Interceptors;

use Grpc\Interceptor;

class AgentInterceptor extends Interceptor
{
    private bool $isFirstRequest = true;
    private string $agent;
    private string $runtimeVersion;

    public function __construct(string $clientType)
    {
        $this->agent = sprintf("php:%s:1.10.0", $clientType);
        $this->runtimeVersion = PHP_VERSION;
    }

    public function interceptUnaryUnary($method, $argument, $deserialize, $continuation, array $metadata = [], array $options = [])
    {
        if ($this->isFirstRequest) {
            $metadata["agent"] = [$this->agent];
            $metadata["runtime-version"] = [$this->runtimeVersion];
            $this->isFirstRequest = false;
        }
        return parent::interceptUnaryUnary($method, $argument, $deserialize, $continuation, $metadata, $options);
    }
}
