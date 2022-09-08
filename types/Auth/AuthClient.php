<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Auth;

/**
 */
class AuthClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Auth\_LoginRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\ServerStreamingCall
     */
    public function Login(\Auth\_LoginRequest $argument,
      $metadata = [], $options = []) {
        return $this->_serverStreamRequest('/auth.Auth/Login',
        $argument,
        ['\Auth\_LoginResponse', 'decode'],
        $metadata, $options);
    }

}
