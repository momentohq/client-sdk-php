<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: permissionmessages.proto

namespace Permission_messages;

use Google\Protobuf\Internal\GPBType;
use Google\Protobuf\Internal\RepeatedField;
use Google\Protobuf\Internal\GPBUtil;

/**
 * Generated from protobuf message <code>permission_messages.PermissionsType</code>
 */
class PermissionsType extends \Google\Protobuf\Internal\Message
{
    protected $kind;

    /**
     * Constructor.
     *
     * @param array $data {
     *     Optional. Data for populating the Message object.
     *
     *     @type \Permission_messages\PermissionsType\CachePermissions $cache_permissions
     *     @type \Permission_messages\PermissionsType\TopicPermissions $topic_permissions
     * }
     */
    public function __construct($data = NULL) {
        \GPBMetadata\Permissionmessages::initOnce();
        parent::__construct($data);
    }

    /**
     * Generated from protobuf field <code>.permission_messages.PermissionsType.CachePermissions cache_permissions = 1;</code>
     * @return \Permission_messages\PermissionsType\CachePermissions|null
     */
    public function getCachePermissions()
    {
        return $this->readOneof(1);
    }

    public function hasCachePermissions()
    {
        return $this->hasOneof(1);
    }

    /**
     * Generated from protobuf field <code>.permission_messages.PermissionsType.CachePermissions cache_permissions = 1;</code>
     * @param \Permission_messages\PermissionsType\CachePermissions $var
     * @return $this
     */
    public function setCachePermissions($var)
    {
        GPBUtil::checkMessage($var, \Permission_messages\PermissionsType\CachePermissions::class);
        $this->writeOneof(1, $var);

        return $this;
    }

    /**
     * Generated from protobuf field <code>.permission_messages.PermissionsType.TopicPermissions topic_permissions = 2;</code>
     * @return \Permission_messages\PermissionsType\TopicPermissions|null
     */
    public function getTopicPermissions()
    {
        return $this->readOneof(2);
    }

    public function hasTopicPermissions()
    {
        return $this->hasOneof(2);
    }

    /**
     * Generated from protobuf field <code>.permission_messages.PermissionsType.TopicPermissions topic_permissions = 2;</code>
     * @param \Permission_messages\PermissionsType\TopicPermissions $var
     * @return $this
     */
    public function setTopicPermissions($var)
    {
        GPBUtil::checkMessage($var, \Permission_messages\PermissionsType\TopicPermissions::class);
        $this->writeOneof(2, $var);

        return $this;
    }

    /**
     * @return string
     */
    public function getKind()
    {
        return $this->whichOneof("kind");
    }

}
