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
 * Parent response type for a storage put request. The
 * response object is resolved to a type-safe object of one of
 * the following subtypes:
 *
 * * StoragePutSuccess
 * * StoragePutError
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
abstract class StoragePutResponse extends ResponseBase
{
    /**
     * @return StoragePutSuccess|null  Returns the success subtype if the request was successful and null otherwise.
     */
    public function asSuccess(): ?StoragePutSuccess
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    /**
     * @return StoragePutError|null Returns the error subtype if the request returned an error and null otherwise.
     */
    public function asError(): ?StoragePutError
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
class StoragePutSuccess extends StoragePutResponse
{
}

/**
 * Contains information about an error returned from the request.
 */
class StoragePutError extends StoragePutResponse
{
    use ErrorBody;
}

/**
 * Represents the data type of a stored value.
 */
abstract class StorageValueType
{
    public const STRING = "STRING";
    public const INTEGER = "INTEGER";
    public const DOUBLE = "DOUBLE";
    public const BYTES = "BYTES";
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
 *     if (!$success->found()) {
 *       print("Value not found in the store\n");
 *     } elseif ($success->type() == StorageValueType::STRING) {
 *       // other storage types are INTEGER, BYTES, and DOUBLE
 *       print("Got string value: " . $success->valueString() . "\n");
 *     }
 * } elseif ($error = $response->asError())
 *     // handle error as appropriate
 * }
 * // If you know you are getting a particular type, you can use the following shorthand
 * // to avoid the type checking for the response and the value. If the response was an error,
 * // an exception will be thrown when any of the response data is requested:
 * try {
 *   if ($response->found()) {
 *     print("Got string value: " . $response->value() . "\n");
 *   }
 * } catch (SdkError $e) {
 *   // the request was unsuccessful, and the response is an error
 *   print("Error getting value: " . $response->asError()->message() . "\n");
 * }
 * </code>
 */
abstract class StorageGetResponse extends ResponseBase
{
    protected bool $found = true;
    protected ?string $type = null;
    protected ?string $value_string = null;
    protected ?string $value_bytes = null;
    protected ?int $value_int = null;
    protected ?float $value_double = null;

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

    public function found(): ?bool
    {
        return $this->found;
    }

    public function type(): ?string
    {
        return $this->type;
    }

    /**
     * @return float|int|string|null
     */
    public function value()
    {
        switch ($this->type) {
            case StorageValueType::STRING:
                return $this->value_string;
            case StorageValueType::INTEGER:
                return $this->value_int;
            case StorageValueType::DOUBLE:
                return $this->value_double;
            case StorageValueType::BYTES:
                return $this->value_bytes;
            default:
                return null;
        }
    }

    public function valueString(): ?string
    {
        return $this->value_string;
    }

    public function valueInteger(): ?int
    {
        return $this->value_int;
    }

    public function valueDouble(): ?float
    {
        return $this->value_double;
    }

    public function valueBytes(): ?string
    {
        return $this->value_bytes;
    }

}

/**
 * Indicates that the request that generated it was successful.
 */
class StorageGetSuccess extends StorageGetResponse
{
    public function __construct(?_StoreGetResponse $grpcResponse=null)
    {
        parent::__construct();
        if (!$grpcResponse) {
            $this->found = false;
            return;
        }
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
        } elseif ($value->hasBytesValue()) {
            $this->type = StorageValueType::BYTES;
            $this->value_bytes = $value->getBytesValue();
        }
    }

    public function __toString()
    {
        $value = null;
        switch ($this->type) {
            case StorageValueType::STRING:
                $value = $this->shortValue($this->value_string);
                break;
            case StorageValueType::INTEGER:
                $value = $this->value_int;
                break;
            case StorageValueType::DOUBLE:
                $value = $this->value_double;
                break;
            case StorageValueType::BYTES:
                $value = $this->shortValue($this->value_bytes);
                break;
        }
        return parent::__toString() . ": $value";
    }
}

/**
 * Contains information about an error returned from the request.
 */
class StorageGetError extends StorageGetResponse
{
    use ErrorBody;

    public function type(): ?string
    {
        return null;
    }

    public function found(): ?bool
    {
        return null;
    }

    public function value()
    {
        throw $this->innerException();
    }

    public function valueString(): ?string
    {
        throw $this->innerException();
    }

    public function valueInteger(): ?int
    {
        throw $this->innerException();
    }

    public function valueDouble(): ?float
    {
        throw $this->innerException();
    }

    public function valueBytes(): ?string
    {
        throw $this->innerException();
    }
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
