<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: store.proto

namespace Store;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * This response is for when a delete request concludes successfully.
 * These are some of the Errors and their corresponding GRPC status codes.
 * 1. Invalid argument was provided, value is missing -> grpc code = INVALID_ARGUMENT.  Metadata: "err" -> "momento_general_err"
 * 2. Store is currently busy. grpc code = UNAVAILABLE. Metadata: "err" -> "server_is_busy", "retry_disposition" -> "retryable"
 * 3. Store not found. grpc code = NOT_FOUND. Metadata: "err" -> "store_not_found"
 *
 * Generated from protobuf message <code>store._StoreDeleteResponse</code>
 */
class _StoreDeleteResponse extends \Google\Protobuf\Internal\Message
{

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Store::initOnce();
        parent::__construct($data);
    }

}

