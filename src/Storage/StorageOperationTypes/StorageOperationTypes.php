<?php
declare(strict_types=1);

namespace Momento\Storage\StorageOperationTypes;

use Control_client\_ListStoresResponse;
use Momento\Cache\CacheOperationTypes\ErrorBody;
use Momento\Cache\CacheOperationTypes\ResponseBase;
use Store\_StoreGetResponse;

class StoreInfo
{
    private string $name;

    public function __construct($grpcListedStore)
    {
        $this->name = $grpcListedStore->getStoreName();
    }

    public function name(): string
    {
        return $this->name;
    }
}

/**
 * Parent response type for a create store request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * CreateStoreSuccess
 * * CreateStoreAlreadyExists
 * * CreateStoreError
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
abstract class CreateStoreResponse extends ResponseBase
{

    /**
     * @return CreateStoreSuccess|null Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?CreateStoreSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CreateStoreError|null Returns the error subtype if the request was successful and null otherwise.
     */
    public function asError(): ?CreateStoreError
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }

    /**
     * @return CreateStoreAlreadyExists|null Returns the "already exists" subtype if the request was successful and null otherwise.
     */
    public function asAlreadyExists(): ?CreateStoreAlreadyExists
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
class CreateStoreSuccess extends CreateStoreResponse
{
}

/**
 * Indicates that a cache with the requested name has already been created in the requesting account.
 */
class CreateStoreAlreadyExists extends CreateStoreResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class CreateStoreError extends CreateStoreResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a delete store request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * DeleteStoreSuccess
 * * DeleteStoreError
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
abstract class DeleteStoreResponse extends ResponseBase
{
    /**
     * @return DeleteStoreSuccess|null  Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?DeleteStoreSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return DeleteStoreError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?DeleteStoreError
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
class DeleteStoreSuccess extends DeleteStoreResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class DeleteStoreError extends DeleteStoreResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a list stores request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *  * ListStoresSuccess
 *  * ListStoresError
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
abstract class ListStoresResponse extends ResponseBase
{

    /**
     * @return ListStoresSuccess|null  Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?ListStoresSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return ListStoresError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?ListStoresError
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
class ListStoresSuccess extends ListStoresResponse
{
    private string $nextToken;
    private array $stores = [];

    public function __construct(_ListStoresResponse $response)
    {
        parent::__construct();
        $this->nextToken = $response->getNextToken() ? $response->getNextToken() : "";
        foreach ($response->getStore() as $store) {
            $this->stores[] = new StoreInfo($store);
        }
    }

    /**
     * @return array List of caches available to the user represented as CacheInfo objects.
     */
    public function stores(): array
    {
        return $this->stores;
    }

    public function __toString()
    {
        $storeNames = array_map(fn($i) => $i->name(), $this->stores);
        return get_class($this) . ": " . join(', ', $storeNames);
    }
}

/**
 * Contains information about an error returned from the request.
 */
class ListStoresError extends ListStoresResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a storage set request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * StorageSetSuccess
 * * StorageSetError
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
abstract class StorageSetResponse extends ResponseBase
{
    /**
     * @return StorageSetSuccess|null  Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?StorageSetSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return StorageSetError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?StorageSetError
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
class StorageSetSuccess extends StorageSetResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class StorageSetError extends StorageSetResponse
{
    use ErrorBody;
}

abstract class StorageValueType
{
    public const STRING = "STRING";
    public const INTEGER = "INTEGER";
    public const DOUBLE = "DOUBLE";
}

/**
 * Parent response type for a storage get request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * StorageGetSuccess
 * * StorageGetError
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
abstract class StorageGetResponse extends ResponseBase
{
    /**
     * @return StorageGetSuccess|null  Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?StorageGetSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return StorageGetError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?StorageGetError
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
class StorageGetSuccess extends StorageGetResponse
{
    private string $type;
    private ?string $value_string = null;
    private ?int $value_int = null;
    private ?float $value_double = null;

    public function __construct(_StoreGetResponse $grpcResponse)
    {
        parent::__construct();
        $value = $grpcResponse->getValue();
        if ($value->hasStringValue()) {
            $this->type = StorageValueType::STRING;
            $this->value_string = $value->getStringValue();
        } elseif ($value->hasIntegerValue()) {
            $this->type = StorageValueType::INTEGER;
            $this->value_int = $value->getIntegerValue();
        } elseif ($value->hasDoubleValue()) {
            $this->type = StorageValueType::DOUBLE;
            $this->value_double = $value->getDoubleValue();
        }
    }

    public function type(): string
    {
        return $this->type;
    }

    public function tryGetString(): ?string
    {
        return $this->value_string;
    }

    public function tryGetInteger(): ?int
    {
        return $this->value_int;
    }

    public function tryGetDouble(): ?float
    {
        return $this->value_double;
    }
}

/**
 * Contains information about an error returned from the request.
 */
class StorageGetError extends StorageGetResponse
{
    use ErrorBody;
}

/**
 * Parent response type for a storage delete request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * StorageDeleteSuccess
 * * StorageDeleteError
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
abstract class StorageDeleteResponse extends ResponseBase
{
    /**
     * @return StorageDeleteSuccess|null  Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?StorageDeleteSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return StorageDeleteError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?StorageDeleteError
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
class StorageDeleteSuccess extends StorageDeleteResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class StorageDeleteError extends StorageDeleteResponse
{
    use ErrorBody;
}
