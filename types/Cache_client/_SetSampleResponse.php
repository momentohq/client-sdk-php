<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: cacheclient.proto

namespace Cache_client;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>cache_client._SetSampleResponse</code>
 */
class _SetSampleResponse extends \Google\Protobuf\Internal\Message
{
    protected $set;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Cache_client\_SetSampleResponse\_Found $found
     *     @type \Cache_client\_SetSampleResponse\_Missing $missing
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Cacheclient::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>.cache_client._SetSampleResponse._Found found = 1;</code>
     * @return \Cache_client\_SetSampleResponse\_Found|null
     */
    public function getFound()
    {
        return $this->readOneof(1);
    }

    public function hasFound()
    {
        return $this->hasOneof(1);
    }

    /**
     * Generated from protobuf field <code>.cache_client._SetSampleResponse._Found found = 1;</code>
     * @param \Cache_client\_SetSampleResponse\_Found $var
     * @return $this
     */
    public function setFound($var)
    {
        GPBUtil::checkMessage($var, \Cache_client\_SetSampleResponse\_Found::class);
        $this->writeOneof(1, $var);

        return $this;
    }

    /**
     * Generated from protobuf field <code>.cache_client._SetSampleResponse._Missing missing = 2;</code>
     * @return \Cache_client\_SetSampleResponse\_Missing|null
     */
    public function getMissing()
    {
        return $this->readOneof(2);
    }

    public function hasMissing()
    {
        return $this->hasOneof(2);
    }

    /**
     * Generated from protobuf field <code>.cache_client._SetSampleResponse._Missing missing = 2;</code>
     * @param \Cache_client\_SetSampleResponse\_Missing $var
     * @return $this
     */
    public function setMissing($var)
    {
        GPBUtil::checkMessage($var, \Cache_client\_SetSampleResponse\_Missing::class);
        $this->writeOneof(2, $var);

        return $this;
    }

    /**
     * @return string
     */
    public function getSet()
    {
        return $this->whichOneof("set");
    }

}

