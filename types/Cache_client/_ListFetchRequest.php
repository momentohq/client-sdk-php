<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: cacheclient.proto

namespace Cache_client;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>cache_client._ListFetchRequest</code>
 */
class _ListFetchRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>bytes list_name = 1;</code>
     */
    protected $list_name = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $list_name
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Cacheclient::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>bytes list_name = 1;</code>
     * @return string
     */
    public function getListName()
    {
        return $this->list_name;
    }

    /**
     * Generated from protobuf field <code>bytes list_name = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setListName($var)
    {
        GPBUtil::checkString($var, False);
        $this->list_name = $var;

        return $this;
    }

}

