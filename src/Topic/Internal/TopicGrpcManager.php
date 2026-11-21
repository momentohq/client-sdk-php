<?php
declare(strict_types=1);

namespace Momento\Topic\Internal;

use Cache_client\Pubsub\PubsubClient;
use Grpc\Channel;
use Grpc\ChannelCredentials;
use Grpc\Interceptor;
use Momento\Auth\ICredentialProvider;
use Momento\Cache\Interceptors\AgentInterceptor;
use Momento\Cache\Interceptors\AuthorizationInterceptor;
use Momento\Config\IConfiguration;

class TopicGrpcManager
{
    public PubsubClient $client;
    private Channel $channel;

    public function __construct(ICredentialProvider $authProvider, IConfiguration $configuration)
    {
        $endpoint = $authProvider->getCacheEndpoint();
        $channelArgs = [
            "credentials" => ChannelCredentials::createSsl(),
            "grpc.service_config_disable_resolution" => 1, // Disable service config resolution to avoid TXT record lookup
        ];

        if ($authProvider->getTrustedCacheEndpointCertificateName()) {
            $channelArgs["grpc.ssl_target_name_override"] = $authProvider->getTrustedCacheEndpointCertificateName();
        }
        $forceNewChannel = $configuration
            ->getTransportStrategy()
            ->getGrpcConfig()
            ->getForceNewChannel();
        if ($forceNewChannel) {
            $channelArgs["force_new"] = $forceNewChannel;
        }
        if ($configuration->getTransportStrategy()->getGrpcConfig()->getKeepAlivePermitWithoutCalls()) {
            $channelArgs["grpc.keepalive_permit_without_calls"] =
                $configuration->getTransportStrategy()->getGrpcConfig()->getKeepAlivePermitWithoutCalls();
        }
        if ($configuration->getTransportStrategy()->getGrpcConfig()->getKeepAliveTimeoutMS()) {
            $channelArgs["grpc.keepalive_timeout_ms"] =
                $configuration->getTransportStrategy()->getGrpcConfig()->getKeepAliveTimeoutMS();
        }
        if ($configuration->getTransportStrategy()->getGrpcConfig()->getKeepAliveTimeMS()) {
            $channelArgs["grpc.keepalive_time_ms"] =
                $configuration->getTransportStrategy()->getGrpcConfig()->getKeepAliveTimeMS();
        }
        $this->channel = new Channel($endpoint, $channelArgs);
        $interceptors = [
            new AuthorizationInterceptor($authProvider->getAuthToken()),
            new AgentInterceptor("topic"),
        ];
        $interceptedChannel = Interceptor::intercept($this->channel, $interceptors);

        $options = [];
        $this->client = new PubsubClient($endpoint, $options, $interceptedChannel);
    }

    public function close(): void
    {
        $this->channel->close();
    }
}
