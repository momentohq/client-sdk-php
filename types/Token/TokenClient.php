<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Token;

/**
 */
class TokenClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Token\_GenerateDisposableTokenRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GenerateDisposableToken(\Token\_GenerateDisposableTokenRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/token.Token/GenerateDisposableToken',
        $argument,
        ['\Token\_GenerateDisposableTokenResponse', 'decode'],
        $metadata, $options);
    }

}
