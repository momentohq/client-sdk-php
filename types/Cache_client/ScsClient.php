<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Cache_client;

/**
 */
class ScsClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * @param \Cache_client\_GetRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Get(\Cache_client\_GetRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/Get',
        $argument,
        ['\Cache_client\_GetResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_SetRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Set(\Cache_client\_SetRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/Set',
        $argument,
        ['\Cache_client\_SetResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_DeleteRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Delete(\Cache_client\_DeleteRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/Delete',
        $argument,
        ['\Cache_client\_DeleteResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_DictionaryGetRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DictionaryGet(\Cache_client\_DictionaryGetRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/DictionaryGet',
        $argument,
        ['\Cache_client\_DictionaryGetResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_DictionaryFetchRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DictionaryFetch(\Cache_client\_DictionaryFetchRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/DictionaryFetch',
        $argument,
        ['\Cache_client\_DictionaryFetchResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_DictionarySetRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DictionarySet(\Cache_client\_DictionarySetRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/DictionarySet',
        $argument,
        ['\Cache_client\_DictionarySetResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_DictionaryDeleteRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DictionaryDelete(\Cache_client\_DictionaryDeleteRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/DictionaryDelete',
        $argument,
        ['\Cache_client\_DictionaryDeleteResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_SetFetchRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function SetFetch(\Cache_client\_SetFetchRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/SetFetch',
        $argument,
        ['\Cache_client\_SetFetchResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_SetUnionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function SetUnion(\Cache_client\_SetUnionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/SetUnion',
        $argument,
        ['\Cache_client\_SetUnionResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_SetDifferenceRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function SetDifference(\Cache_client\_SetDifferenceRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/SetDifference',
        $argument,
        ['\Cache_client\_SetDifferenceResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_ListPushFrontRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListPushFront(\Cache_client\_ListPushFrontRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/ListPushFront',
        $argument,
        ['\Cache_client\_ListPushFrontResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_ListPushBackRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListPushBack(\Cache_client\_ListPushBackRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/ListPushBack',
        $argument,
        ['\Cache_client\_ListPushBackResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_ListPopFrontRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListPopFront(\Cache_client\_ListPopFrontRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/ListPopFront',
        $argument,
        ['\Cache_client\_ListPopFrontResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_ListPopBackRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListPopBack(\Cache_client\_ListPopBackRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/ListPopBack',
        $argument,
        ['\Cache_client\_ListPopBackResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_ListEraseRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListErase(\Cache_client\_ListEraseRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/ListErase',
        $argument,
        ['\Cache_client\_ListEraseResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_ListRemoveRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListRemove(\Cache_client\_ListRemoveRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/ListRemove',
        $argument,
        ['\Cache_client\_ListRemoveResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_ListFetchRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListFetch(\Cache_client\_ListFetchRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/ListFetch',
        $argument,
        ['\Cache_client\_ListFetchResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_ListLengthRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListLength(\Cache_client\_ListLengthRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/ListLength',
        $argument,
        ['\Cache_client\_ListLengthResponse', 'decode'],
        $metadata, $options);
    }

}
