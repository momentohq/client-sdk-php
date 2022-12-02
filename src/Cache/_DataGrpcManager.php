<?php
declare(strict_types=1);

namespace Momento\Cache;

use Cache_client\ScsClient;
use Grpc\Channel;
use Grpc\ChannelCredentials;
use Grpc\Interceptor;
use Momento\Auth\ICredentialProvider;
use Momento\Cache\Interceptors\AgentInterceptor;
use Momento\Cache\Interceptors\AuthorizationInterceptor;
use Momento\Config\IConfiguration;

class _DataGrpcManager
{

    public ScsClient $client;

    public function __construct(ICredentialProvider $authProvider, IConfiguration $configuration)
    {
        $endpoint = $authProvider->getCacheEndpoint();
        $channelArgs = ["credentials" => ChannelCredentials::createSsl()];
        if ($authProvider->getTrustedCacheEndpointCertificateName()) {
            $channelArgs["grpc.ssl_target_name_override"] = $authProvider->getTrustedCacheEndpointCertificateName();
        }
        if ($configuration->getTransportStrategy()->getGrpcConfig()->getForceNew()) {
            $channelArgs["force_new"] = true;
        }
        $channel = new Channel($endpoint, $channelArgs);
        $interceptors = [
            new AuthorizationInterceptor($authProvider->getAuthToken()),
            new AgentInterceptor(),
        ];
        $channel = Interceptor::intercept($channel, $interceptors);
        $options = [];
        $this->client = new ScsClient($endpoint, $options, $channel);
    }
}
