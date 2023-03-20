<?php
declare(strict_types=1);

namespace Momento\Cache;

use Grpc\Channel;
use Grpc\ChannelCredentials;
use Control_client\ScsControlClient;
use Grpc\Interceptor;
use Momento\Auth\CredentialProvider;
use Momento\Cache\Interceptors\AgentInterceptor;
use Momento\Cache\Interceptors\AuthorizationInterceptor;


class _ControlGrpcManager
{

    public ScsControlClient $client;

    public function __construct(CredentialProvider $authProvider)
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
