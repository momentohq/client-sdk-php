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
     * @param \Cache_client\_SetIfNotExistsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function SetIfNotExists(\Cache_client\_SetIfNotExistsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/SetIfNotExists',
        $argument,
        ['\Cache_client\_SetIfNotExistsResponse', 'decode'],
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
     * @param \Cache_client\_KeysExistRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function KeysExist(\Cache_client\_KeysExistRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/KeysExist',
        $argument,
        ['\Cache_client\_KeysExistResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_IncrementRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Increment(\Cache_client\_IncrementRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/Increment',
        $argument,
        ['\Cache_client\_IncrementResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_UpdateTtlRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function UpdateTtl(\Cache_client\_UpdateTtlRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/UpdateTtl',
        $argument,
        ['\Cache_client\_UpdateTtlResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_ItemGetTtlRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ItemGetTtl(\Cache_client\_ItemGetTtlRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/ItemGetTtl',
        $argument,
        ['\Cache_client\_ItemGetTtlResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_ItemGetTypeRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ItemGetType(\Cache_client\_ItemGetTypeRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/ItemGetType',
        $argument,
        ['\Cache_client\_ItemGetTypeResponse', 'decode'],
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
     * @param \Cache_client\_DictionaryIncrementRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function DictionaryIncrement(\Cache_client\_DictionaryIncrementRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/DictionaryIncrement',
        $argument,
        ['\Cache_client\_DictionaryIncrementResponse', 'decode'],
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
     * @param \Cache_client\_SetContainsRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function SetContains(\Cache_client\_SetContainsRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/SetContains',
        $argument,
        ['\Cache_client\_SetContainsResponse', 'decode'],
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

    /**
     * @param \Cache_client\_ListConcatenateFrontRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListConcatenateFront(\Cache_client\_ListConcatenateFrontRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/ListConcatenateFront',
        $argument,
        ['\Cache_client\_ListConcatenateFrontResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_ListConcatenateBackRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListConcatenateBack(\Cache_client\_ListConcatenateBackRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/ListConcatenateBack',
        $argument,
        ['\Cache_client\_ListConcatenateBackResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * @param \Cache_client\_ListRetainRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function ListRetain(\Cache_client\_ListRetainRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/ListRetain',
        $argument,
        ['\Cache_client\_ListRetainResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Sorted Set Operations
     * A sorted set is a collection of elements ordered by their score.
     * The elements with same score are ordered lexicographically.
     *
     * Add or Updates new element with its score to the Sorted Set.
     * If sorted set doesn't exist, a new one is created with the specified
     * element and its associated score.
     * If an element exists, then its associate score gets overridden with the one
     * provided in this operation.
     * @param \Cache_client\_SortedSetPutRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function SortedSetPut(\Cache_client\_SortedSetPutRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/SortedSetPut',
        $argument,
        ['\Cache_client\_SortedSetPutResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Fetches a subset of elements in the sorted set.
     * @param \Cache_client\_SortedSetFetchRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function SortedSetFetch(\Cache_client\_SortedSetFetchRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/SortedSetFetch',
        $argument,
        ['\Cache_client\_SortedSetFetchResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Gets the specified element and its associated score if it exists in the
     * sorted set.
     * @param \Cache_client\_SortedSetGetScoreRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function SortedSetGetScore(\Cache_client\_SortedSetGetScoreRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/SortedSetGetScore',
        $argument,
        ['\Cache_client\_SortedSetGetScoreResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Removes specified elements and their associated scores
     * @param \Cache_client\_SortedSetRemoveRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function SortedSetRemove(\Cache_client\_SortedSetRemoveRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/SortedSetRemove',
        $argument,
        ['\Cache_client\_SortedSetRemoveResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Changes the score associated with the element by specified amount.
     * If the provided amount is negative, then the score associated with the
     * element is decremented.
     * If the element that needs to be incremented isn't present in the sorted
     * set, it is added with specified number as the score.
     * If the set itself doesn't exist then a new one with specified element and
     * score is created.
     * @param \Cache_client\_SortedSetIncrementRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function SortedSetIncrement(\Cache_client\_SortedSetIncrementRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/SortedSetIncrement',
        $argument,
        ['\Cache_client\_SortedSetIncrementResponse', 'decode'],
        $metadata, $options);
    }

    /**
     * Gives the rank of an element.
     * @param \Cache_client\_SortedSetGetRankRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function SortedSetGetRank(\Cache_client\_SortedSetGetRankRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.Scs/SortedSetGetRank',
        $argument,
        ['\Cache_client\_SortedSetGetRankResponse', 'decode'],
        $metadata, $options);
    }

}
