<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: controlclient.proto

namespace Control_client;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>control_client._CreateCacheRequest</code>
 */
class _CreateCacheRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string cache_name = 1;</code>
     */
    protected $cache_name = '';

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $cache_name
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Controlclient::initOnce();
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

}

