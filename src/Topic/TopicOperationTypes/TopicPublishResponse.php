<?php
declare(strict_types=1);

namespace Momento\Topic\TopicOperationTypes;

use Momento\Cache\CacheOperationTypes\ResponseBase;

/**
 * Parent response type for a topic publish request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * TopicPublishSuccess
 * * TopicPublishError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asSuccess()) {
 *     // handle success if needed
 * } elseif ($error = $response->asError()) {
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class TopicPublishResponse extends ResponseBase
{
    /**
     * @return TopicPublishSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?TopicPublishSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return TopicPublishError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?TopicPublishError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}
