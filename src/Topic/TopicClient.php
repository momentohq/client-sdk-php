<?php
declare(strict_types=1);

namespace Momento\Topic;

use Momento\Auth\ICredentialProvider;
use Momento\Cache\CacheOperationTypes\ResponseFuture;
use Momento\Config\IConfiguration;
use Momento\Topic\Internal\ScsTopicClient;
use Momento\Topic\TopicOperationTypes\TopicPublishResponse;

/**
 * Client to publish values to Momento Topics.
 */
class TopicClient
{
    private ScsTopicClient $topicClient;

    /**
     * @param IConfiguration $configuration Configuration to use for transport.
     * @param ICredentialProvider $authProvider Momento authentication provider.
     */
    public function __construct(IConfiguration $configuration, ICredentialProvider $authProvider)
    {
        $this->topicClient = new ScsTopicClient($configuration, $authProvider);
    }

    /**
     * Close the client and free up all associated resources. NOTE: the client object will not be usable after calling
     * this method.
     */
    public function close(): void
    {
        $this->topicClient->close();
    }

    /**
     * Publishes a text value to a topic.
     *
     * @param string $cacheName Name of the cache that contains the topic.
     * @param string $topicName Name of the topic to publish to.
     * @param string $value The UTF-8 text to publish.
     * @return ResponseFuture<TopicPublishResponse> A waitable future which will provide
     * the result of the publish operation upon a blocking call to wait:<br />
     * <code>$response = $responseFuture->wait();</code><br />
     * The response represents the result of the publish operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * TopicPublishSuccess<br>
     * * TopicPublishError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>
     * if ($error = $response->asError()) {
     *   // handle error condition
     * }
     * </code>
     * If inspection of the response is not required, one need not call wait as
     * we implicitly wait for completion of the request on destruction of the
     * response future.
     */
    public function publishAsync(string $cacheName, string $topicName, string $value): ResponseFuture
    {
        return $this->topicClient->publish($cacheName, $topicName, $value);
    }

    /**
     * Publishes a text value to a topic.
     *
     * @param string $cacheName Name of the cache that contains the topic.
     * @param string $topicName Name of the topic to publish to.
     * @param string $value The UTF-8 text to publish.
     * @return TopicPublishResponse Represents the result of the publish operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * TopicPublishSuccess<br>
     * * TopicPublishError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>
     * if ($error = $response->asError()) {
     *   // handle error condition
     * }
     * </code>
     */
    public function publish(string $cacheName, string $topicName, string $value): TopicPublishResponse
    {
        return $this->publishAsync($cacheName, $topicName, $value)->wait();
    }

    /**
     * Publishes a binary value to a topic.
     *
     * @param string $cacheName Name of the cache that contains the topic.
     * @param string $topicName Name of the topic to publish to.
     * @param string $value The bytes to publish.
     * @return ResponseFuture<TopicPublishResponse> A waitable future which will provide
     * the result of the publish operation upon a blocking call to wait:<br />
     * <code>$response = $responseFuture->wait();</code><br />
     * The response represents the result of the publish operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * TopicPublishSuccess<br>
     * * TopicPublishError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>
     * if ($error = $response->asError()) {
     *   // handle error condition
     * }
     * </code>
     * If inspection of the response is not required, one need not call wait as
     * we implicitly wait for completion of the request on destruction of the
     * response future.
     */
    public function publishBinaryAsync(string $cacheName, string $topicName, string $value): ResponseFuture
    {
        return $this->topicClient->publishBinary($cacheName, $topicName, $value);
    }

    /**
     * Publishes a binary value to a topic.
     *
     * @param string $cacheName Name of the cache that contains the topic.
     * @param string $topicName Name of the topic to publish to.
     * @param string $value The bytes to publish.
     * @return TopicPublishResponse Represents the result of the publish operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * TopicPublishSuccess<br>
     * * TopicPublishError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>
     * if ($error = $response->asError()) {
     *   // handle error condition
     * }
     * </code>
     */
    public function publishBinary(string $cacheName, string $topicName, string $value): TopicPublishResponse
    {
        return $this->publishBinaryAsync($cacheName, $topicName, $value)->wait();
    }
}
