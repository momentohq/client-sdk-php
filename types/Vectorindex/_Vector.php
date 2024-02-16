<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: vectorindex.proto

namespace Vectorindex;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>vectorindex._Vector</code>
 */
class _Vector extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>repeated float elements = 1;</code>
     */
    private $elements;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type float[]|\Google\Protobuf\Internal\RepeatedField $elements
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Vectorindex::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>repeated float elements = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * Generated from protobuf field <code>repeated float elements = 1;</code>
     * @param float[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setElements($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::FLOAT);
        $this->elements = $arr;

        return $this;
    }

}

