<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Store;

/**
 * Found on the `err` trailer for error responses, when the error is sent by the server and it has a relevant value to set
 * {
 *   Key: "err"
 *   Values: [
 *      This is to indicate the error is coming from Momento, and not tonic or other middleware
 *    "momento_general_err",
 *      The server may or may not have tried to process this command, but it was unable
 *      to complete it either way. A resource was exhausted which prevented the request
 *      from completing.
 *    "server_is_busy",
 *      Indicates that the stored type for the key supplied isn't compatible with the
 *      requested operation
 *    "invalid_type",
 *      Indicates the item doesn't exist for the given key
 *    "item_not_found",
 *      Indicates the store doesn't exist
 *    "store_not_found"
 *   ]
 * },
 * Found on the `retry_disposition` trailer for error responses, when the value is known by the service
 * {
 *   Key: "retry_disposition"
 *   Values: [
 *      This rpc is safe to retry, even if it is non-idempotent. It was not executed by the server.
 *    "retryable",
 *      This rpc may be safe to retry, but it may have been applied.
 *      Non-idempotent commands should not be retried, unless configured explicitly.
 *      Idempotent commands should be considered eligible for retry.
 *    "unknown"
 *   ]
 * }
 *
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
     * @param \Store\_StorePutRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Put(\Store\_StorePutRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/store.Store/Put',
        $argument,
        ['\Store\_StorePutResponse', 'decode'],
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
