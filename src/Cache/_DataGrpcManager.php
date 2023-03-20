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
use Momento\Cache\Interceptors\RetryInterceptor;
use Momento\Config\Retry\IRetryStrategy;

class _DataGrpcManager
{

    public ScsClient $client;

    public function __construct(ICredentialProvider $authProvider, IRetryStrategy $retryStrategy)
    {
        $endpoint = $authProvider->getCacheEndpoint();
        $channelArgs = ["credentials" => ChannelCredentials::createSsl()];
        if ($authProvider->getTrustedCacheEndpointCertificateName()) {
            $channelArgs["grpc.ssl_target_name_override"] = $authProvider->getTrustedCacheEndpointCertificateName();
        }
        $channel = new Channel($endpoint, $channelArgs);
        $interceptors = [
            new AuthorizationInterceptor($authProvider->getAuthToken()),
            new AgentInterceptor(),
            new RetryInterceptor($retryStrategy),
        ];
        $channel = Interceptor::intercept($channel, $interceptors);
        $options = [];
        $this->client = new ScsClient($endpoint, $options, $channel);
    }
}
