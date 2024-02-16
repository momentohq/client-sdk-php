<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: vectorindex.proto

namespace Vectorindex;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>vectorindex._Item</code>
 */
class _Item extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string id = 1;</code>
     */
    protected $id = '';
    /**
     * Generated from protobuf field <code>.vectorindex._Vector vector = 2;</code>
     */
    protected $vector = null;
    /**
     * Generated from protobuf field <code>repeated .vectorindex._Metadata metadata = 3;</code>
     */
    private $metadata;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $id
     *     @type \Vectorindex\_Vector $vector
     *     @type \Vectorindex\_Metadata[]|\Google\Protobuf\Internal\RepeatedField $metadata
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Vectorindex::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string id = 1;</code>
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Generated from protobuf field <code>string id = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setId($var)
    {
        GPBUtil::checkString($var, True);
        $this->id = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.vectorindex._Vector vector = 2;</code>
     * @return \Vectorindex\_Vector|null
     */
    public function getVector()
    {
        return $this->vector;
    }

    public function hasVector()
    {
        return isset($this->vector);
    }

    public function clearVector()
    {
        unset($this->vector);
    }

    /**
     * Generated from protobuf field <code>.vectorindex._Vector vector = 2;</code>
     * @param \Vectorindex\_Vector $var
     * @return $this
     */
    public function setVector($var)
    {
        GPBUtil::checkMessage($var, \Vectorindex\_Vector::class);
        $this->vector = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>repeated .vectorindex._Metadata metadata = 3;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Generated from protobuf field <code>repeated .vectorindex._Metadata metadata = 3;</code>
     * @param \Vectorindex\_Metadata[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setMetadata($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Vectorindex\_Metadata::class);
        $this->metadata = $arr;

        return $this;
    }

}

