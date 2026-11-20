<?php
declare(strict_types=1);

namespace Momento\Auth\AuthOperationTypes;

use Momento\Cache\CacheOperationTypes\ErrorBody;

/**
 * Contains information about an error returned from the request.
 */
class GenerateDisposableTokenError extends GenerateDisposableTokenResponse
{
    use ErrorBody;
}
