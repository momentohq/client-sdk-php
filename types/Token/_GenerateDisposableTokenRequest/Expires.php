<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: token.proto

namespace Token\_GenerateDisposableTokenRequest;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * generate a token that has an expiry
 *
 * Generated from protobuf message <code>token._GenerateDisposableTokenRequest.Expires</code>
 */
class Expires extends \Google\Protobuf\Internal\Message
{
    /**
     * how many seconds do you want the api token to be valid for?
     *
     * Generated from protobuf field <code>uint32 valid_for_seconds = 1;</code>
     */
    protected $valid_for_seconds = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type int $valid_for_seconds
     *           how many seconds do you want the api token to be valid for?
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Token::initOnce();
        parent::__construct($data);
    }

    /**
     * how many seconds do you want the api token to be valid for?
     *
     * Generated from protobuf field <code>uint32 valid_for_seconds = 1;</code>
     * @return int
     */
    public function getValidForSeconds()
    {
        return $this->valid_for_seconds;
    }

    /**
     * how many seconds do you want the api token to be valid for?
     *
     * Generated from protobuf field <code>uint32 valid_for_seconds = 1;</code>
     * @param int $var
     * @return $this
     */
    public function setValidForSeconds($var)
    {
        GPBUtil::checkUint32($var);
        $this->valid_for_seconds = $var;

        return $this;
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(Expires::class, \Token\_GenerateDisposableTokenRequest_Expires::class);

