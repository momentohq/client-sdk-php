<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: cacheclient.proto

namespace Cache_client\_SetDifferenceRequest\_Subtrahend;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Subtract the set's identity (itself) from itself - which deletes it.
 *
 * Generated from protobuf message <code>cache_client._SetDifferenceRequest._Subtrahend._Identity</code>
 */
class _Identity extends \Google\Protobuf\Internal\Message
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
        \GPBMetadata\Cacheclient::initOnce();
        parent::__construct($data);
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(_Identity::class, \Cache_client\_SetDifferenceRequest__Subtrahend__Identity::class);

