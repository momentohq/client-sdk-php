<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: cacheclient.proto

namespace Cache_client;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>cache_client._SetPopRequest</code>
 */
class _SetPopRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>bytes set_name = 1;</code>
     */
    protected $set_name = '';
    /**
     * Generated from protobuf field <code>uint32 count = 2;</code>
     */
    protected $count = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $set_name
     *     @type int $count
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Cacheclient::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>bytes set_name = 1;</code>
     * @return string
     */
    public function getSetName()
    {
        return $this->set_name;
    }

    /**
     * Generated from protobuf field <code>bytes set_name = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setSetName($var)
    {
        GPBUtil::checkString($var, False);
        $this->set_name = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>uint32 count = 2;</code>
     * @return int
     */
    public function getCount()
    {
        return $this->count;
    }

    /**
     * Generated from protobuf field <code>uint32 count = 2;</code>
     * @param int $var
     * @return $this
     */
    public function setCount($var)
    {
        GPBUtil::checkUint32($var);
        $this->count = $var;

        return $this;
    }

}

