<?php
declare(strict_types=1);

namespace Momento\Auth\AuthOperationTypes;

use Momento\Cache\CacheOperationTypes\ResponseBase;

/**
 * Parent response type for a generate disposable token request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * GenerateDisposableTokenSuccess
 * * GenerateDisposableTokenError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asSuccess()) {
 *     return $success->authToken();
 * } elseif ($error = $response->asError()) {
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class GenerateDisposableTokenResponse extends ResponseBase
{
    /**
     * @return GenerateDisposableTokenSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?GenerateDisposableTokenSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return GenerateDisposableTokenError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?GenerateDisposableTokenError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}
