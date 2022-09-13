<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: cacheclient.proto

namespace Cache_client;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>cache_client._SetDifferenceRequest</code>
 */
class _SetDifferenceRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>bytes set_name = 1;</code>
     */
    protected $set_name = '';
    protected $difference;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $set_name
     *     @type \Cache_client\_SetDifferenceRequest\_Minuend $minuend
     *     @type \Cache_client\_SetDifferenceRequest\_Subtrahend $subtrahend
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
     * Generated from protobuf field <code>.cache_client._SetDifferenceRequest._Minuend minuend = 2;</code>
     * @return \Cache_client\_SetDifferenceRequest\_Minuend|null
     */
    public function getMinuend()
    {
        return $this->readOneof(2);
    }

    public function hasMinuend()
    {
        return $this->hasOneof(2);
    }

    /**
     * Generated from protobuf field <code>.cache_client._SetDifferenceRequest._Minuend minuend = 2;</code>
     * @param \Cache_client\_SetDifferenceRequest\_Minuend $var
     * @return $this
     */
    public function setMinuend($var)
    {
        GPBUtil::checkMessage($var, \Cache_client\_SetDifferenceRequest\_Minuend::class);
        $this->writeOneof(2, $var);

        return $this;
    }

    /**
     * Generated from protobuf field <code>.cache_client._SetDifferenceRequest._Subtrahend subtrahend = 3;</code>
     * @return \Cache_client\_SetDifferenceRequest\_Subtrahend|null
     */
    public function getSubtrahend()
    {
        return $this->readOneof(3);
    }

    public function hasSubtrahend()
    {
        return $this->hasOneof(3);
    }

    /**
     * Generated from protobuf field <code>.cache_client._SetDifferenceRequest._Subtrahend subtrahend = 3;</code>
     * @param \Cache_client\_SetDifferenceRequest\_Subtrahend $var
     * @return $this
     */
    public function setSubtrahend($var)
    {
        GPBUtil::checkMessage($var, \Cache_client\_SetDifferenceRequest\_Subtrahend::class);
        $this->writeOneof(3, $var);

        return $this;
    }

    /**
     * @return string
     */
    public function getDifference()
    {
        return $this->whichOneof("difference");
    }

}

