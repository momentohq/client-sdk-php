<?php

namespace Momento\Cache;

use Grpc\Channel;
use Grpc\ChannelCredentials;
use Control_client\ScsControlClient;

class _ControlGrpcManager {

    private Channel $channel;
    public ScsControlClient $client;

    public function __construct(string $authToken, string $endpoint)
    {
        $uri = "https://" . $endpoint;
        $this->channel = new Channel($uri, ["credentials"=>ChannelCredentials::createSsl()]);
        $this->client = new ScsControlClient($endpoint, [], $this->channel);
    }

}
