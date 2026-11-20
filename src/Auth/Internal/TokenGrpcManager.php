<?php
declare(strict_types=1);

namespace Momento\Auth\Internal;

use Grpc\Channel;
use Grpc\ChannelCredentials;
use Grpc\Interceptor;
use Momento\Auth\ITokenCredentialProvider;
use Momento\Cache\Interceptors\AgentInterceptor;
use Token\TokenClient;

class TokenGrpcManager
{
    public TokenClient $client;
    private Channel $channel;

    public function __construct(ITokenCredentialProvider $authProvider)
    {
        $endpoint = $authProvider->getTokenEndpoint();
        $channelArgs = [
            "credentials" => ChannelCredentials::createSsl(),
            "grpc.service_config_disable_resolution" => 1, // Disable service config resolution to avoid TXT record lookup
        ];
        $this->channel = new Channel($endpoint, $channelArgs);

        // The token service authenticates the API key carried in the
        // request message, so no authorization metadata is sent.
        $interceptors = [
            new AgentInterceptor("auth"),
        ];
        $interceptedChannel = Interceptor::intercept($this->channel, $interceptors);
        $options = [];
        $this->client = new TokenClient($endpoint, $options, $interceptedChannel);
    }

    public function close(): void
    {
        $this->channel->close();
    }
}
