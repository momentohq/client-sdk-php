<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: webhook.proto

namespace Webhook;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>webhook._Webhook</code>
 */
class _Webhook extends \Google\Protobuf\Internal\Message
{
    /**
     * Generated from protobuf field <code>.webhook._WebhookId webhook_id = 1;</code>
     */
    protected $webhook_id = null;
    /**
     * Generated from protobuf field <code>string topic_name = 2;</code>
     */
    protected $topic_name = '';
    /**
     * Generated from protobuf field <code>.webhook._WebhookDestination destination = 3;</code>
     */
    protected $destination = null;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Webhook\_WebhookId $webhook_id
     *     @type string $topic_name
     *     @type \Webhook\_WebhookDestination $destination
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Webhook::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>.webhook._WebhookId webhook_id = 1;</code>
     * @return \Webhook\_WebhookId|null
     */
    public function getWebhookId()
    {
        return $this->webhook_id;
    }

    public function hasWebhookId()
    {
        return isset($this->webhook_id);
    }

    public function clearWebhookId()
    {
        unset($this->webhook_id);
    }

    /**
     * Generated from protobuf field <code>.webhook._WebhookId webhook_id = 1;</code>
     * @param \Webhook\_WebhookId $var
     * @return $this
     */
    public function setWebhookId($var)
    {
        GPBUtil::checkMessage($var, \Webhook\_WebhookId::class);
        $this->webhook_id = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>string topic_name = 2;</code>
     * @return string
     */
    public function getTopicName()
    {
        return $this->topic_name;
    }

    /**
     * Generated from protobuf field <code>string topic_name = 2;</code>
     * @param string $var
     * @return $this
     */
    public function setTopicName($var)
    {
        GPBUtil::checkString($var, True);
        $this->topic_name = $var;

        return $this;
    }

    /**
     * Generated from protobuf field <code>.webhook._WebhookDestination destination = 3;</code>
     * @return \Webhook\_WebhookDestination|null
     */
    public function getDestination()
    {
        return $this->destination;
    }

    public function hasDestination()
    {
        return isset($this->destination);
    }

    public function clearDestination()
    {
        unset($this->destination);
    }

    /**
     * Generated from protobuf field <code>.webhook._WebhookDestination destination = 3;</code>
     * @param \Webhook\_WebhookDestination $var
     * @return $this
     */
    public function setDestination($var)
    {
        GPBUtil::checkMessage($var, \Webhook\_WebhookDestination::class);
        $this->destination = $var;

        return $this;
    }

}

