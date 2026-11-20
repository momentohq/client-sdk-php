<?php
declare(strict_types=1);

namespace Momento\Topic\TopicOperationTypes;

use Momento\Cache\CacheOperationTypes\ErrorBody;

/**
 * Contains information about an error returned from the request.
 */
class TopicPublishError extends TopicPublishResponse
{
    use ErrorBody;
}
