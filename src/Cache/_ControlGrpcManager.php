<?php
declare(strict_types=1);

namespace Momento\Cache;

use Control_client\ScsControlClient;
use Grpc\Channel;
use Grpc\ChannelCredentials;
use Grpc\Interceptor;
use Momento\Auth\ICredentialProvider;
use Momento\Cache\Interceptors\AgentInterceptor;
use Momento\Cache\Interceptors\AuthorizationInterceptor;


class _ControlGrpcManager
{

    public ScsControlClient $client;

    public function __construct(ICredentialProvider $authProvider)
    {
        $endpoint = $authProvider->getControlEndpoint();
        $channelArgs = ["credentials" => ChannelCredentials::createSsl()];
        if ($authProvider->getTrustedControlEndpointCertificateName()) {
            $channelArgs["grpc.ssl_target_name_override"] = $authProvider->getTrustedControlEndpointCertificateName();
        }
        $channel = new Channel($endpoint, $channelArgs);
        $interceptors = [
            new AuthorizationInterceptor($authProvider->getAuthToken()),
            new AgentInterceptor(),
        ];
        $channel = Interceptor::intercept($channel, $interceptors);
        $options = [];
        $this->client = new ScsControlClient($endpoint, $options, $channel);
    }

}
