<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: leaderboard.proto

namespace Leaderboard;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>leaderboard._DeleteLeaderboardRequest</code>
 */
class _DeleteLeaderboardRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string leaderboard = 2;</code>
     */
    protected $leaderboard = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $leaderboard
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

}

