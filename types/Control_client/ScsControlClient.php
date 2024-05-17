<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Control_client;

/**
 */
class ScsControlClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Control_client\_CreateCacheRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CreateCache(\Control_client\_CreateCacheRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/control_client.ScsControl/CreateCache',
        $argument,
        ['\Control_client\_CreateCacheResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Control_client\_DeleteCacheRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DeleteCache(\Control_client\_DeleteCacheRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/control_client.ScsControl/DeleteCache',
        $argument,
        ['\Control_client\_DeleteCacheResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Control_client\_ListCachesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListCaches(\Control_client\_ListCachesRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/control_client.ScsControl/ListCaches',
        $argument,
        ['\Control_client\_ListCachesResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Control_client\_FlushCacheRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function FlushCache(\Control_client\_FlushCacheRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/control_client.ScsControl/FlushCache',
        $argument,
        ['\Control_client\_FlushCacheResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Control_client\_CreateSigningKeyRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CreateSigningKey(\Control_client\_CreateSigningKeyRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/control_client.ScsControl/CreateSigningKey',
        $argument,
        ['\Control_client\_CreateSigningKeyResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Control_client\_RevokeSigningKeyRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function RevokeSigningKey(\Control_client\_RevokeSigningKeyRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/control_client.ScsControl/RevokeSigningKey',
        $argument,
        ['\Control_client\_RevokeSigningKeyResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Control_client\_ListSigningKeysRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListSigningKeys(\Control_client\_ListSigningKeysRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/control_client.ScsControl/ListSigningKeys',
        $argument,
        ['\Control_client\_ListSigningKeysResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Control_client\_CreateIndexRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CreateIndex(\Control_client\_CreateIndexRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/control_client.ScsControl/CreateIndex',
        $argument,
        ['\Control_client\_CreateIndexResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Control_client\_DeleteIndexRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DeleteIndex(\Control_client\_DeleteIndexRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/control_client.ScsControl/DeleteIndex',
        $argument,
        ['\Control_client\_DeleteIndexResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Control_client\_ListIndexesRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListIndexes(\Control_client\_ListIndexesRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/control_client.ScsControl/ListIndexes',
        $argument,
        ['\Control_client\_ListIndexesResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Control_client\_CreateStoreRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CreateStore(\Control_client\_CreateStoreRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/control_client.ScsControl/CreateStore',
        $argument,
        ['\Control_client\_CreateStoreResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Control_client\_DeleteStoreRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DeleteStore(\Control_client\_DeleteStoreRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/control_client.ScsControl/DeleteStore',
        $argument,
        ['\Control_client\_DeleteStoreResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Control_client\_ListStoresRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListStores(\Control_client\_ListStoresRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/control_client.ScsControl/ListStores',
        $argument,
        ['\Control_client\_ListStoresResponse', 'decode'],
        $metadata, $options);
    }

}
