<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: vectorindex.proto

namespace Vectorindex;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>vectorindex._ItemResponse</code>
 */
class _ItemResponse extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string id = 3;</code>
     */
    protected $id = '';
    /**
     * Generated from protobuf field <code>.vectorindex._Vector vector = 4;</code>
     */
    protected $vector = null;
    /**
     * Generated from protobuf field <code>repeated .vectorindex._Metadata metadata = 5;</code>
     */
    private $metadata;
    protected $response;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Vectorindex\_ItemResponse\_Miss $miss
     *           TODO: reserve after migration
     *     @type \Vectorindex\_ItemResponse\_Hit $hit
     *           TODO: reserve after migration
     *     @type string $id
     *     @type \Vectorindex\_Vector $vector
     *     @type \Vectorindex\_Metadata[]|\Google\Protobuf\Internal\RepeatedField $metadata
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Vectorindex::initOnce();
        parent::__construct($data);
    }

    /**
     * TODO: reserve after migration
     *
     * Generated from protobuf field <code>.vectorindex._ItemResponse._Miss miss = 1;</code>
     * @return \Vectorindex\_ItemResponse\_Miss|null
     */
    public function getMiss()
    {
        return $this->readOneof(1);
    }

    public function hasMiss()
    {
        return $this->hasOneof(1);
    }

    /**
     * TODO: reserve after migration
     *
     * Generated from protobuf field <code>.vectorindex._ItemResponse._Miss miss = 1;</code>
     * @param \Vectorindex\_ItemResponse\_Miss $var
     * @return $this
     */
    public function setMiss($var)
    {
        GPBUtil::checkMessage($var, \Vectorindex\_ItemResponse\_Miss::class);
        $this->writeOneof(1, $var);

        return $this;
    }

    /**
     * TODO: reserve after migration
     *
     * Generated from protobuf field <code>.vectorindex._ItemResponse._Hit hit = 2;</code>
     * @return \Vectorindex\_ItemResponse\_Hit|null
     */
    public function getHit()
    {
        return $this->readOneof(2);
    }

    public function hasHit()
    {
        return $this->hasOneof(2);
    }

    /**
     * TODO: reserve after migration
     *
     * Generated from protobuf field <code>.vectorindex._ItemResponse._Hit hit = 2;</code>
     * @param \Vectorindex\_ItemResponse\_Hit $var
     * @return $this
     */
    public function setHit($var)
    {
        GPBUtil::checkMessage($var, \Vectorindex\_ItemResponse\_Hit::class);
        $this->writeOneof(2, $var);

        return $this;
    }

    /**
     * Generated from protobuf field <code>string id = 3;</code>
     * @return string
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * Generated from protobuf field <code>string id = 3;</code>
     * @param string $var
     * @return $this
     */
    public function setId($var)
    {
        GPBUtil::checkString($var, True);
        $this->id = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.vectorindex._Vector vector = 4;</code>
     * @return \Vectorindex\_Vector|null
     */
    public function getVector()
    {
        return $this->vector;
    }

    public function hasVector()
    {
        return isset($this->vector);
    }

    public function clearVector()
    {
        unset($this->vector);
    }

    /**
     * Generated from protobuf field <code>.vectorindex._Vector vector = 4;</code>
     * @param \Vectorindex\_Vector $var
     * @return $this
     */
    public function setVector($var)
    {
        GPBUtil::checkMessage($var, \Vectorindex\_Vector::class);
        $this->vector = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>repeated .vectorindex._Metadata metadata = 5;</code>
     * @return \Google\Protobuf\Internal\RepeatedField
     */
    public function getMetadata()
    {
        return $this->metadata;
    }

    /**
     * Generated from protobuf field <code>repeated .vectorindex._Metadata metadata = 5;</code>
     * @param \Vectorindex\_Metadata[]|\Google\Protobuf\Internal\RepeatedField $var
     * @return $this
     */
    public function setMetadata($var)
    {
        $arr = GPBUtil::checkRepeatedField($var, \Google\Protobuf\Internal\GPBType::MESSAGE, \Vectorindex\_Metadata::class);
        $this->metadata = $arr;

        return $this;
    }

    /**
     * @return string
     */
    public function getResponse()
    {
        return $this->whichOneof("response");
    }

}

