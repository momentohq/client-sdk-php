<?php
namespace Momento\Cache;

use Cache_client\ScsClient;
use Grpc\Channel;
use Grpc\ChannelCredentials;

class _DataGrpcManager
{

    public ScsClient $client;

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
        $this->client = new ScsClient($endpoint, $options, $channel);
    }
}
