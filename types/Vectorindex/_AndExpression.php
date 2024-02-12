<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: vectorindex.proto

namespace Vectorindex;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>vectorindex._AndExpression</code>
 */
class _AndExpression extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>.vectorindex._FilterExpression first_expression = 1;</code>
     */
    protected $first_expression = null;
    /**
     * Generated from protobuf field <code>.vectorindex._FilterExpression second_expression = 2;</code>
     */
    protected $second_expression = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Vectorindex\_FilterExpression $first_expression
     *     @type \Vectorindex\_FilterExpression $second_expression
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Vectorindex::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>.vectorindex._FilterExpression first_expression = 1;</code>
     * @return \Vectorindex\_FilterExpression|null
     */
    public function getFirstExpression()
    {
        return $this->first_expression;
    }

    public function hasFirstExpression()
    {
        return isset($this->first_expression);
    }

    public function clearFirstExpression()
    {
        unset($this->first_expression);
    }

    /**
     * Generated from protobuf field <code>.vectorindex._FilterExpression first_expression = 1;</code>
     * @param \Vectorindex\_FilterExpression $var
     * @return $this
     */
    public function setFirstExpression($var)
    {
        GPBUtil::checkMessage($var, \Vectorindex\_FilterExpression::class);
        $this->first_expression = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.vectorindex._FilterExpression second_expression = 2;</code>
     * @return \Vectorindex\_FilterExpression|null
     */
    public function getSecondExpression()
    {
        return $this->second_expression;
    }

    public function hasSecondExpression()
    {
        return isset($this->second_expression);
    }

    public function clearSecondExpression()
    {
        unset($this->second_expression);
    }

    /**
     * Generated from protobuf field <code>.vectorindex._FilterExpression second_expression = 2;</code>
     * @param \Vectorindex\_FilterExpression $var
     * @return $this
     */
    public function setSecondExpression($var)
    {
        GPBUtil::checkMessage($var, \Vectorindex\_FilterExpression::class);
        $this->second_expression = $var;

        return $this;
    }

}

