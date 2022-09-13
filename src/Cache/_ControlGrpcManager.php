<?php

namespace Momento\Cache;

use Grpc\Channel;
use Grpc\ChannelCredentials;
use Control_client\ScsControlClient;


class _ControlGrpcManager {

    public ScsControlClient $client;

    public function __construct(string $authToken, string $endpoint)
    {
        $options = [
            "update_metadata" => function($metadata) use ($authToken) {
                $metadata["authorization"] = [$authToken];
                $metadata["agent"] = ["php:0.1"];
                return $metadata;
            }
        ];

        $channel = new Channel($endpoint, ["credentials"=>ChannelCredentials::createSsl()]);
        $this->client = new ScsControlClient($endpoint, $options, $channel);
    }

}
