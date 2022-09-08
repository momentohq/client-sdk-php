<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Cache_client;

/**
 */
class HttpCacheClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Cache_client\_HttpGetRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Get(\Cache_client\_HttpGetRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.HttpCache/Get',
        $argument,
        ['\Google\Api\HttpBody', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_HttpSetRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Set(\Cache_client\_HttpSetRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.HttpCache/Set',
        $argument,
        ['\Cache_client\_SetResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_HttpSetRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function SetButItsAPut(\Cache_client\_HttpSetRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.HttpCache/SetButItsAPut',
        $argument,
        ['\Cache_client\_SetResponse', 'decode'],
        $metadata, $options);
    }

}
