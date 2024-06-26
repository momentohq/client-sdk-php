<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: controlclient.proto

namespace Control_client;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>control_client._ListStoresResponse</code>
 */
class _ListStoresResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>repeated .control_client._Store store = 1;</code>
     */
    private $store;
    /**
     * Generated from protobuf field <code>string next_token = 2;</code>
     */
    protected $next_token = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Control_client\_Store[]|\Google\Protobuf\Internal\RepeatedField $store
     *     @type string $next_token
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Controlclient::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>repeated .control_client._Store store = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getStore()
    {
        return $this->store;
    }

    /**
     * Generated from protobuf field <code>repeated .control_client._Store store = 1;</code>
     * @param \Control_client\_Store[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setStore($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Control_client\_Store::class);
        $this->store = $arr;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string next_token = 2;</code>
     * @return string
     */
    public function getNextToken()
    {
        return $this->next_token;
    }

    /**
     * Generated from protobuf field <code>string next_token = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setNextToken($var)
    {
        GPBUtil::checkString($var, True);
        $this->next_token = $var;

        return $this;
    }

}

