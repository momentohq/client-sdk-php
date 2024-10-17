<?php
/** @noinspection PhpIncompatibleReturnTypeInspection */
/** @noinspection PhpMultipleClassesDeclarationsInOneFile */
declare(strict_types=1);

namespace Momento\Cache\CacheOperationTypes;

use Cache_client\_DictionaryFetchResponse;
use Cache_client\_DictionaryGetResponse;
use Cache_client\_DictionaryIncrementResponse;
use Cache_client\_GetResponse;
use Cache_client\_IncrementResponse;
use Cache_client\_ItemGetTtlResponse;
use Cache_client\_KeysExistResponse;
use Cache_client\_ListFetchResponse;
use Cache_client\_ListLengthResponse;
use Cache_client\_ListPopBackResponse;
use Cache_client\_ListPopFrontResponse;
use Cache_client\_ListPushBackResponse;
use Cache_client\_ListPushFrontResponse;
use Cache_client\_SetContainsResponse;
use Cache_client\_SetFetchResponse;
use Cache_client\_SetLengthResponse;
use Cache_client\_SortedSetFetchResponse;
use Cache_client\ECacheResult;
use Closure;
use Control_client\_ListCachesResponse;
use Generator;
use Momento\Cache\Errors\SdkError;
use Momento\Cache\Errors\UnknownError;
use Throwable;

trait ErrorBody
{
    private SdkError $innerException;
    private string $errorCode;
    private string $message;

    public function __construct(SdkError $error)
    {
        parent::__construct();
        $this->setError($error);
    }

    public function setError(SdkError $error) {
        $this->innerException = $error;
        $this->message = "{$error->messageWrapper}: {$error->getMessage()}";
        $this->errorCode = $error->errorCode;
    }

    /**
     * @return SdkError The exception from which the error response was created.
     */
    public function innerException(): SdkError
    {
        return $this->innerException;
    }

    /**
     * @return string The Momento error code corresponding to the error's type (ex: MomentoErrorCode::TIMEOUT_ERROR).
     */
    public function errorCode(): string
    {
        return $this->errorCode;
    }

    /**
     * @return string An explanation of conditions that caused and potential ways to resolve the error.
     */
    public function message(): string
    {
        return $this->message;
    }

    public function __toString()
    {
        return parent::__toString() . ": " . $this->message;
    }

}

class CacheInfo
{
    private string $name;

    public function __construct($grpcListedCache)
    {
        $this->name = $grpcListedCache->getCacheName();
    }

    public function name(): string
    {
        return $this->name;
    }
}

/**
 * @template T extends ResponseBase
 */
class ResponseFuture
{
    /**
     * @var (Closure(): T)|null
     */
    private ?Closure $resolver;

    /**
     * @var (Closure(): T)|null
     */
    private ?Closure $response;

    /**
     * @param (Closure(): T)|null $resolver
     * @param (Closure(): T)|null $response
     */
    private function __construct(?Closure $resolver, ?Closure $response)
    {
        $this->resolver = $resolver;
        $this->response = $response;
    }

    public function __destruct()
    {
        // if still pending, force resolution to happen. we don't bother to
        // store the response or null out the resolver because the instance is
        // already being destroyed at this point, decrementing the resolver
        // refcount (which is what setting it to null would have done)
        if (null === $this->response) {
            ($this->resolver)();
        }
    }

    /**
     * @param Closure(): T $resolver
     */
    public static function createPending(Closure $resolver): self
    {
        return new self($resolver, null);
    }

    /**
     * @param T $response
     */
    public static function createResolved(ResponseBase $response): self
    {
        return new self(null, fn () => $response);
    }

    public function isPending(): bool
    {
        return null === $this->response;
    }

    public function isResolved(): bool
    {
        return null !== $this->response;
    }

    /**
     * @return T
     */
    public function wait(): ResponseBase
    {
        if (null === $this->response) {
            try {
                $response = ($this->resolver)();

                // important to unset the resolver here so that any destructor
                // code fires and any crashes get caught, instead of this happening
                // later when our own destructor runs (assuming refcount 0)
                $this->resolver = null;

                $this->response = fn () => $response;
            } catch (Throwable $e) {
                $this->response = function () use ($e) {
                    throw $e;
                };
            }
        }

        return ($this->response)();
    }
}

abstract class ResponseBase
{
    protected string $baseType;
    protected int $valueSubstringLength = 32;

    public function __construct()
    {
        $baseTypeSuffix = "Response";
        $this->baseType = get_parent_class($this);
        // Remove the "Response" suffix from base type so we can match the subtype names.
        $this->baseType = substr($this->baseType, 0, strlen($baseTypeSuffix)*-1);
    }

    public function __toString()
    {
        return get_class($this);
    }

    protected function isError(): bool
    {
        return get_class($this) == "{$this->baseType}Error";
    }

    protected function isSuccess(): bool
    {
        return get_class($this) == "{$this->baseType}Success";
    }

    protected function isAlreadyExists(): bool
    {
        return get_class($this) == "{$this->baseType}AlreadyExists";
    }

    protected function isHit(): bool
    {
        return get_class($this) == "{$this->baseType}Hit";
    }

    protected function isMiss(): bool
    {
        return get_class($this) == "{$this->baseType}Miss";
    }

    protected function isStored(): bool
    {
        return get_class($this) == "{$this->baseType}Stored";
    }

    protected function isNotStored(): bool
    {
        return get_class($this) == "{$this->baseType}NotStored";
    }

    protected function isFound(): bool
    {
        return get_class($this) == "{$this->baseType}Found";
    }

    protected function isNotFound(): bool
    {
        return get_class($this) == "{$this->baseType}NotFound";
    }

    protected function isSet(): bool
    {
        return get_class($this) == "{$this->baseType}Set";
    }

    protected function isNotSet(): bool
    {
        return get_class($this) == "{$this->baseType}NotSet";
    }

    protected function shortValue(string $value): string
    {
        if (strlen($value) <= $this->valueSubstringLength) {
            return $value;
        }
        return mb_substr($value, 0, $this->valueSubstringLength) . "...";
    }
}

/**
 * Parent response type for a create cache request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CreateCacheSuccess
 * * CreateCacheAlreadyExists
 * * CreateCacheError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($response->asSuccess()) {
 *     // handle success if needed
 * } elseif ($response->asAlreadyExists()) {
 *     // handle already exists as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class CreateCacheResponse extends ResponseBase
{

    /**
     * @return CreateCacheSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?CreateCacheSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CreateCacheError|null Returns the error subtype if the request was successful and null otherwise.
     */
    public function asError(): ?CreateCacheError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CreateCacheAlreadyExists|null Returns the "already exists" subtype if the request was successful and null otherwise.
     */
    public function asAlreadyExists(): ?CreateCacheAlreadyExists
    {
        if ($this->isAlreadyExists()) {
            return $this;
        }
        return null;
    }

}

/**
 * Indicates that the request that generated it was successful.
 */
class CreateCacheSuccess extends CreateCacheResponse
{
}

/**
 * Indicates that a cache with the requested name has already been created in the requesting account.
 */
class CreateCacheAlreadyExists extends CreateCacheResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class CreateCacheError extends CreateCacheResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a delete cache request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * DeleteCacheSuccess
 * * DeleteCacheError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($response->asSuccess()) {
 *     // handle success if needed
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class DeleteCacheResponse extends ResponseBase
{
    /**
     * @return DeleteCacheSuccess|null  Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?DeleteCacheSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DeleteCacheError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?DeleteCacheError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Indicates that the request that generated it was successful.
 */
class DeleteCacheSuccess extends DeleteCacheResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class DeleteCacheError extends DeleteCacheResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a list caches request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *  * ListCachesSuccess
 *  * ListCachesError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asSuccess()) {
 *     return $success->caches();
 * } elseif ($response->isError()) {
 *     // handle error as appropriate
 * }
 */
abstract class ListCachesResponse extends ResponseBase
{

    /**
     * @return ListCachesSuccess|null  Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?ListCachesSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ListCachesError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?ListCachesError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }

}

/**
 * Contains information from the successful request that generated it.
 */
class ListCachesSuccess extends ListCachesResponse
{
    private string $nextToken;
    private array $caches = [];

    public function __construct(_ListCachesResponse $response)
    {
        parent::__construct();
        $this->nextToken = $response->getNextToken() ? $response->getNextToken() : "";
        foreach ($response->getCache() as $cache) {
            $this->caches[] = new CacheInfo($cache);
        }
    }

    /**
     * @return array List of caches available to the user represented as CacheInfo objects.
     */
    public function caches(): array
    {
        return $this->caches;
    }

    public function __toString()
    {
        $cacheNames = array_map(fn($i) => $i->name(), $this->caches);
        return get_class($this) . ": " . join(', ', $cacheNames);
    }
}

/**
 * Contains information about an error returned from the request.
 */
class ListCachesError extends ListCachesResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a flush cache request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * FlushCacheSuccess
 * * FlushCacheError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asSuccess()) {
 *     // handle success if needed
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class FlushCacheResponse extends ResponseBase
{
    /**
     * @return FlushCacheSuccess|null  Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?FlushCacheSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return FlushCacheError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?FlushCacheError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }

}

/**
 * Indicates that the request that generated it was successful.
 */
class FlushCacheSuccess extends FlushCacheResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class FlushCacheError extends FlushCacheResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a set request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * SetSuccess
 * * SetError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asSuccess()) {
 *     // handle success if needed
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class SetResponse extends ResponseBase
{
    /**
     * @return SetSuccess|null  Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?SetSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?SetError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Indicates that the request that generated it was successful.
 */
class SetSuccess extends SetResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class SetError extends SetResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a get request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * GetHit
 * * GetMiss
 * * GetError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asHit()) {
 *     return $success->valueString();
 * } elseif ($response->asMiss())
 *     // handle miss as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class GetResponse extends ResponseBase
{

    /**
     * @return GetHit|null Returns the hit subtype if the request returned an error and null otherwise.
     */
    public function asHit(): ?GetHit
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return GetMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): ?GetMiss
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return GetError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?GetError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Contains the result of a cache hit.
 */
class GetHit extends GetResponse
{
    private string $value;

    public function __construct(_GetResponse $grpcGetResponse)
    {
        parent::__construct();
        $this->value = $grpcGetResponse->getCacheBody();
    }

    /**
     * @return string Value returned from the cache for the specified key.
     */
    public function valueString(): string
    {
        return $this->value;
    }

    public function __toString()
    {
        return parent::__toString() . ": {$this->shortValue($this->value)}";
    }
}

/**
 * Indicates that the request that generated it was a cache miss.
 */
class GetMiss extends GetResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class GetError extends GetResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a set if present request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * SetIfPresentStored
 * * SetIfPresentNotStored
 * * SetIfPresentError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($response->asStored()) {
 *     // handle successfully stored field as appropriate
 * } elseif ($response->asNotStored())
 *     // handle not stored field as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class SetIfPresentResponse extends ResponseBase
{

    /**
     * @return SetIfPresentStored|null Returns the subtype for a successfully stored value and null otherwise.
     */
    public function asStored(): ?SetIfPresentStored
    {
       if ($this->isStored()) {
           return $this;
       }
       return null;
    }

    /**
     * @return SetIfPresentStored|null Returns the subtype indicating the value was not stored and null otherwise.
     */
    public function asNotStored(): ?SetIfPresentNotStored
    {
        if($this->isNotStored()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetIfPresentError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?SetIfPresentError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }

}

/**
 * Indicates that the value was successfully stored.
 */
class SetIfPresentStored extends SetIfPresentResponse {}

/**
 * Indicates that the value was not stored because the specified cache key already exists.
 */
class SetIfPresentNotStored extends SetIfPresentResponse {}

/**
 * Contains information about an error returned from the request.
 */
class SetIfPresentError extends SetIfPresentResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a set if present and not equal request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * SetIfPresentAndNotEqualStored
 * * SetIfPresentAndNotEqualNotStored
 * * SetIfPresentAndNotEqualError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($response->asStored()) {
 *     // handle successfully stored field as appropriate
 * } elseif ($response->asNotStored())
 *     // handle not stored field as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class SetIfPresentAndNotEqualResponse extends ResponseBase
{

    /**
     * @return SetIfPresentAndNotEqualStored|null Returns the subtype for a successfully stored value and null otherwise.
     */
    public function asStored(): ?SetIfPresentAndNotEqualStored
    {
        if ($this->isStored()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetIfPresentAndNotEqualStored|null Returns the subtype indicating the value was not stored and null otherwise.
     */
    public function asNotStored(): ?SetIfPresentAndNotEqualNotStored
    {
        if($this->isNotStored()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetIfPresentAndNotEqualError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?SetIfPresentAndNotEqualError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }

}

/**
 * Indicates that the value was successfully stored.
 */
class SetIfPresentAndNotEqualStored extends SetIfPresentAndNotEqualResponse {}

/**
 * Indicates that the value was not stored because the specified cache key already exists.
 */
class SetIfPresentAndNotEqualNotStored extends SetIfPresentAndNotEqualResponse {}

/**
 * Contains information about an error returned from the request.
 */
class SetIfPresentAndNotEqualError extends SetIfPresentAndNotEqualResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a set if absent request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * SetIfAbsentStored
 * * SetIfAbsentNotStored
 * * SetIfAbsentError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($response->asStored()) {
 *     // handle successfully stored field as appropriate
 * } elseif ($response->asNotStored())
 *     // handle not stored field as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class SetIfAbsentResponse extends ResponseBase
{

    /**
     * @return SetIfAbsentStored|null Returns the subtype for a successfully stored value and null otherwise.
     */
    public function asStored(): ?SetIfAbsentStored
    {
        if ($this->isStored()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetIfAbsentStored|null Returns the subtype indicating the value was not stored and null otherwise.
     */
    public function asNotStored(): ?SetIfAbsentNotStored
    {
        if($this->isNotStored()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetIfAbsentError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?SetIfAbsentError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }

}

/**
 * Indicates that the value was successfully stored.
 */
class SetIfAbsentStored extends SetIfAbsentResponse {}

/**
 * Indicates that the value was not stored because the specified cache key already exists.
 */
class SetIfAbsentNotStored extends SetIfAbsentResponse {}

/**
 * Contains information about an error returned from the request.
 */
class SetIfAbsentError extends SetIfAbsentResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a set if absent or equal request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * SetIfAbsentOrEqualStored
 * * SetIfAbsentOrEqualNotStored
 * * SetIfAbsentOrEqualError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($response->asStored()) {
 *     // handle successfully stored field as appropriate
 * } elseif ($response->asNotStored())
 *     // handle not stored field as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class SetIfAbsentOrEqualResponse extends ResponseBase
{

    /**
     * @return SetIfAbsentOrEqualStored|null Returns the subtype for a successfully stored value and null otherwise.
     */
    public function asStored(): ?SetIfAbsentOrEqualStored
    {
        if ($this->isStored()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetIfAbsentOrEqualStored|null Returns the subtype indicating the value was not stored and null otherwise.
     */
    public function asNotStored(): ?SetIfAbsentOrEqualNotStored
    {
        if($this->isNotStored()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetIfAbsentOrEqualError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?SetIfAbsentOrEqualError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }

}

/**
 * Indicates that the value was successfully stored.
 */
class SetIfAbsentOrEqualStored extends SetIfAbsentOrEqualResponse {}

/**
 * Indicates that the value was not stored because the specified cache key already exists.
 */
class SetIfAbsentOrEqualNotStored extends SetIfAbsentOrEqualResponse {}

/**
 * Contains information about an error returned from the request.
 */
class SetIfAbsentOrEqualError extends SetIfAbsentOrEqualResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a set if equal request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * SetIfEqualStored
 * * SetIfEqualNotStored
 * * SetIfEqualError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($response->asStored()) {
 *     // handle successfully stored field as appropriate
 * } elseif ($response->asNotStored())
 *     // handle not stored field as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class SetIfEqualResponse extends ResponseBase
{

    /**
     * @return SetIfEqualStored|null Returns the subtype for a successfully stored value and null otherwise.
     */
    public function asStored(): ?SetIfEqualStored
    {
        if ($this->isStored()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetIfEqualStored|null Returns the subtype indicating the value was not stored and null otherwise.
     */
    public function asNotStored(): ?SetIfEqualNotStored
    {
        if($this->isNotStored()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetIfEqualError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?SetIfEqualError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }

}

/**
 * Indicates that the value was successfully stored.
 */
class SetIfEqualStored extends SetIfEqualResponse {}

/**
 * Indicates that the value was not stored because the specified cache key already exists.
 */
class SetIfEqualNotStored extends SetIfEqualResponse {}

/**
 * Contains information about an error returned from the request.
 */
class SetIfEqualError extends SetIfEqualResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a set if not equal request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * SetIfNotEqualStored
 * * SetIfNotEqualNotStored
 * * SetIfNotEqualError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($response->asStored()) {
 *     // handle successfully stored field as appropriate
 * } elseif ($response->asNotStored())
 *     // handle not stored field as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class SetIfNotEqualResponse extends ResponseBase
{

    /**
     * @return SetIfNotEqualStored|null Returns the subtype for a successfully stored value and null otherwise.
     */
    public function asStored(): ?SetIfNotEqualStored
    {
        if ($this->isStored()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetIfNotEqualStored|null Returns the subtype indicating the value was not stored and null otherwise.
     */
    public function asNotStored(): ?SetIfNotEqualNotStored
    {
        if($this->isNotStored()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetIfNotEqualError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?SetIfNotEqualError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }

}

/**
 * Indicates that the value was successfully stored.
 */
class SetIfNotEqualStored extends SetIfNotEqualResponse {}

/**
 * Indicates that the value was not stored because the specified cache key already exists.
 */
class SetIfNotEqualNotStored extends SetIfNotEqualResponse {}

/**
 * Contains information about an error returned from the request.
 */
class SetIfNotEqualError extends SetIfNotEqualResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a set if not exists request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * SetIfNotExistsStored
 * * SetIfNotExistsNotStored
 * * SetIfNotExistsError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($response->asStored()) {
 *     // handle successfully stored field as appropriate
 * } elseif ($response->asNotStored())
 *     // handle not stored field as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class SetIfNotExistsResponse extends ResponseBase
{
    /**
     * @return SetIfNotExistsStored|null Returns the subtype for a successfully stored value and null otherwise.
     */
    public function asStored(): ?SetIfNotExistsStored
    {
        if ($this->isStored()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetIfNotExistsNotStored|null Returns the subtype indicating the value was not stored and null otherwise.
     */
    public function asNotStored(): ?SetIfNotExistsNotStored
    {
        if($this->isNotStored()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetIfNotExistsError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?SetIfNotExistsError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Indicates that the value was successfully stored.
 */
class SetIfNotExistsStored extends SetIfNotExistsResponse
{
}

/**
 * Indicates that the value was not stored because the specified cache key already exists.
 */
class SetIfNotExistsNotStored extends SetIfNotExistsResponse {}

/**
 * Contains information about an error returned from the request.
 */
class SetIfNotExistsError extends SetIfNotExistsResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a cache delete request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * DeleteSuccess
 * * DeleteError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($response->asSuccess()) {
 *     // handle success
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class DeleteResponse extends ResponseBase
{
    /**
     * @return DeleteSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?DeleteSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DeleteError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?DeleteError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Indicates that the request that generated it was successful.
 */
class DeleteSuccess extends DeleteResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class DeleteError extends DeleteResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a keys exist request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * KeysExistSuccess
 * * KeysExistError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asSuccess()) {
 *     $existsArray = $success->exists();
 *     $existsDictionary = $success->existsDictionary();
 * } elseif ($response->asMiss())
 *     // handle miss as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class KeysExistResponse extends ResponseBase
{
    /**
     * @return KeysExistSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess() : ?KeysExistSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return KeysExistError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError() : ?KeysExistError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Indicates that the request that generated it was successful.
 */
class KeysExistSuccess extends KeysExistResponse
{
    private array $values = [];
    private array $valuesDictionary = [];

    public function __construct(_KeysExistResponse $response, array $keys) {
        parent::__construct();
        foreach ($response->getExists()->getIterator() as $index=>$value) {
            $this->values[] = (bool)$value;
            $this->valuesDictionary[$keys[$index]] = (bool)$value;
        }
    }

    /**
     * @return array List of booleans corresponding to the supplied keys.
     */
    public function exists() : array {
        return $this->values;
    }

    /**
     * @return array Dictionary mapping supplied keys to booleans indicating whether the key exists in the cache.
     */
    public function existsDictionary() : array
    {
        return $this->valuesDictionary;
    }
}

/**
 * Contains information about an error returned from the request.
 */
class KeysExistError extends KeysExistResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a key exists request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * KeyExistsSuccess
 * * KeyExistsError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asSuccess()) {
 *     return $success->exists();
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class KeyExistsResponse extends ResponseBase
{
    /**
     * @return KeyExistsSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess() : ?KeyExistsSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return KeyExistsError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError() : ?KeyExistsError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Indicates that the request that generated it was successful.
 */
class KeyExistsSuccess extends KeyExistsResponse
{
    private bool $value;

    public function __construct(bool $response) {
        parent::__construct();
        $this->value = $response;
    }

    public function exists() : bool {
        return $this->value;
    }


}

/**
 * Contains information about an error returned from the request.
 */
class KeyExistsError extends KeyExistsResponse
{
    use ErrorBody;
}

/**
 * Parent response type for an increment request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * IncrementSuccess
 * * IncrementError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asSuccess()) {
 *     return $success->exists();
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class IncrementResponse extends ResponseBase
{
    /**
     * @return IncrementSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess() : ?IncrementSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return IncrementError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError() : ?IncrementError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Indicates that the request that generated it was successful.
 */
class IncrementSuccess extends IncrementResponse
{
    private int $value;

    public function __construct(_IncrementResponse $response) {
        parent::__construct();
        $this->value = $response->getValue();
    }

    public function value() : int {
        return $this->value;
    }
}


/**
 * Contains information about an error returned from the request.
 */
class IncrementError extends IncrementResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a list fetch request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * ListFetchHit
 * * ListFetchMiss
 * * ListFetchError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asHit()) {
 *     return $success->valuesArray();
 * } elseif ($response->asMiss())
 *     // handle miss as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class ListFetchResponse extends ResponseBase
{
    /**
     * @return ListFetchHit|null Returns the hit subtype if the request returned an error and null otherwise.
     */
    public function asHit(): ?ListFetchHit
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ListFetchMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): ?ListFetchMiss
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ListFetchError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?ListFetchError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Contains the result of a cache hit.
 */
class ListFetchHit extends ListFetchResponse
{

    private array $values = [];
    private int $count;

    public function __construct(_ListFetchResponse $response)
    {
        parent::__construct();
        if ($response->getFound()) {
            foreach ($response->getFound()->getValues() as $value) {
                $this->values[] = $value;
            }
            $this->count = count($this->values);
        }
    }

    /**
     * @return array Values from the requested list.
     */
    public function valuesArray(): array
    {
        return $this->values;
    }

    public function __toString()
    {
        return parent::__toString() . ": {$this->count} items";
    }
}

/**
 * Indicates that the request that generated it was a cache miss.
 */
class ListFetchMiss extends ListFetchResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class ListFetchError extends ListFetchResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a list push front request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * ListPushFrontSuccess
 * * ListPushFrontError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asSuccess()) {
 *     return $success->listLength();
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class ListPushFrontResponse extends ResponseBase
{
    /**
     * @return ListPushFrontSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?ListPushFrontSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ListPushFrontError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?ListPushFrontError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Indicates that the request that generated it was successful.
 */
class ListPushFrontSuccess extends ListPushFrontResponse
{
    private int $listLength;

    public function __construct(_ListPushFrontResponse $response)
    {
        parent::__construct();
        $this->listLength = $response->getListLength();
    }

    /**
     * @return int Length of the list after the successful push.
     */
    public function listLength(): int
    {
        return $this->listLength;
    }

    public function __toString()
    {
        return parent::__toString() . ": " . $this->listLength . " items";
    }
}

/**
 * Contains information about an error returned from the request.
 */
class ListPushFrontError extends ListPushFrontResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a list push back request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * ListPushBackSuccess
 * * ListPushBackError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asSuccess()) {
 *     return $success->listLength();
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class ListPushBackResponse extends ResponseBase
{
    /**
     * @return ListPushBackSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?ListPushBackSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ListPushBackError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?ListPushBackError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Indicates that the request that generated it was successful.
 */
class ListPushBackSuccess extends ListPushBackResponse
{
    private int $listLength;

    public function __construct(_ListPushBackResponse $response)
    {
        parent::__construct();
        $this->listLength = $response->getListLength();
    }

    /**
     * @return int Length of the list after the successful push.
     */
    public function listLength(): int
    {
        return $this->listLength;
    }

    public function __toString()
    {
        return parent::__toString() . ": " . $this->listLength . " items";
    }
}

/**
 * Contains information about an error returned from the request.
 */
class ListPushBackError extends ListPushBackResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a list pop front request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * ListPopFrontHit
 * * ListPopFrontMiss
 * * ListPopFrontError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asHit()) {
 *     return $success->valueString();
 * } elseif ($response->asMiss())
 *     // handle miss as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class ListPopFrontResponse extends ResponseBase
{
    /**
     * @return ListPopFrontHit|null Returns the hit subtype if the request returned an error and null otherwise.
     */
    public function asHit(): ?ListPopFrontHit
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ListPopFrontMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): ?ListPopFrontMiss
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ListPopFrontError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?ListPopFrontError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Contains the result of a cache hit.
 */
class ListPopFrontHit extends ListPopFrontResponse
{
    private string $value;

    public function __construct(_ListPopFrontResponse $response)
    {
        parent::__construct();
        $this->value = $response->getFound()->getFront();
    }

    /**
     * @return string The value popped from the list.
     */
    public function valueString(): string
    {
        return $this->value;
    }

    public function __toString()
    {
        return parent::__toString() . ": {$this->shortValue($this->value)}";
    }
}

/**
 * Indicates that the request that generated it was a cache miss.
 */
class ListPopFrontMiss extends ListPopFrontResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class ListPopFrontError extends ListPopFrontResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a list pop back request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * ListPopBackHit
 * * ListPopBackMiss
 * * ListPopBackError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asHit()) {
 *     return $success->valueString();
 * } elseif ($response->asMiss())
 *     // handle miss as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class ListPopBackResponse extends ResponseBase
{
    /**
     * @return ListPopBackHit|null Returns the hit subtype if the request returned an error and null otherwise.
     */
    public function asHit(): ?ListPopBackHit
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ListPopBackMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): ?ListPopBackMiss
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ListPopBackError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?ListPopBackError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Contains the result of a cache hit.
 */
class ListPopBackHit extends ListPopBackResponse
{
    private string $value;

    public function __construct(_ListPopBackResponse $response)
    {
        parent::__construct();
        $this->value = $response->getFound()->getBack();
    }

    /**
     * @return string The value popped from the list.
     */
    public function valueString(): string
    {
        return $this->value;
    }

    public function __toString()
    {
        return parent::__toString() . ": {$this->shortValue($this->value)}";
    }
}

/**
 * Indicates that the request that generated it was a cache miss.
 */
class ListPopBackMiss extends ListPopBackResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class ListPopBackError extends ListPopBackResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a list remove value request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * ListRemoveValueSuccess
 * * ListRemoveValueError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asSuccess()) {
 *     // handle success as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class ListRemoveValueResponse extends ResponseBase
{
    /**
     * @return ListRemoveValueSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?ListRemoveValueSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ListRemoveValueError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?ListRemoveValueError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Indicates that the request that generated it was successful.
 */
class ListRemoveValueSuccess extends ListRemoveValueResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class ListRemoveValueError extends ListRemoveValueResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a list length request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * ListLengthSuccess
 * * ListLengthError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asSuccess()) {
 *     return $success->length();
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class ListLengthResponse extends ResponseBase
{
    /**
     * @return ListLengthSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?ListLengthSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ListLengthError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?ListLengthError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Indicates that the request that generated it was successful.
 */
class ListLengthSuccess extends ListLengthResponse
{
    private int $length;

    public function __construct(_ListLengthResponse $response)
    {
        parent::__construct();
        $this->length = $response->getFound() ? $response->getFound()->getLength() : 0;
    }

    /**
     * @return int Length of the specified list.
     */
    public function length(): int
    {
        return $this->length;
    }

    public function __toString()
    {
        return parent::__toString() . ": {$this->length}";
    }
}

/**
 * Contains information about an error returned from the request.
 */
class ListLengthError extends ListLengthResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a dictionary set field request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * DictionarySetFieldSuccess
 * * DictionarySetFieldError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($response->asSuccess()) {
 *     // handle success as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class DictionarySetFieldResponse extends ResponseBase
{
    /**
     * @return DictionarySetFieldSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?DictionarySetFieldSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DictionarySetFieldError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?DictionarySetFieldError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Indicates that the request that generated it was successful.
 */
class DictionarySetFieldSuccess extends DictionarySetFieldResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class DictionarySetFieldError extends DictionarySetFieldResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a dictionary get field request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * DictionaryGetFieldHit
 * * DictionaryGetFieldMiss
 * * DictionaryGetFieldError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asHit()) {
 *     return $success->valueString();
 * } elseif ($response->asMiss())
 *     // handle miss as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class DictionaryGetFieldResponse extends ResponseBase
{
    /**
     * @return DictionaryGetFieldHit|null Returns the hit subtype if the request returned an error and null otherwise.
     */
    public function asHit(): ?DictionaryGetFieldHit
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DictionaryGetFieldMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): ?DictionaryGetFieldMiss
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DictionaryGetFieldError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?DictionaryGetFieldError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Contains the result of a cache hit.
 */
class DictionaryGetFieldHit extends DictionaryGetFieldResponse
{
    private string $value;
    private string $field;

    public function __construct(string $field, ?_DictionaryGetResponse $response = null, ?string $cacheBody = null)
    {
        parent::__construct();
        $this->field = $field;
        if (!is_null($response) && is_null($cacheBody)) {
            $this->value = $response->getFound()->getItems()[0]->getCacheBody();
        }
        if (is_null($response) && !is_null($cacheBody)) {
            $this->value = $cacheBody;
        }
    }

    /**
     * @return string The requested field
     */
    public function field() : string
    {
        return $this->field;
    }

    /**
     * @return string Value of the requested dictionary
     */
    public function valueString(): string
    {
        return $this->value;
    }

    public function __toString()
    {
        return parent::__toString() . ": " . $this->shortValue($this->value);
    }
}

/**
 * Indicates that the request that generated it was a cache miss.
 */
class DictionaryGetFieldMiss extends DictionaryGetFieldResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class DictionaryGetFieldError extends DictionaryGetFieldResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a dictionary fetch request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * DictionaryFetchHit
 * * DictionaryFetchMiss
 * * DictionaryFetchError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asHit()) {
 *     return $success->valuesDictionary();
 * } elseif ($response->asMiss())
 *     // handle miss as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class DictionaryFetchResponse extends ResponseBase
{
    /**
     * @return DictionaryFetchHit|null Returns the hit subtype if the request returned an error and null otherwise.
     */
    public function asHit(): ?DictionaryFetchHit
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DictionaryFetchMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): ?DictionaryFetchMiss
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DictionaryFetchError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?DictionaryFetchError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Contains the result of a cache hit.
 */
class DictionaryFetchHit extends DictionaryFetchResponse
{
    private array $valuesDictionary;

    public function __construct(_DictionaryFetchResponse $response)
    {
        parent::__construct();
        $items = $response->getFound()->getItems();
        foreach ($items as $item) {
            $this->valuesDictionary[$item->getField()] = $item->getValue();
        }
    }

    /**
     * @return array The dictionary fetched from the cache.
     */
    public function valuesDictionary(): array
    {
        return $this->valuesDictionary;
    }

    public function __toString()
    {
        $numItems = count($this->valuesDictionary);
        return parent::__toString() . ": $numItems items";
    }
}

/**
 * Indicates that the request that generated it was a cache miss.
 */
class DictionaryFetchMiss extends DictionaryFetchResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class DictionaryFetchError extends DictionaryFetchResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a dictionary set fields request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * DictionarySetFieldsSuccess
 * * DictionarySetFieldsError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($response->asSuccess()) {
 *     // handle success as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class DictionarySetFieldsResponse extends ResponseBase
{
    /**
     * @return DictionarySetFieldsSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?DictionarySetFieldsSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DictionarySetFieldsError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?DictionarySetFieldsError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Indicates that the request that generated it was successful.
 */
class DictionarySetFieldsSuccess extends DictionarySetFieldsResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class DictionarySetFieldsError extends DictionarySetFieldsResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a dictionary get fields request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * DictionaryGetFieldsHit
 * * DictionaryGetFieldsMiss
 * * DictionaryGetFieldsError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asHit()) {
 *     $raw_responses_list = $response->responses();
 *     return $success->valuesDictionary();
 * } elseif ($response->asMiss())
 *     // handle miss as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class DictionaryGetFieldsResponse extends ResponseBase
{
    /**
     * @return DictionaryGetFieldsHit|null Returns the hit subtype if the request returned an error and null otherwise.
     */
    public function asHit(): ?DictionaryGetFieldsHit
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DictionaryGetFieldsMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): ?DictionaryGetFieldsMiss
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DictionaryGetFieldsError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?DictionaryGetFieldsError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Contains the result of a cache hit.
 */
class DictionaryGetFieldsHit extends DictionaryGetFieldsResponse
{
    private array $responses = [];
    private array $valuesDictionary = [];

    public function __construct(_DictionaryGetResponse $responses, ?array $fields = null)
    {
        parent::__construct();
        $counter = 0;
        foreach ($responses->getFound()->getItems() as $response) {
            if ($response->getResult() == ECacheResult::Hit) {
                $this->responses[] = new DictionaryGetFieldHit($fields[$counter], null, $response->getCacheBody());
                $this->valuesDictionary[$fields[$counter]] = $response->getCacheBody();
            } elseif ($response->getResult() == ECacheResult::Miss) {
                $this->responses[] = new DictionaryGetFieldMiss();
            } else {
                $this->responses[] = new DictionaryGetFieldError(new UnknownError(strval($response->getResult())));
            }
            $counter++;
        }
    }

    /**
     * @return array List of CacheDictionaryGetField response objects indicating hit/miss/error corresponding to the supplied field list.
     */
    public function responses(): array
    {
        return $this->responses;
    }

    /**
     * @return array Dictionary with requested fields from the dictionary as keys and cache values as values.
     * Fields that don't exist in the cache are omitted.
     */
    public function valuesDictionary(): array
    {
        return $this->valuesDictionary;
    }

    public function __toString()
    {
        $numResponses = count($this->responses());
        return parent::__toString() . ": $numResponses responses";
    }
}

/**
 * Indicates that the request that generated it was a cache miss.
 */
class DictionaryGetFieldsMiss extends DictionaryGetFieldsResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class DictionaryGetFieldsError extends DictionaryGetFieldsResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a dictionary increment request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * DictionaryIncrementSuccess
 * * DictionaryIncrementError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asSuccess()) {
 *     $newValue = $success->valueInt();
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class DictionaryIncrementResponse extends ResponseBase
{
    /**
     * @return DictionaryIncrementSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?DictionaryIncrementSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DictionaryIncrementError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?DictionaryIncrementError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Indicates that the request that generated it was successful.
 */
class DictionaryIncrementSuccess extends DictionaryIncrementResponse
{

    private int $value;

    public function __construct(_DictionaryIncrementResponse $response)
    {
        parent::__construct();
        $this->value = $response->getValue();
    }

    /**
     * @return int Value of the dictionary field after successful increment.
     */
    public function valueInt(): int
    {
        return $this->value;
    }

    public function __toString()
    {
        return parent::__toString() . ": " . $this->value;
    }
}

/**
 * Contains information about an error returned from the request.
 */
class DictionaryIncrementError extends DictionaryIncrementResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a dictionary remove field request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * DictionaryRemoveFieldSuccess
 * * DictionaryRemoveFieldError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($response->asSuccess()) {
 *     // handle success as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class DictionaryRemoveFieldResponse extends ResponseBase
{
    /**
     * @return DictionaryRemoveFieldSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?DictionaryRemoveFieldSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DictionaryRemoveFieldError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?DictionaryRemoveFieldError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Indicates that the request that generated it was successful.
 */
class DictionaryRemoveFieldSuccess extends DictionaryRemoveFieldResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class DictionaryRemoveFieldError extends DictionaryRemoveFieldResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a dictionary remove fields request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * DictionaryRemoveFieldsSuccess
 * * DictionaryRemoveFieldsError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($response->asSuccess()) {
 *     // handle success as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class DictionaryRemoveFieldsResponse extends ResponseBase
{
    /**
     * @return DictionaryRemoveFieldsSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?DictionaryRemoveFieldsSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DictionaryRemoveFieldsError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?DictionaryRemoveFieldsError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Indicates that the request that generated it was successful.
 */
class DictionaryRemoveFieldsSuccess extends DictionaryRemoveFieldsResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class DictionaryRemoveFieldsError extends DictionaryRemoveFieldsResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a set add elements request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * SetAddElementsSuccess
 * * SetAddElementsError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($response->asSuccess()) {
 *     // handle success as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class SetAddElementResponse extends ResponseBase
{
    /**
     * @return SetAddElementSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?SetAddElementSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetAddElementError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?SetAddElementError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Indicates that the request that generated it was successful.
 */
class SetAddElementSuccess extends SetAddElementResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class SetAddElementError extends SetAddElementResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a set union request. The response object
 * is resolved to a type-safe object of one of the following subtypes:
 *
 * * SetAddElementsSuccess
 * * SetAddElementsError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($response->asSuccess()) {
 *     // handle success as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class SetAddElementsResponse extends ResponseBase
{
    /**
     * @return SetAddElementsSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?SetAddElementsSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetAddElementsError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?SetAddElementsError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Indicates that the request that generated it was successful.
 */
class SetAddElementsSuccess extends SetAddElementsResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class SetAddElementsError extends SetAddElementsResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a SetContainsElements request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * SetContainsElementsHit
 * * SetContainsElementsMiss
 * * SetContainsElementsError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asHit()) {
 *     // a dictionary containing a key for each `element` in your request; the value is a boolean indicating whether the element is in the set
 *     return $success->containsElementsDictionary();
 * } elseif ($response->asMiss())
 *     // handle miss as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class SetContainsElementsResponse extends ResponseBase
{

    /**
     * @return SetContainsElementsHit|null Returns the hit subtype if the request returned an error and null otherwise.
     */
    public function asHit(): ?SetContainsElementsHit
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetContainsElementsMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): ?SetContainsElementsMiss
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetContainsElementsError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?SetContainsElementsError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Contains the result of a cache hit.
 */
class SetContainsElementsHit extends SetContainsElementsResponse
{
    private array $values = [];
    private array $valuesDictionary = [];

    public function __construct(_SetContainsResponse $response, array $elements) {
        parent::__construct();
        foreach ($response->getFound()->getContains()->getIterator() as $index=>$value) {
            $this->values[] = (bool)$value;
            $this->valuesDictionary[$elements[$index]] = (bool)$value;
        }
    }

    /**
     * @return array List of booleans corresponding to the supplied elements.
     */
    public function containsElements() : array {
        return $this->values;
    }

    /**
     * @return array Dictionary mapping supplied elements to booleans indicating whether the key exists in the set.
     */
    public function containsElementsDictionary() : array
    {
        return $this->valuesDictionary;
    }

    public function __toString()
    {
        $numElements = count($this->values);
        return parent::__toString() . ": $numElements elements";
    }
}

/**
 * Indicates that the request that generated it was a cache miss.
 */
class SetContainsElementsMiss extends SetContainsElementsResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class SetContainsElementsError extends SetContainsElementsResponse
{
    use ErrorBody;
}


/**
 * Parent response type for a set fetch request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * SetFetchHit
 * * SetFetchMiss
 * * SetFetchError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asHit()) {
 *     return $success->valuesArray();
 * } elseif ($response->asMiss())
 *     // handle miss as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class SetFetchResponse extends ResponseBase
{

    /**
     * @return SetFetchHit|null Returns the hit subtype if the request returned an error and null otherwise.
     */
    public function asHit(): ?SetFetchHit
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetFetchMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): ?SetFetchMiss
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetFetchError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?SetFetchError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Contains the result of a cache hit.
 */
class SetFetchHit extends SetFetchResponse
{
    private array $stringSet;

    public function __construct(_SetFetchResponse $response)
    {
        parent::__construct();
        foreach ($response->getFound()->getElements() as $element) {
            $this->stringSet[] = $element;
        }
    }

    /**
     * @return array Values of the requested set.
     */
    public function valuesArray(): array
    {
        return $this->stringSet;
    }

    public function __toString()
    {
        $numElements = count($this->stringSet);
        return parent::__toString() . ": $numElements elements";
    }
}

/**
 * Indicates that the request that generated it was a cache miss.
 */
class SetFetchMiss extends SetFetchResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class SetFetchError extends SetFetchResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a set length request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * SetLengthSuccess
 * * SetLengthError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asSuccess()) {
 *     return $success->length();
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class SetLengthResponse extends ResponseBase
{
    /**
     * @return SetLengthSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?SetLengthSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetLengthError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?SetLengthError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Indicates that the request that generated it was successful.
 */
class SetLengthSuccess extends SetLengthResponse
{
    private int $length;

    public function __construct(_SetLengthResponse $response)
    {
        parent::__construct();
        $this->length = $response->getFound() ? $response->getFound()->getLength() : 0;
    }

    /**
     * @return int Length of the specified set.
     */
    public function length(): int
    {
        return $this->length;
    }

    public function __toString()
    {
        return parent::__toString() . ": {$this->length}";
    }
}

/**
 * Contains information about an error returned from the request.
 */
class SetLengthError extends SetLengthResponse
{
    use ErrorBody;
}


/**
 * Parent response type for a set remove element request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * SetRemoveElementSuccess
 * * SetRemoveElementError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($response->asSuccess()) {
 *     // handle success as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class SetRemoveElementResponse extends ResponseBase
{
    /**
     * @return SetRemoveElementSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?SetRemoveElementSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetRemoveElementError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?SetRemoveElementError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Indicates that the request that generated it was successful.
 */
class SetRemoveElementSuccess extends SetRemoveElementResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class SetRemoveElementError extends SetRemoveElementResponse
{
    use ErrorBody;
}

// placeholder: sortedSetLengthByScore

/**
 * Parent response type for a sorted set put element request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * SortedSetPutElementSuccess
 * * SortedSetPutElementError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($response->asSuccess()) {
 *     // handle success as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class SortedSetPutElementResponse extends ResponseBase
{
    /**
     * @return SortedSetPutElementSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?SortedSetPutElementSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SortedSetPutElementError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?SortedSetPutElementError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Indicates that the request that generated it was successful.
 */
class SortedSetPutElementSuccess extends SortedSetPutElementResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class SortedSetPutElementError extends SortedSetPutElementResponse
{
    use ErrorBody;
}

// placeholder: sortedSetIncrementScoreResponse

/**
 * Parent response type for a sorted set fetch request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * SortedSetFetchHit
 * * SortedSetFetchMiss
 * * SortedSetFetchError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asHit()) {
 *     return $success->valuesArray();
 * } elseif ($response->asMiss())
 *     // handle miss as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class SortedSetFetchResponse extends ResponseBase
{

    /**
     * @return SortedSetFetchHit|null Returns the hit subtype if the request returned an error and null otherwise.
     */
    public function asHit(): ?SortedSetFetchHit
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SortedSetFetchMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): ?SortedSetFetchMiss
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SortedSetFetchError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?SortedSetFetchError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Contains the result of a cache hit.
 */
class SortedSetFetchHit extends SortedSetFetchResponse
{
    private array $elements;

    public function __construct(_SortedSetFetchResponse $response)
    {
        parent::__construct();
        foreach ($response->getFound()->getValuesWithScores()->getElements() as $element) {
            $this->elements[$element->getValue()] = $element->getScore();
        }
    }

    /**
     * @return array Values of the requested sorted set.
     */
    public function valuesArray(): array
    {
        return $this->elements;
    }

    public function __toString()
    {
        $numElements = count($this->elements);
        return parent::__toString() . ": $numElements elements";
    }
}

/**
 * Indicates that the request that generated it was a cache miss.
 */
class SortedSetFetchMiss extends SortedSetFetchResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class SortedSetFetchError extends SortedSetFetchResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a sorted get score request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 * * SortedSetGetScoreHit
 * * SortedSetGetScoreMiss
 * * SortedSetGetScoreError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asHit()) {
 *   return $success->score();
 * } elseif ($response->asMiss())
 * // handle miss as appropriate
 * } elseif ($error = $response->asError())
 * // handle error as appropriate
 * }
 * </code>
 */
class SortedSetGetScoreResponse extends ResponseBase {
    private string $value;

    protected function __construct(string $value)
    {
        parent::__construct();
        $this->value = $value;
    }

    /**
     * @return string Value of the requested element.
     */
    public function valueString(): string
    {
        return $this->value;
    }

    /**
     * @return SortedSetGetScoreHit|null Returns the hit subtype if the request returned an error and null otherwise.
     */
    public function asHit(): ?SortedSetGetScoreHit {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SortedSetGetScoreMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): ?SortedSetGetScoreMiss {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SortedSetGetScoreError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?SortedSetGetScoreError {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class SortedSetGetScoreHit extends SortedSetGetScoreResponse {
    private float $score;

    public function __construct(string $value, float $score)
    {
        parent::__construct($value);
        $this->score = $score;
    }

    /**
     * @return float The score of the requested element.
     */
    public function score(): float
    {
        return $this->score;
    }

    public function __toString()
    {
        return parent::__toString() . ": " . $this->score;
    }
}

class SortedSetGetScoreMiss extends SortedSetGetScoreResponse {
    public function __construct(string $value)
    {
        parent::__construct($value);
    }
}

class SortedSetGetScoreError extends SortedSetGetScoreResponse {
    use ErrorBody;

    public function __construct(string $value, SdkError $error)
    {
        parent::__construct($value);
        $this->setError($error);
    }
}

/**
 * Parent response type for a sorted set remove element request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * SortedSetRemoveElementSuccess
 * * SortedSetRemoveElementError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($response->asSuccess()) {
 *     // handle success as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class SortedSetRemoveElementResponse extends ResponseBase
{
    /**
     * @return SortedSetRemoveElementSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?SortedSetRemoveElementSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SortedSetRemoveElementError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?SortedSetRemoveElementError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Indicates that the request that generated it was successful.
 */
class SortedSetRemoveElementSuccess extends SortedSetRemoveElementResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class SortedSetRemoveElementError extends SortedSetRemoveElementResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a sorted set remove elements request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 * SortedSetRemoveElementsSuccess
 * SortedSetRemoveElementsError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($response->asSuccess()) {
 * // handle success as appropriate
 * } elseif ($error = $response->asError())
 * // handle error as appropriate
 * }
 * </code>
 */
abstract class SortedSetRemoveElementsResponse extends ResponseBase
{
    /**
     * @return SortedSetRemoveElementsSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?SortedSetRemoveElementsSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SortedSetRemoveElementsError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?SortedSetRemoveElementsError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Indicates that the request that generated it was successful.
 */
class SortedSetRemoveElementsSuccess extends SortedSetRemoveElementsResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class SortedSetRemoveElementsError extends SortedSetRemoveElementsResponse
{
    use ErrorBody;
}

abstract class GetBatchResponse extends ResponseBase
{
    /**
     * @return GetBatchSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?GetBatchSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return GetBatchError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?GetBatchError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class GetBatchSuccess extends GetBatchResponse
{
    private array $values = [];
    private array $results = [];

    public function __construct(array $responses)
    {
        parent::__construct();
        $this->results = $responses;
    }

    public function results(): array
    {
        return $this->results;
    }

    public function values(): array
    {
        if (!$this->values) {
            $array = [];
            foreach ($this->results as $result) {
                $value = $result->asHit() ? $result->asHit()->valueString() : null;
                $array[] = $value;
            }
            $this->values = $array;
        }
        return $this->values;
    }
}

class GetBatchError extends GetBatchResponse
{
    use ErrorBody;
}

abstract class SetBatchResponse extends ResponseBase
{
    /**
     * @return SetBatchSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?SetBatchSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetBatchError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?SetBatchError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class SetBatchSuccess extends SetBatchResponse
{
    private array $responses;

    public function __construct(array $responses)
    {
        parent::__construct();
        $this->responses = $responses;
    }

    public function results(): array
    {
        return $this->responses;
    }
}

class SetBatchError extends SetBatchResponse
{
    use ErrorBody;
}


/**
 * Parent response type for a item get ttl request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * ItemGetTtlHit
 * * ItemGetTtlMiss
 * * itemGetTtlError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($success = $response->asHit()) {
 *     return $success->remainingTtlMillis();
 * } elseif ($response->asMiss())
 *     // handle miss as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class ItemGetTtlResponse extends ResponseBase
{
    /**
     * @return ItemGetTtlHit|null Returns the hit subtype if the request returned an error and null otherwise.
     */
    public function asHit(): ?ItemGetTtlHit
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ItemGetTtlMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): ?ItemGetTtlMiss
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ItemGetTtlError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?ItemGetTtlError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Contains the result of a cache hit.
 */
class ItemGetTtlHit extends ItemGetTtlResponse
{
    private int $remainingTtlMillis;

    public function __construct(_ItemGetTtlResponse $response)
    {
        parent::__construct();
        if ($response->getFound()) {
            $this->remainingTtlMillis = $response->getFound()->getRemainingTtlMillis();
        }
    }

    /**
     * @return int Remaining time-to-live in milliseconds.
     */
    public function remainingTtlMillis(): int
    {
        return $this->remainingTtlMillis;
    }

    public function __toString()
    {
        return parent::__toString() . ": remaining ttl: {$this->remainingTtlMillis}";
    }
}

/**
 * Indicates that the request that generated it was a cache miss.
 */
class ItemGetTtlMiss extends ItemGetTtlResponse
{

    public function remainingTtlMillis()
    {
        return null;
    }
}

/**
 * Contains information about an error returned from the request.
 */
class ItemGetTtlError extends ItemGetTtlResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a update ttl request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * UpdateTtlSet
 * * UpdateTtlMiss
 * * UpdateTtlError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($set = $response->asSet()) {
 *     // handle set as appropriate
 * } elseif ($response->asMiss())
 *     // handle miss as appropriate
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * </code>
 */
abstract class UpdateTtlResponse extends ResponseBase
{
    /**
     * @return UpdateTtlSet|null Returns the set subtype if the request returned a set and null otherwise.
     */
    public function asSet(): ?UpdateTtlSet
    {
        if ($this->isSet()) {
            return $this;
        }
        return null;
    }

    /**
     * @return UpdateTtlMiss|null Returns the miss subtype if the request returned a miss and null otherwise.
     */
    public function asMiss(): ?UpdateTtlMiss
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return UpdateTtlError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?UpdateTtlError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Contains the result of a cache hit and ttl set.
 */
class UpdateTtlSet extends UpdateTtlResponse
{
}

/**
 * Indicates that the request that generated it was a cache miss.
 */
class UpdateTtlMiss extends UpdateTtlResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class UpdateTtlError extends UpdateTtlResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a increase ttl request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * IncreaseTtlSet
 * * IncreaseTtlNotSet
 * * IncreaseTtlMiss
 * * IncreaseTtlError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($set = $response->asSet()) {
 *     // handle set as appropriate
 * } elseif ($notSet = $response->asNotSet()) {
 *    // handle not set as appropriate
 * } elseif ($response->asMiss())
 *    // handle miss as appropriate
 * } elseif ($error = $response->asError())
 *    // handle error as appropriate
 * }
 * </code>
 */
abstract class IncreaseTtlResponse extends ResponseBase
{
    /**
     * @return IncreaseTtlSet|null Returns the set subtype if the request returned a set and null otherwise.
     */
    public function asSet(): ?IncreaseTtlSet
    {
        if ($this->isSet()) {
            return $this;
        }
        return null;
    }

    /**
     * @return IncreaseTtlNotSet|null Returns the not set subtype if the request returned a not set and null otherwise.
     */
    public function asNotSet(): ?IncreaseTtlNotSet
    {
        if ($this->isNotSet()) {
            return $this;
        }
        return null;
    }

    /**
     * @return IncreaseTtlMiss|null Returns the miss subtype if the request returned a miss and null otherwise.
     */
    public function asMiss(): ?IncreaseTtlMiss
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return IncreaseTtlError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?IncreaseTtlError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Contains the result of a cache hit and ttl increase.
 */
class IncreaseTtlSet extends IncreaseTtlResponse
{
}

/**
 * Contains the result of a cache hit but ttl not updated due to a failed condition.
 */
class IncreaseTtlNotSet extends IncreaseTtlResponse
{
}

/**
 * Indicates that the request that generated it was a cache miss.
 */
class IncreaseTtlMiss extends IncreaseTtlResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class IncreaseTtlError extends IncreaseTtlResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a decrease ttl request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * DecreaseTtlSet
 * * DecreaseTtlNotSet
 * * DecreaseTtlMiss
 * * DecreaseTtlError
 *
 * Pattern matching can be used to operate on the appropriate subtype.
 * For example:
 * <code>
 * if ($set = $response->asSet()) {
 *     // handle set as appropriate
 * } elseif ($notSet = $response->asNotSet()) {
 *    // handle not set as appropriate
 * } elseif ($response->asMiss())
 *    // handle miss as appropriate
 * } elseif ($error = $response->asError())
 *    // handle error as appropriate
 * }
 * </code>
 */
abstract class DecreaseTtlResponse extends ResponseBase
{
    /**
     * @return DecreaseTtlSet|null Returns the set subtype if the request returned a set and null otherwise.
     */
    public function asSet(): ?DecreaseTtlSet
    {
        if ($this->isSet()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DecreaseTtlNotSet|null Returns the not set subtype if the request returned a not set and null otherwise.
     */
    public function asNotSet(): ?DecreaseTtlNotSet
    {
        if ($this->isNotSet()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DecreaseTtlMiss|null Returns the miss subtype if the request returned a  miss and null otherwise.
     */
    public function asMiss(): ?DecreaseTtlMiss
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DecreaseTtlError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?DecreaseTtlError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

/**
 * Contains the result of a cache hit and ttl decrease.
 */
class DecreaseTtlSet extends DecreaseTtlResponse
{
}

/**
 * Contains the result of a cache hit but ttl not updated due to a failed condition.
 */
class DecreaseTtlNotSet extends DecreaseTtlResponse
{
}

/**
 * Indicates that the request that generated it was a cache miss.
 */
class DecreaseTtlMiss extends DecreaseTtlResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class DecreaseTtlError extends DecreaseTtlResponse
{
    use ErrorBody;
}
