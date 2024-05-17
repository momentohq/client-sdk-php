<?php
// GENERATED CODE -- DO NOT EDIT!

namespace Cache_client\Pubsub;

/**
 * For working with topics in a cache.
 * Momento topics are conceptually located on a cache. They are best-effort multicast.
 * To use them, create a cache then start subscribing and publishing!
 *
 * Momento topic subscriptions try to give you information about the quality of the
 *   stream you are receiving. For example, you might miss messages if your network
 *   is slow, or if some intermediate switch fails, or due to rate limiting. It is
 *   also possible, though we try to avoid it, that messages could briefly come out
 *   of order between subscribers.
 *   We try to tell you when things like this happen via a Discontinuity in your
 *   subscription stream. If you do not care about occasional discontinuities then
 *   don't bother handling them! You might still want to log them just in case ;-)
 */
class PubsubClient extends \Grpc\BaseStub {

    /**
     * @param string $hostname hostname
     * @param array $opts channel options
     * @param \Grpc\Channel $channel (optional) re-use channel object
     */
    public function __construct($hostname, $opts, $channel = null) {
        parent::__construct($hostname, $opts, $channel);
    }

    /**
     * Publish a message to a topic.
     *
     * If a topic has no subscribers, then the effect of Publish MAY be either of:
     * * It is dropped and the topic is nonexistent.
     * * It is accepted to the topic as the next message.
     *
     * Publish() does not wait for subscribers to accept. It returns Ok upon accepting
     * the topic value. It also returns Ok if there are no subscribers and the value
     * happens to be dropped. Publish() can not guarantee delivery in theory but in
     * practice it should almost always deliver to subscribers.
     *
     * REQUIRES HEADER authorization: Momento auth token
     * @param \Cache_client\Pubsub\_PublishRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\UnaryCall
     */
    public function Publish(\Cache_client\Pubsub\_PublishRequest $argument,
      $metadata = [], $options = []) {
        return $this->_simpleRequest('/cache_client.pubsub.Pubsub/Publish',
        $argument,
        ['\Common\_Empty', 'decode'],
        $metadata, $options);
    }

    /**
     * Subscribe to notifications from a topic.
     *
     * You will receive a stream of values and (hopefully occasional) discontinuities.
     * Values will appear as copies of the payloads you Publish() to the topic.
     *
     * REQUIRES HEADER authorization: Momento auth token
     * @param \Cache_client\Pubsub\_SubscriptionRequest $argument input argument
     * @param array $metadata metadata
     * @param array $options call options
     * @return \Grpc\ServerStreamingCall
     */
    public function Subscribe(\Cache_client\Pubsub\_SubscriptionRequest $argument,
      $metadata = [], $options = []) {
        return $this->_serverStreamRequest('/cache_client.pubsub.Pubsub/Subscribe',
        $argument,
        ['\Cache_client\Pubsub\_SubscriptionItem', 'decode'],
        $metadata, $options);
    }

}
