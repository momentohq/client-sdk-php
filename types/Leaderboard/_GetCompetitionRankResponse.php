<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: leaderboard.proto

namespace Leaderboard;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>leaderboard._GetCompetitionRankResponse</code>
 */
class _GetCompetitionRankResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>repeated .leaderboard._RankedElement elements = 1;</code>
     */
    private $elements;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Leaderboard\_RankedElement[]|\Google\Protobuf\Internal\RepeatedField $elements
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Leaderboard::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>repeated .leaderboard._RankedElement elements = 1;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getElements()
    {
        return $this->elements;
    }

    /**
     * Generated from protobuf field <code>repeated .leaderboard._RankedElement elements = 1;</code>
     * @param \Leaderboard\_RankedElement[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setElements($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Leaderboard\_RankedElement::class);
        $this->elements = $arr;

        return $this;
    }

}

