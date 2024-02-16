<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: leaderboard.proto

namespace Leaderboard;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>leaderboard._GetByRankRequest</code>
 */
class _GetByRankRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string cache_name = 1;</code>
     */
    protected $cache_name = '';
    /**
     * Generated from protobuf field <code>string leaderboard = 2;</code>
     */
    protected $leaderboard = '';
    /**
     * Generated from protobuf field <code>.leaderboard._RankRange rank_range = 3;</code>
     */
    protected $rank_range = null;
    /**
     * Generated from protobuf field <code>.leaderboard._Order order = 4;</code>
     */
    protected $order = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $cache_name
     *     @type string $leaderboard
     *     @type \Leaderboard\_RankRange $rank_range
     *     @type int $order
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Leaderboard::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>string cache_name = 1;</code>
     * @return string
     */
    public function getCacheName()
    {
        return $this->cache_name;
    }

    /**
     * Generated from protobuf field <code>string cache_name = 1;</code>
     * @param string $var
     * @return $this
     */
    public function setCacheName($var)
    {
        GPBUtil::checkString($var, True);
        $this->cache_name = $var;

        return $this;
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
     * Generated from protobuf field <code>.leaderboard._RankRange rank_range = 3;</code>
     * @return \Leaderboard\_RankRange|null
     */
    public function getRankRange()
    {
        return $this->rank_range;
    }

    public function hasRankRange()
    {
        return isset($this->rank_range);
    }

    public function clearRankRange()
    {
        unset($this->rank_range);
    }

    /**
     * Generated from protobuf field <code>.leaderboard._RankRange rank_range = 3;</code>
     * @param \Leaderboard\_RankRange $var
     * @return $this
     */
    public function setRankRange($var)
    {
        GPBUtil::checkMessage($var, \Leaderboard\_RankRange::class);
        $this->rank_range = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.leaderboard._Order order = 4;</code>
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Generated from protobuf field <code>.leaderboard._Order order = 4;</code>
     * @param int $var
     * @return $this
     */
    public function setOrder($var)
    {
        GPBUtil::checkEnum($var, \Leaderboard\_Order::class);
        $this->order = $var;

        return $this;
    }

}

