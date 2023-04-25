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

    /**
     * api for initially generating api and refresh tokens
     * @param \Auth\_GenerateApiTokenRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GenerateApiToken(\Auth\_GenerateApiTokenRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/auth.Auth/GenerateApiToken',
        $argument,
        ['\Auth\_GenerateApiTokenResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * api for programmatically refreshing api and refresh tokens
     * @param \Auth\_RefreshApiTokenRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RefreshApiToken(\Auth\_RefreshApiTokenRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/auth.Auth/RefreshApiToken',
        $argument,
        ['\Auth\_RefreshApiTokenResponse', 'decode'],
        $metadata, $options);
    }

}
