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
        $this->baseType = get_parent_class($this);
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
 * * CreateCacheResponseSuccess
 * * CreateCacheResponseAlreadyExists
 * * CreateCacheResponseError
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
     * @return CreateCacheResponseSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): CreateCacheResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CreateCacheResponseError|null Returns the error subtype if the request was successful and null otherwise.
     */
    public function asError(): CreateCacheResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CreateCacheResponseAlreadyExists|null Returns the "already exists" subtype if the request was successful and null otherwise.
     */
    public function asAlreadyExists(): CreateCacheResponseAlreadyExists|null
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
class CreateCacheResponseSuccess extends CreateCacheResponse
{
}

/**
 * Indicates that a cache with the requested name has already been created in the requesting account.
 */
class CreateCacheResponseAlreadyExists extends CreateCacheResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class CreateCacheResponseError extends CreateCacheResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a delete cache request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * DeleteCacheResponseSuccess
 * * DeleteCacheResponseError
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
     * @return DeleteCacheResponseSuccess|null  Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): DeleteCacheResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DeleteCacheResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): DeleteCacheResponseError|null
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
class DeleteCacheResponseSuccess extends DeleteCacheResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class DeleteCacheResponseError extends DeleteCacheResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a list caches request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *  * ListCachesResponseSuccess
 *  * ListCachesResponseError
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
     * @return ListCachesResponseSuccess|null  Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ListCachesResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ListCachesResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ListCachesResponseError|null
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
class ListCachesResponseSuccess extends ListCachesResponse
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
class ListCachesResponseError extends ListCachesResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a set request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CacheSetResponseSuccess
 * * CacheSetResponseError
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
abstract class CacheSetResponse extends ResponseBase
{
    /**
     * @return CacheSetResponseSuccess|null  Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): CacheSetResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheSetResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): CacheSetResponseError|null
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
class CacheSetResponseSuccess extends CacheSetResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class CacheSetResponseError extends CacheSetResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a get request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CacheGetResponseHit
 * * CacheGetResponseMiss
 * * CacheGetResponseError
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
abstract class CacheGetResponse extends ResponseBase
{

    /**
     * @return CacheGetResponseHit|null Returns the hit subtype if the request returned an error and null otherwise.
     */
    public function asHit(): CacheGetResponseHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheGetResponseMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): CacheGetResponseMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheGetResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): CacheGetResponseError|null
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
class CacheGetResponseHit extends CacheGetResponse
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
class CacheGetResponseMiss extends CacheGetResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class CacheGetResponseError extends CacheGetResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a set if not exists request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CacheSetIfNotExistsResponseStored
 * * CacheSetIfNotExistsResponseNotStored
 * * CacheSetIfNotExistsResponseError
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
abstract class CacheSetIfNotExistsResponse extends ResponseBase
{
    /**
     * @return CacheSetIfNotExistsResponseStored|null Returns the subtype for a successfully stored value and null otherwise.
     */
    public function asStored(): CacheSetIfNotExistsResponseStored|null
    {
        if ($this->isStored()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheSetIfNotExistsResponseNotStored|null Returns the subtype indicating the value was not stored and null otherwise.
     */
    public function asNotStored(): CacheSetIfNotExistsResponseNotStored|null
    {
        if($this->isNotStored()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheSetIfNotExistsResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): CacheSetIfNotExistsResponseError|null
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
class CacheSetIfNotExistsResponseStored extends CacheSetIfNotExistsResponse
{
}

/**
 * Indicates that the value was not stored because the specified cache key already exists.
 */
class CacheSetIfNotExistsResponseNotStored extends CacheSetIfNotExistsResponse {}

/**
 * Contains information about an error returned from the request.
 */
class CacheSetIfNotExistsResponseError extends CacheSetIfNotExistsResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a cache delete request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CacheDeleteResponseSuccess
 * * CacheDeleteResponseError
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
abstract class CacheDeleteResponse extends ResponseBase
{
    /**
     * @return CacheDeleteResponseSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): CacheDeleteResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheDeleteResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): CacheDeleteResponseError|null
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
class CacheDeleteResponseSuccess extends CacheDeleteResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class CacheDeleteResponseError extends CacheDeleteResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a keys exist request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CacheKeysExistResponseSuccess
 * * CacheKeysExistResponseError
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
abstract class CacheKeysExistResponse extends ResponseBase
{
    /**
     * @return CacheKeysExistResponseSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess() : CacheKeysExistResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheKeysExistResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError() : CacheKeysExistResponseError|null
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
class CacheKeysExistResponseSuccess extends CacheKeysExistResponse
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
class CacheKeysExistResponseError extends CacheKeysExistResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a key exists request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CacheKeyExistsResponseSuccess
 * * CacheKeyExistsResponseError
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
abstract class CacheKeyExistsResponse extends ResponseBase
{
    /**
     * @return CacheKeyExistsResponseSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess() : CacheKeyExistsResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheKeyExistsResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError() : CacheKeyExistsResponseError|null
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
class CacheKeyExistsResponseSuccess extends CacheKeyExistsResponse
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
class CacheKeyExistsResponseError extends CacheKeyExistsResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a list fetch request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CacheListFetchResponseHit
 * * CacheListFetchResponseMiss
 * * CacheListFetchResponseError
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
abstract class CacheListFetchResponse extends ResponseBase
{
    /**
     * @return CacheListFetchResponseHit|null Returns the hit subtype if the request returned an error and null otherwise.
     */
    public function asHit(): CacheListFetchResponseHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheListFetchResponseMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): CacheListFetchResponseMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheListFetchResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): CacheListFetchResponseError|null
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
class CacheListFetchResponseHit extends CacheListFetchResponse
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
class CacheListFetchResponseMiss extends CacheListFetchResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class CacheListFetchResponseError extends CacheListFetchResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a list push front request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CacheListPushFrontResponseSuccess
 * * CacheListPushFrontResponseError
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
abstract class CacheListPushFrontResponse extends ResponseBase
{
    /**
     * @return CacheListPushFrontResponseSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): CacheListPushFrontResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheListPushFrontResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): CacheListPushFrontResponseError|null
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
class CacheListPushFrontResponseSuccess extends CacheListPushFrontResponse
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
class CacheListPushFrontResponseError extends CacheListPushFrontResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a list push back request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CacheListPushBackResponseSuccess
 * * CacheListPushBackResponseError
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
abstract class CacheListPushBackResponse extends ResponseBase
{
    /**
     * @return CacheListPushBackResponseSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): CacheListPushBackResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheListPushBackResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): CacheListPushBackResponseError|null
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
class CacheListPushBackResponseSuccess extends CacheListPushBackResponse
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
class CacheListPushBackResponseError extends CacheListPushBackResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a list pop front request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CacheListPopFrontResponseHit
 * * CacheListPopFrontResponseMiss
 * * CacheListPopFrontResponseError
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
abstract class CacheListPopFrontResponse extends ResponseBase
{
    /**
     * @return CacheListPopFrontResponseHit|null Returns the hit subtype if the request returned an error and null otherwise.
     */
    public function asHit(): CacheListPopFrontResponseHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheListPopFrontResponseMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): CacheListPopFrontResponseMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheListPopFrontResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): CacheListPopFrontResponseError|null
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
class CacheListPopFrontResponseHit extends CacheListPopFrontResponse
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
class CacheListPopFrontResponseMiss extends CacheListPopFrontResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class CacheListPopFrontResponseError extends CacheListPopFrontResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a list pop back request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CacheListPopBackResponseHit
 * * CacheListPopBackResponseMiss
 * * CacheListPopBackResponseError
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
abstract class CacheListPopBackResponse extends ResponseBase
{
    /**
     * @return CacheListPopBackResponseHit|null Returns the hit subtype if the request returned an error and null otherwise.
     */
    public function asHit(): CacheListPopBackResponseHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheListPopBackResponseMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): CacheListPopBackResponseMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheListPopBackResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): CacheListPopBackResponseError|null
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
class CacheListPopBackResponseHit extends CacheListPopBackResponse
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
class CacheListPopBackResponseMiss extends CacheListPopBackResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class CacheListPopBackResponseError extends CacheListPopBackResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a list remove value request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CacheListRemoveValueResponseSuccess
 * * CacheListRemoveValueResponseError
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
abstract class CacheListRemoveValueResponse extends ResponseBase
{
    /**
     * @return CacheListRemoveValueResponseSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): CacheListRemoveValueResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheListRemoveValueResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): CacheListRemoveValueResponseError|null
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
class CacheListRemoveValueResponseSuccess extends CacheListRemoveValueResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class CacheListRemoveValueResponseError extends CacheListRemoveValueResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a list length request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CacheListRemoveValueResponseSuccess
 * * CacheListRemoveValueResponseError
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
abstract class CacheListLengthResponse extends ResponseBase
{
    /**
     * @return CacheListLengthResponseSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): CacheListLengthResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheListLengthResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): CacheListLengthResponseError|null
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
class CacheListLengthResponseSuccess extends CacheListLengthResponse
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
class CacheListLengthResponseError extends CacheListLengthResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a dictionary set field request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CacheDictionarySetFieldResponseSuccess
 * * CacheDictionarySetFieldResponseError
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
abstract class CacheDictionarySetFieldResponse extends ResponseBase
{
    /**
     * @return CacheDictionarySetFieldResponseSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): CacheDictionarySetFieldResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheDictionarySetFieldResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): CacheDictionarySetFieldResponseError|null
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
class CacheDictionarySetFieldResponseSuccess extends CacheDictionarySetFieldResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class CacheDictionarySetFieldResponseError extends CacheDictionarySetFieldResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a dictionary get field request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CacheDictionaryGetFieldResponseHit
 * * CacheDictionaryGetFieldResponseMiss
 * * CacheDictionaryGetFieldResponseError
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
abstract class CacheDictionaryGetFieldResponse extends ResponseBase
{
    /**
     * @return CacheDictionaryGetFieldResponseHit|null Returns the hit subtype if the request returned an error and null otherwise.
     */
    public function asHit(): CacheDictionaryGetFieldResponseHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheDictionaryGetFieldResponseMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): CacheDictionaryGetFieldResponseMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheDictionaryGetFieldResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): CacheDictionaryGetFieldResponseError|null
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
class CacheDictionaryGetFieldResponseHit extends CacheDictionaryGetFieldResponse
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
class CacheDictionaryGetFieldResponseMiss extends CacheDictionaryGetFieldResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class CacheDictionaryGetFieldResponseError extends CacheDictionaryGetFieldResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a dictionary fetch request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CacheDictionaryFetchResponseHit
 * * CacheDictionaryFetchResponseMiss
 * * CacheDictionaryFetchResponseError
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
abstract class CacheDictionaryFetchResponse extends ResponseBase
{
    /**
     * @return CacheDictionaryFetchResponseHit|null Returns the hit subtype if the request returned an error and null otherwise.
     */
    public function asHit(): CacheDictionaryFetchResponseHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheDictionaryFetchResponseMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): CacheDictionaryFetchResponseMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheDictionaryFetchResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): CacheDictionaryFetchResponseError|null
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
class CacheDictionaryFetchResponseHit extends CacheDictionaryFetchResponse
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
class CacheDictionaryFetchResponseMiss extends CacheDictionaryFetchResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class CacheDictionaryFetchResponseError extends CacheDictionaryFetchResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a dictionary set fields request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CacheDictionarySetFieldsResponseSuccess
 * * CacheDictionarySetFieldsResponseError
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
abstract class CacheDictionarySetFieldsResponse extends ResponseBase
{
    /**
     * @return CacheDictionarySetFieldsResponseSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): CacheDictionarySetFieldsResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheDictionarySetFieldsResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): CacheDictionarySetFieldsResponseError|null
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
class CacheDictionarySetFieldsResponseSuccess extends CacheDictionarySetFieldsResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class CacheDictionarySetFieldsResponseError extends CacheDictionarySetFieldsResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a dictionary get fields request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CacheDictionaryGetFieldsResponseHit
 * * CacheDictionaryGetFieldsResponseMiss
 * * CacheDictionaryGetFieldsResponseError
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
abstract class CacheDictionaryGetFieldsResponse extends ResponseBase
{
    /**
     * @return CacheDictionaryGetFieldsResponseHit|null Returns the hit subtype if the request returned an error and null otherwise.
     */
    public function asHit(): CacheDictionaryGetFieldsResponseHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheDictionaryGetFieldsResponseMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): CacheDictionaryGetFieldsResponseMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheDictionaryGetFieldsResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): CacheDictionaryGetFieldsResponseError|null
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
class CacheDictionaryGetFieldsResponseHit extends CacheDictionaryGetFieldsResponse
{
    private array $responses = [];
    private array $valuesDictionary = [];

    public function __construct(_DictionaryGetResponse $responses, ?array $fields = null)
    {
        parent::__construct();
        $counter = 0;
        foreach ($responses->getFound()->getItems() as $response) {
            if ($response->getResult() == ECacheResult::Hit) {
                $this->responses[] = new CacheDictionaryGetFieldResponseHit($fields[$counter], null, $response->getCacheBody());
                $this->valuesDictionary[$fields[$counter]] = $response->getCacheBody();
            } elseif ($response->getResult() == ECacheResult::Miss) {
                $this->responses[] = new CacheDictionaryGetFieldResponseMiss();
            } else {
                $this->responses[] = new CacheDictionaryGetFieldResponseError(new UnknownError(strval($response->getResult())));
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
class CacheDictionaryGetFieldsResponseMiss extends CacheDictionaryGetFieldsResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class CacheDictionaryGetFieldsResponseError extends CacheDictionaryGetFieldsResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a dictionary increment request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CacheDictionaryIncrementResponseSuccess
 * * CacheDictionaryIncrementResponseError
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
abstract class CacheDictionaryIncrementResponse extends ResponseBase
{
    /**
     * @return CacheDictionaryIncrementResponseSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): CacheDictionaryIncrementResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheDictionaryIncrementResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): CacheDictionaryIncrementResponseError|null
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
class CacheDictionaryIncrementResponseSuccess extends CacheDictionaryIncrementResponse
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
class CacheDictionaryIncrementResponseError extends CacheDictionaryIncrementResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a dictionary remove field request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CacheDictionaryRemoveFieldResponseSuccess
 * * CacheDictionaryRemoveFieldResponseError
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
abstract class CacheDictionaryRemoveFieldResponse extends ResponseBase
{
    /**
     * @return CacheDictionaryRemoveFieldResponseSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): CacheDictionaryRemoveFieldResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheDictionaryRemoveFieldResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): CacheDictionaryRemoveFieldResponseError|null
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
class CacheDictionaryRemoveFieldResponseSuccess extends CacheDictionaryRemoveFieldResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class CacheDictionaryRemoveFieldResponseError extends CacheDictionaryRemoveFieldResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a dictionary remove fields request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CacheDictionaryRemoveFieldsResponseSuccess
 * * CacheDictionaryRemoveFieldsResponseError
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
abstract class CacheDictionaryRemoveFieldsResponse extends ResponseBase
{
    /**
     * @return CacheDictionaryRemoveFieldsResponseSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): CacheDictionaryRemoveFieldsResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheDictionaryRemoveFieldsResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): CacheDictionaryRemoveFieldsResponseError|null
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
class CacheDictionaryRemoveFieldsResponseSuccess extends CacheDictionaryRemoveFieldsResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class CacheDictionaryRemoveFieldsResponseError extends CacheDictionaryRemoveFieldsResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a set add elements request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CacheSetAddElementsResponseSuccess
 * * CacheSetAddElementsResponseError
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
abstract class CacheSetAddElementResponse extends ResponseBase
{
    /**
     * @return CacheSetAddElementResponseSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): CacheSetAddElementResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheSetAddElementResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): CacheSetAddElementResponseError|null
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
class CacheSetAddElementResponseSuccess extends CacheSetAddElementResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class CacheSetAddElementResponseError extends CacheSetAddElementResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a set fetch request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CacheSetFetchResponseHit
 * * CacheSetFetchResponseMiss
 * * CacheSetFetchResponseError
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
abstract class CacheSetFetchResponse extends ResponseBase
{

    /**
     * @return CacheSetFetchResponseHit|null Returns the hit subtype if the request returned an error and null otherwise.
     */
    public function asHit(): CacheSetFetchResponseHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheSetFetchResponseMiss|null Returns the miss subtype if the request returned an error and null otherwise.
     */
    public function asMiss(): CacheSetFetchResponseMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheSetFetchResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): CacheSetFetchResponseError|null
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
class CacheSetFetchResponseHit extends CacheSetFetchResponse
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
class CacheSetFetchResponseMiss extends CacheSetFetchResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class CacheSetFetchResponseError extends CacheSetFetchResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a set remove element request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CacheSetRemoveElementResponseSuccess
 * * CacheSetRemoveElementResponseError
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
abstract class CacheSetRemoveElementResponse extends ResponseBase
{
    /**
     * @return CacheSetRemoveElementResponseSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): CacheSetRemoveElementResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CacheSetRemoveElementResponseError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): CacheSetRemoveElementResponseError|null
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
class CacheSetRemoveElementResponseSuccess extends CacheSetRemoveElementResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class CacheSetRemoveElementResponseError extends CacheSetRemoveElementResponse
{
    use ErrorBody;
}
