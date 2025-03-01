<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: leaderboard.proto

namespace Leaderboard;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>leaderboard._RemoveElementsRequest</code>
 */
class _RemoveElementsRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string leaderboard = 2;</code>
     */
    protected $leaderboard = '';
    /**
     * You can have up to 8192 ids in this list.
     *
     * Generated from protobuf field <code>repeated uint32 ids = 3;</code>
     */
    private $ids;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $leaderboard
     *     @type int[]|\Google\Protobuf\Internal\RepeatedField $ids
     *           You can have up to 8192 ids in this list.
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Leaderboard::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string leaderboard = 2;</code>
     * @return string
     */
    public function getLeaderboard()
    {
        return $this->leaderboard;
    }

    /**
     * Generated from protobuf field <code>string leaderboard = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setLeaderboard($var)
    {
        GPBUtil::checkString($var, True);
        $this->leaderboard = $var;

        return $this;
    }

    /**
     * You can have up to 8192 ids in this list.
     *
     * Generated from protobuf field <code>repeated uint32 ids = 3;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getIds()
    {
        return $this->ids;
    }

    /**
     * You can have up to 8192 ids in this list.
     *
     * Generated from protobuf field <code>repeated uint32 ids = 3;</code>
     * @param int[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setIds($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::UINT32);
        $this->ids = $arr;

        return $this;
    }

}

