<?php
declare(strict_types=1);

namespace Momento\Cache\Internal;

use Control_client\ScsControlClient;
use Grpc\Channel;
use Grpc\ChannelCredentials;
use Grpc\Interceptor;
use Momento\Auth\ICredentialProvider;
use Momento\Cache\Interceptors\AgentInterceptor;
use Momento\Cache\Interceptors\AuthorizationInterceptor;


class ControlGrpcManager
{

    public ScsControlClient $client;
    private Channel $channel;

    public function __construct(ICredentialProvider $authProvider)
    {
        $endpoint = $authProvider->getControlEndpoint();
        $channelArgs = ["credentials" => ChannelCredentials::createSsl()];
        if ($authProvider->getTrustedControlEndpointCertificateName()) {
            $channelArgs["grpc.ssl_target_name_override"] = $authProvider->getTrustedControlEndpointCertificateName();
        }
        $this->channel = new Channel($endpoint, $channelArgs);
        $interceptors = [
            new AuthorizationInterceptor($authProvider->getAuthToken()),
            new AgentInterceptor(),
        ];
        $interceptedChannel = Interceptor::intercept($this->channel, $interceptors);
        $options = [];
        $this->client = new ScsControlClient($endpoint, $options, $interceptedChannel);
    }

    public function close(): void {
        $this->channel->close();
    }

}
