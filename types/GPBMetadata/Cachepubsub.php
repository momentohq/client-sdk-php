<?php
# Generated by the protocol buffer compiler.  DO NOT EDIT!
# source: cachepubsub.proto

namespace GPBMetadata;

class Cachepubsub
{
    public static $is_initialized = false;

    public static function initOnce() {
        $pool = \Google\Protobuf\Internal\DescriptorPool::getGeneratedPool();

        if (static::$is_initialized == true) {
          return;
        }
        \GPBMetadata\Common::initOnce();
        \GPBMetadata\Extensions::initOnce();
        $pool->internalAddGeneratedFile(
            '
�
cachepubsub.protocache_client.pubsubextensions.proto"k
_PublishRequest

cache_name (	
topic (	/
value (2 .cache_client.pubsub._TopicValue:�� "
_SubscriptionRequest

cache_name (	
topic (	\'
resume_at_topic_sequence_number (
sequence_page (:��"�
_SubscriptionItem/
item (2.cache_client.pubsub._TopicItemH <
discontinuity (2#.cache_client.pubsub._DiscontinuityH 4
	heartbeat (2.cache_client.pubsub._HeartbeatH B
kind"�

_TopicItem
topic_sequence_number (/
value (2 .cache_client.pubsub._TopicValue
publisher_id (	
sequence_page ("7
_TopicValue
text (	H 
binary (H B
kind"d
_Discontinuity
last_topic_sequence (
new_topic_sequence (
new_sequence_page ("

_Heartbeat2�
Pubsub?
Publish$.cache_client.pubsub._PublishRequest.common._Empty`
	Subscribe).cache_client.pubsub._SubscriptionRequest&.cache_client.pubsub._SubscriptionItem0Br
grpc.cache_client.pubsubPZ0github.com/momentohq/client-sdk-go;client_sdk_go�!Momento.Protos.CacheClient.Pubsubbproto3'
        , true);

        static::$is_initialized = true;
    }
}

