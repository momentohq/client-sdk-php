<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: controlclient.proto

namespace Control_client;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>control_client._SimilarityMetric</code>
 */
class _SimilarityMetric extends \Google\Protobuf\Internal\Message
{
    protected $similarity_metric;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Control_client\_SimilarityMetric\_EuclideanSimilarity $euclidean_similarity
     *     @type \Control_client\_SimilarityMetric\_InnerProduct $inner_product
     *     @type \Control_client\_SimilarityMetric\_CosineSimilarity $cosine_similarity
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Controlclient::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>.control_client._SimilarityMetric._EuclideanSimilarity euclidean_similarity = 1;</code>
     * @return \Control_client\_SimilarityMetric\_EuclideanSimilarity|null
     */
    public function getEuclideanSimilarity()
    {
        return $this->readOneof(1);
    }

    public function hasEuclideanSimilarity()
    {
        return $this->hasOneof(1);
    }

    /**
     * Generated from protobuf field <code>.control_client._SimilarityMetric._EuclideanSimilarity euclidean_similarity = 1;</code>
     * @param \Control_client\_SimilarityMetric\_EuclideanSimilarity $var
     * @return $this
     */
    public function setEuclideanSimilarity($var)
    {
        GPBUtil::checkMessage($var, \Control_client\_SimilarityMetric\_EuclideanSimilarity::class);
        $this->writeOneof(1, $var);

        return $this;
    }

    /**
     * Generated from protobuf field <code>.control_client._SimilarityMetric._InnerProduct inner_product = 2;</code>
     * @return \Control_client\_SimilarityMetric\_InnerProduct|null
     */
    public function getInnerProduct()
    {
        return $this->readOneof(2);
    }

    public function hasInnerProduct()
    {
        return $this->hasOneof(2);
    }

    /**
     * Generated from protobuf field <code>.control_client._SimilarityMetric._InnerProduct inner_product = 2;</code>
     * @param \Control_client\_SimilarityMetric\_InnerProduct $var
     * @return $this
     */
    public function setInnerProduct($var)
    {
        GPBUtil::checkMessage($var, \Control_client\_SimilarityMetric\_InnerProduct::class);
        $this->writeOneof(2, $var);

        return $this;
    }

    /**
     * Generated from protobuf field <code>.control_client._SimilarityMetric._CosineSimilarity cosine_similarity = 3;</code>
     * @return \Control_client\_SimilarityMetric\_CosineSimilarity|null
     */
    public function getCosineSimilarity()
    {
        return $this->readOneof(3);
    }

    public function hasCosineSimilarity()
    {
        return $this->hasOneof(3);
    }

    /**
     * Generated from protobuf field <code>.control_client._SimilarityMetric._CosineSimilarity cosine_similarity = 3;</code>
     * @param \Control_client\_SimilarityMetric\_CosineSimilarity $var
     * @return $this
     */
    public function setCosineSimilarity($var)
    {
        GPBUtil::checkMessage($var, \Control_client\_SimilarityMetric\_CosineSimilarity::class);
        $this->writeOneof(3, $var);

        return $this;
    }

    /**
     * @return string
     */
    public function getSimilarityMetric()
    {
        return $this->whichOneof("similarity_metric");
    }

}

