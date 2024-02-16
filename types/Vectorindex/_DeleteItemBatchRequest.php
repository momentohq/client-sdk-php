<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: vectorindex.proto

namespace Vectorindex;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>vectorindex._DeleteItemBatchRequest</code>
 */
class _DeleteItemBatchRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string index_name = 1;</code>
     */
    protected $index_name = '';
    /**
     * TODO: reserve after migration
     *
     * Generated from protobuf field <code>repeated string ids = 2;</code>
     */
    private $ids;
    /**
     * Generated from protobuf field <code>.vectorindex._FilterExpression filter = 3;</code>
     */
    protected $filter = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $index_name
     *     @type string[]|\Google\Protobuf\Internal\RepeatedField $ids
     *           TODO: reserve after migration
     *     @type \Vectorindex\_FilterExpression $filter
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Vectorindex::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string index_name = 1;</code>
     * @return string
     */
    public function getIndexName()
    {
        return $this->index_name;
    }

    /**
     * Generated from protobuf field <code>string index_name = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setIndexName($var)
    {
        GPBUtil::checkString($var, True);
        $this->index_name = $var;

        return $this;
    }

    /**
     * TODO: reserve after migration
     *
     * Generated from protobuf field <code>repeated string ids = 2;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getIds()
    {
        return $this->ids;
    }

    /**
     * TODO: reserve after migration
     *
     * Generated from protobuf field <code>repeated string ids = 2;</code>
     * @param string[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setIds($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::STRING);
        $this->ids = $arr;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.vectorindex._FilterExpression filter = 3;</code>
     * @return \Vectorindex\_FilterExpression|null
     */
    public function getFilter()
    {
        return $this->filter;
    }

    public function hasFilter()
    {
        return isset($this->filter);
    }

    public function clearFilter()
    {
        unset($this->filter);
    }

    /**
     * Generated from protobuf field <code>.vectorindex._FilterExpression filter = 3;</code>
     * @param \Vectorindex\_FilterExpression $var
     * @return $this
     */
    public function setFilter($var)
    {
        GPBUtil::checkMessage($var, \Vectorindex\_FilterExpression::class);
        $this->filter = $var;

        return $this;
    }

}

