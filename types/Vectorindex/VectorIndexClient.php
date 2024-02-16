<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Vectorindex;

/**
 */
class VectorIndexClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Vectorindex\_UpsertItemBatchRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function UpsertItemBatch(\Vectorindex\_UpsertItemBatchRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/vectorindex.VectorIndex/UpsertItemBatch',
        $argument,
        ['\Vectorindex\_UpsertItemBatchResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Vectorindex\_DeleteItemBatchRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DeleteItemBatch(\Vectorindex\_DeleteItemBatchRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/vectorindex.VectorIndex/DeleteItemBatch',
        $argument,
        ['\Vectorindex\_DeleteItemBatchResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Vectorindex\_SearchRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Search(\Vectorindex\_SearchRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/vectorindex.VectorIndex/Search',
        $argument,
        ['\Vectorindex\_SearchResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Vectorindex\_SearchAndFetchVectorsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function SearchAndFetchVectors(\Vectorindex\_SearchAndFetchVectorsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/vectorindex.VectorIndex/SearchAndFetchVectors',
        $argument,
        ['\Vectorindex\_SearchAndFetchVectorsResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Vectorindex\_GetItemMetadataBatchRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetItemMetadataBatch(\Vectorindex\_GetItemMetadataBatchRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/vectorindex.VectorIndex/GetItemMetadataBatch',
        $argument,
        ['\Vectorindex\_GetItemMetadataBatchResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Vectorindex\_GetItemBatchRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function GetItemBatch(\Vectorindex\_GetItemBatchRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/vectorindex.VectorIndex/GetItemBatch',
        $argument,
        ['\Vectorindex\_GetItemBatchResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Vectorindex\_CountItemsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function CountItems(\Vectorindex\_CountItemsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/vectorindex.VectorIndex/CountItems',
        $argument,
        ['\Vectorindex\_CountItemsResponse', 'decode'],
        $metadata, $options);
    }

}
