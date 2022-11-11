<?php
declare(strict_types=1);

namespace Momento\Cache;

use Cache_client\ScsClient;
use Grpc\Channel;
use Grpc\ChannelCredentials;
use Grpc\Interceptor;
use Momento\Cache\Interceptors\AgentInterceptor;
use Momento\Cache\Interceptors\AuthorizationInterceptor;

class _DataGrpcManager
{

    public ScsClient $client;

    public function __construct(string $authToken, string $endpoint)
    {
        $options = [];
        $channel = new Channel($endpoint, ["credentials" => ChannelCredentials::createSsl()]);
        $interceptors = [
            new AuthorizationInterceptor($authToken),
            new AgentInterceptor(),
        ];
        $channel = Interceptor::intercept($channel, $interceptors);
        $this->client = new ScsClient($endpoint, $options, $channel);
    }
}
