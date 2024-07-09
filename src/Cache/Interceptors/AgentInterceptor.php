<?php
declare(strict_types=1);

namespace Momento\Cache\Interceptors;

use Grpc\Interceptor;

class AgentInterceptor extends Interceptor
{
    private bool $isFirstRequest = true;
    private string $agent;
    private string $runtimeVersion;
    private string $sdkVersion = "1.11.1"; // x-release-please-version


    public function __construct(string $clientType)
    {
        $this->agent = sprintf("php:%s:%s", $clientType, $this->sdkVersion);
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
