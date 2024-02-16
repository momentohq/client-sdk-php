<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Cache_client;

/**
 */
class PingClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Cache_client\_PingRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Ping(\Cache_client\_PingRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Ping/Ping',
        $argument,
        ['\Cache_client\_PingResponse', 'decode'],
        $metadata, $options);
    }

}
