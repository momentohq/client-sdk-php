<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Store;

/**
 */
class StoreClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Store\_StoreGetRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Get(\Store\_StoreGetRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/store.Store/Get',
        $argument,
        ['\Store\_StoreGetResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Store\_StoreSetRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Set(\Store\_StoreSetRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/store.Store/Set',
        $argument,
        ['\Store\_StoreSetResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Store\_StoreDeleteRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Delete(\Store\_StoreDeleteRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/store.Store/Delete',
        $argument,
        ['\Store\_StoreDeleteResponse', 'decode'],
        $metadata, $options);
    }

}
