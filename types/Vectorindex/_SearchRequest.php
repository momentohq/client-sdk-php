<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: vectorindex.proto

namespace Vectorindex;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>vectorindex._SearchRequest</code>
 */
class _SearchRequest extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>string index_name = 1;</code>
     */
    protected $index_name = '';
    /**
     * Generated from protobuf field <code>uint32 top_k = 2;</code>
     */
    protected $top_k = 0;
    /**
     * Generated from protobuf field <code>.vectorindex._Vector query_vector = 3;</code>
     */
    protected $query_vector = null;
    /**
     * Generated from protobuf field <code>.vectorindex._MetadataRequest metadata_fields = 4;</code>
     */
    protected $metadata_fields = null;
    /**
     * Generated from protobuf field <code>.vectorindex._FilterExpression filter = 7;</code>
     */
    protected $filter = null;
    protected $threshold;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type string $index_name
     *     @type int $top_k
     *     @type \Vectorindex\_Vector $query_vector
     *     @type \Vectorindex\_MetadataRequest $metadata_fields
     *     @type float $score_threshold
     *     @type \Vectorindex\_NoScoreThreshold $no_score_threshold
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
     * Generated from protobuf field <code>uint32 top_k = 2;</code>
     * @return int
     */
    public function getTopK()
    {
        return $this->top_k;
    }

    /**
     * Generated from protobuf field <code>uint32 top_k = 2;</code>
     * @param int $var
     * @return $this
     */
    public function setTopK($var)
    {
        GPBUtil::checkUint32($var);
        $this->top_k = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.vectorindex._Vector query_vector = 3;</code>
     * @return \Vectorindex\_Vector|null
     */
    public function getQueryVector()
    {
        return $this->query_vector;
    }

    public function hasQueryVector()
    {
        return isset($this->query_vector);
    }

    public function clearQueryVector()
    {
        unset($this->query_vector);
    }

    /**
     * Generated from protobuf field <code>.vectorindex._Vector query_vector = 3;</code>
     * @param \Vectorindex\_Vector $var
     * @return $this
     */
    public function setQueryVector($var)
    {
        GPBUtil::checkMessage($var, \Vectorindex\_Vector::class);
        $this->query_vector = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.vectorindex._MetadataRequest metadata_fields = 4;</code>
     * @return \Vectorindex\_MetadataRequest|null
     */
    public function getMetadataFields()
    {
        return $this->metadata_fields;
    }

    public function hasMetadataFields()
    {
        return isset($this->metadata_fields);
    }

    public function clearMetadataFields()
    {
        unset($this->metadata_fields);
    }

    /**
     * Generated from protobuf field <code>.vectorindex._MetadataRequest metadata_fields = 4;</code>
     * @param \Vectorindex\_MetadataRequest $var
     * @return $this
     */
    public function setMetadataFields($var)
    {
        GPBUtil::checkMessage($var, \Vectorindex\_MetadataRequest::class);
        $this->metadata_fields = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>float score_threshold = 5;</code>
     * @return float
     */
    public function getScoreThreshold()
    {
        return $this->readOneof(5);
    }

    public function hasScoreThreshold()
    {
        return $this->hasOneof(5);
    }

    /**
     * Generated from protobuf field <code>float score_threshold = 5;</code>
     * @param float $var
     * @return $this
     */
    public function setScoreThreshold($var)
    {
        GPBUtil::checkFloat($var);
        $this->writeOneof(5, $var);

        return $this;
    }

    /**
     * Generated from protobuf field <code>.vectorindex._NoScoreThreshold no_score_threshold = 6;</code>
     * @return \Vectorindex\_NoScoreThreshold|null
     */
    public function getNoScoreThreshold()
    {
        return $this->readOneof(6);
    }

    public function hasNoScoreThreshold()
    {
        return $this->hasOneof(6);
    }

    /**
     * Generated from protobuf field <code>.vectorindex._NoScoreThreshold no_score_threshold = 6;</code>
     * @param \Vectorindex\_NoScoreThreshold $var
     * @return $this
     */
    public function setNoScoreThreshold($var)
    {
        GPBUtil::checkMessage($var, \Vectorindex\_NoScoreThreshold::class);
        $this->writeOneof(6, $var);

        return $this;
    }

    /**
     * Generated from protobuf field <code>.vectorindex._FilterExpression filter = 7;</code>
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
     * Generated from protobuf field <code>.vectorindex._FilterExpression filter = 7;</code>
     * @param \Vectorindex\_FilterExpression $var
     * @return $this
     */
    public function setFilter($var)
    {
        GPBUtil::checkMessage($var, \Vectorindex\_FilterExpression::class);
        $this->filter = $var;

        return $this;
    }

    /**
     * @return string
     */
    public function getThreshold()
    {
        return $this->whichOneof("threshold");
    }

}

