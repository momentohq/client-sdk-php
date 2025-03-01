<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: leaderboard.proto

namespace Leaderboard;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>leaderboard._GetByScoreRequest</code>
 */
class _GetByScoreRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string leaderboard = 2;</code>
     */
    protected $leaderboard = '';
    /**
     * Generated from protobuf field <code>.leaderboard._ScoreRange score_range = 3;</code>
     */
    protected $score_range = null;
    /**
     * Where should we start returning scores from in the elements within this range?
     *
     * Generated from protobuf field <code>uint32 offset = 4;</code>
     */
    protected $offset = 0;
    /**
     * How many elements should we limit to returning? (8192 max)
     *
     * Generated from protobuf field <code>uint32 limit_elements = 5;</code>
     */
    protected $limit_elements = 0;
    /**
     * Generated from protobuf field <code>.leaderboard._Order order = 6;</code>
     */
    protected $order = 0;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $leaderboard
     *     @type \Leaderboard\_ScoreRange $score_range
     *     @type int $offset
     *           Where should we start returning scores from in the elements within this range?
     *     @type int $limit_elements
     *           How many elements should we limit to returning? (8192 max)
     *     @type int $order
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
     * Generated from protobuf field <code>.leaderboard._ScoreRange score_range = 3;</code>
     * @return \Leaderboard\_ScoreRange|null
     */
    public function getScoreRange()
    {
        return $this->score_range;
    }

    public function hasScoreRange()
    {
        return isset($this->score_range);
    }

    public function clearScoreRange()
    {
        unset($this->score_range);
    }

    /**
     * Generated from protobuf field <code>.leaderboard._ScoreRange score_range = 3;</code>
     * @param \Leaderboard\_ScoreRange $var
     * @return $this
     */
    public function setScoreRange($var)
    {
        GPBUtil::checkMessage($var, \Leaderboard\_ScoreRange::class);
        $this->score_range = $var;

        return $this;
    }

    /**
     * Where should we start returning scores from in the elements within this range?
     *
     * Generated from protobuf field <code>uint32 offset = 4;</code>
     * @return int
     */
    public function getOffset()
    {
        return $this->offset;
    }

    /**
     * Where should we start returning scores from in the elements within this range?
     *
     * Generated from protobuf field <code>uint32 offset = 4;</code>
     * @param int $var
     * @return $this
     */
    public function setOffset($var)
    {
        GPBUtil::checkUint32($var);
        $this->offset = $var;

        return $this;
    }

    /**
     * How many elements should we limit to returning? (8192 max)
     *
     * Generated from protobuf field <code>uint32 limit_elements = 5;</code>
     * @return int
     */
    public function getLimitElements()
    {
        return $this->limit_elements;
    }

    /**
     * How many elements should we limit to returning? (8192 max)
     *
     * Generated from protobuf field <code>uint32 limit_elements = 5;</code>
     * @param int $var
     * @return $this
     */
    public function setLimitElements($var)
    {
        GPBUtil::checkUint32($var);
        $this->limit_elements = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.leaderboard._Order order = 6;</code>
     * @return int
     */
    public function getOrder()
    {
        return $this->order;
    }

    /**
     * Generated from protobuf field <code>.leaderboard._Order order = 6;</code>
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

