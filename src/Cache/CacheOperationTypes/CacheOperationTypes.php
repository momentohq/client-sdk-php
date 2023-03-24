<?php
declare(strict_types=1);

namespace Momento\Cache\CacheOperationTypes;

use Cache_client\_DictionaryFetchResponse;
use Cache_client\_DictionaryGetResponse;
use Cache_client\_DictionaryIncrementResponse;
use Cache_client\_GetResponse;
use Cache_client\_KeysExistResponse;
use Cache_client\_ListFetchResponse;
use Cache_client\_ListLengthResponse;
use Cache_client\_ListPopBackResponse;
use Cache_client\_ListPopFrontResponse;
use Cache_client\_ListPushBackResponse;
use Cache_client\_ListPushFrontResponse;
use Cache_client\_SetFetchResponse;
use Cache_client\_SetIfNotExistsResponse;
use Cache_client\_SetResponse;
use Cache_client\ECacheResult;
use Control_client\_ListCachesResponse;
use Momento\Cache\Errors\SdkError;
use Momento\Cache\Errors\UnknownError;

trait ErrorBody
{
    private SdkError $innerException;
    private string $errorCode;
    private string $message;

    public function __construct(SdkError $error)
    {
        parent::__construct();
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
    public function asSuccess(): CreateCacheSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CreateCacheError|null Returns the error subtype if the request was successful and null otherwise.
     */
    public function asError(): CreateCacheError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CreateCacheAlreadyExists|null Returns the "already exists" subtype if the request was successful and null otherwise.
     */
    public function asAlreadyExists(): CreateCacheAlreadyExists|null
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
    public function asSuccess(): DeleteCacheSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DeleteCacheError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): DeleteCacheError|null
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
    public function asSuccess(): ListCachesSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ListCachesError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ListCachesError|null
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
    public function asSuccess(): SetSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): SetError|null
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
 * * CacheGetHit
 * * CacheGetMiss
 * * CacheGetError
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
    public function asHit(): GetHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return GetMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): GetMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return GetError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): GetError|null
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
 * } elseif ($response->asMiss())
 *     // handle not stored field due to existing value as appropriate
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
    public function asStored(): SetIfNotExistsStored|null
    {
        if ($this->isStored()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetIfNotExistsNotStored|null Returns the subtype indicating the value was not stored and null otherwise.
     */
    public function asNotStored(): SetIfNotExistsNotStored|null
    {
        if($this->isNotStored()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetIfNotExistsError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): SetIfNotExistsError|null
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
 * * CacheDeleteSuccess
 * * CacheDeleteError
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
    public function asSuccess(): DeleteSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DeleteError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): DeleteError|null
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
 * * CacheKeysExistSuccess
 * * CacheKeysExistError
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
    public function asSuccess() : KeysExistSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return KeysExistError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError() : KeysExistError|null
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
 * * CacheKeyExistsSuccess
 * * CacheKeyExistsError
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
    public function asSuccess() : KeyExistsSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return KeyExistsError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError() : KeyExistsError|null
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
    public function asHit(): ListFetchHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ListFetchMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): ListFetchMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ListFetchError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ListFetchError|null
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
    public function asSuccess(): ListPushFrontSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ListPushFrontError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ListPushFrontError|null
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
    public function asSuccess(): ListPushBackSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ListPushBackError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ListPushBackError|null
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
    public function asHit(): ListPopFrontHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ListPopFrontMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): ListPopFrontMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ListPopFrontError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ListPopFrontError|null
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
    public function asHit(): ListPopBackHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ListPopBackMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): ListPopBackMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ListPopBackError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ListPopBackError|null
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
    public function asSuccess(): ListRemoveValueSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ListRemoveValueError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ListRemoveValueError|null
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
 * * ListRemoveValueSuccess
 * * ListRemoveValueError
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
    public function asSuccess(): ListLengthSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ListLengthError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ListLengthError|null
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
    public function asSuccess(): DictionarySetFieldSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DictionarySetFieldError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): DictionarySetFieldError|null
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
    public function asHit(): DictionaryGetFieldHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DictionaryGetFieldMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): DictionaryGetFieldMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DictionaryGetFieldError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): DictionaryGetFieldError|null
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

    public function __construct(string $field, _DictionaryGetResponse $response = null, ?string $cacheBody = null)
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
    public function asHit(): DictionaryFetchHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DictionaryFetchMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): DictionaryFetchMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DictionaryFetchError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): DictionaryFetchError|null
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
    public function asSuccess(): DictionarySetFieldsSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DictionarySetFieldsError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): DictionarySetFieldsError|null
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
    public function asHit(): DictionaryGetFieldsHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DictionaryGetFieldsMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): DictionaryGetFieldsMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DictionaryGetFieldsError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): DictionaryGetFieldsError|null
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
    public function asSuccess(): DictionaryIncrementSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DictionaryIncrementError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): DictionaryIncrementError|null
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
    public function asSuccess(): DictionaryRemoveFieldSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DictionaryRemoveFieldError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): DictionaryRemoveFieldError|null
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
    public function asSuccess(): DictionaryRemoveFieldsSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DictionaryRemoveFieldsError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): DictionaryRemoveFieldsError|null
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
    public function asSuccess(): SetAddElementSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetAddElementError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): SetAddElementError|null
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
    public function asHit(): SetFetchHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetFetchMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): SetFetchMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetFetchError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): SetFetchError|null
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
    public function asSuccess(): SetRemoveElementSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return SetRemoveElementError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): SetRemoveElementError|null
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
