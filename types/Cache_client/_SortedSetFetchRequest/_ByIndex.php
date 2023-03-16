<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: cacheclient.proto

namespace Cache_client\_SortedSetFetchRequest;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Start and end are zero-based indexes, with 0 being the first element.
 * A negative index indicates offsets from the end of the sorted set, with
 * -1 being the last element.
 *
 * Generated from protobuf message <code>cache_client._SortedSetFetchRequest._ByIndex</code>
 */
class _ByIndex extends \Google\Protobuf\Internal\Message
{
    protected $start;
    protected $end;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Cache_client\_Unbounded $unbounded_start
     *     @type int $inclusive_start_index
     *     @type \Cache_client\_Unbounded $unbounded_end
     *     @type int $exclusive_end_index
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Cacheclient::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>.cache_client._Unbounded unbounded_start = 1;</code>
     * @return \Cache_client\_Unbounded|null
     */
    public function getUnboundedStart()
    {
        return $this->readOneof(1);
    }

    public function hasUnboundedStart()
    {
        return $this->hasOneof(1);
    }

    /**
     * Generated from protobuf field <code>.cache_client._Unbounded unbounded_start = 1;</code>
     * @param \Cache_client\_Unbounded $var
     * @return $this
     */
    public function setUnboundedStart($var)
    {
        GPBUtil::checkMessage($var, \Cache_client\_Unbounded::class);
        $this->writeOneof(1, $var);

        return $this;
    }

    /**
     * Generated from protobuf field <code>sint32 inclusive_start_index = 2;</code>
     * @return int
     */
    public function getInclusiveStartIndex()
    {
        return $this->readOneof(2);
    }

    public function hasInclusiveStartIndex()
    {
        return $this->hasOneof(2);
    }

    /**
     * Generated from protobuf field <code>sint32 inclusive_start_index = 2;</code>
     * @param int $var
     * @return $this
     */
    public function setInclusiveStartIndex($var)
    {
        GPBUtil::checkInt32($var);
        $this->writeOneof(2, $var);

        return $this;
    }

    /**
     * Generated from protobuf field <code>.cache_client._Unbounded unbounded_end = 3;</code>
     * @return \Cache_client\_Unbounded|null
     */
    public function getUnboundedEnd()
    {
        return $this->readOneof(3);
    }

    public function hasUnboundedEnd()
    {
        return $this->hasOneof(3);
    }

    /**
     * Generated from protobuf field <code>.cache_client._Unbounded unbounded_end = 3;</code>
     * @param \Cache_client\_Unbounded $var
     * @return $this
     */
    public function setUnboundedEnd($var)
    {
        GPBUtil::checkMessage($var, \Cache_client\_Unbounded::class);
        $this->writeOneof(3, $var);

        return $this;
    }

    /**
     * Generated from protobuf field <code>sint32 exclusive_end_index = 4;</code>
     * @return int
     */
    public function getExclusiveEndIndex()
    {
        return $this->readOneof(4);
    }

    public function hasExclusiveEndIndex()
    {
        return $this->hasOneof(4);
    }

    /**
     * Generated from protobuf field <code>sint32 exclusive_end_index = 4;</code>
     * @param int $var
     * @return $this
     */
    public function setExclusiveEndIndex($var)
    {
        GPBUtil::checkInt32($var);
        $this->writeOneof(4, $var);

        return $this;
    }

    /**
     * @return string
     */
    public function getStart()
    {
        return $this->whichOneof("start");
    }

    /**
     * @return string
     */
    public function getEnd()
    {
        return $this->whichOneof("end");
    }

}

// Adding a class alias for backwards compatibility with the previous class name.
class_alias(_ByIndex::class, \Cache_client\_SortedSetFetchRequest__ByIndex::class);

