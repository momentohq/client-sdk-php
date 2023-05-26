<?php
declare(strict_types=1);

namespace Momento\Cache\Internal;

use Cache_client\ScsClient;
use Grpc\Channel;
use Grpc\ChannelCredentials;
use Grpc\Interceptor;
use Momento\Auth\ICredentialProvider;
use Momento\Cache\Interceptors\AgentInterceptor;
use Momento\Cache\Interceptors\AuthorizationInterceptor;

class DataGrpcManager
{
    public ScsClient $client;
    private Channel $channel;

    public function __construct(ICredentialProvider $authProvider)
    {
        $endpoint = $authProvider->getCacheEndpoint();
        $channelArgs = ["credentials" => ChannelCredentials::createSsl()];
        if ($authProvider->getTrustedCacheEndpointCertificateName()) {
            $channelArgs["grpc.ssl_target_name_override"] = $authProvider->getTrustedCacheEndpointCertificateName();
        }
        $this->channel = new Channel($endpoint, $channelArgs);
        $interceptors = [
            new AuthorizationInterceptor($authProvider->getAuthToken()),
            new AgentInterceptor(),
        ];
        $interceptedChannel = Interceptor::intercept($this->channel, $interceptors);

        $options = [];
        $this->client = new ScsClient($endpoint, $options, $interceptedChannel);
    }

    public function close(): void {
        $this->channel->close();
    }
}
