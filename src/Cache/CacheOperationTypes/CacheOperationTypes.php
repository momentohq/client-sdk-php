<?php

namespace Momento\Cache\CacheOperationTypes;

use Cache_client\_DictionaryGetResponse;
use Cache_client\_GetResponse;
use Cache_client\_ListFetchResponse;
use Cache_client\_SetResponse;
use Control_client\_ListCachesResponse;
use Momento\Cache\Errors\MomentoErrorCode;
use Momento\Cache\Errors\SdkError;

trait ErrorBody
{
    private SdkError $innerException;
    private MomentoErrorCode $errorCode;
    private string $message;

    public function __construct(SdkError $error)
    {
        parent::__construct();
        $this->innerException = $error;
        $this->message = "{$error->messageWrapper}: {$error->getMessage()}";
        $this->errorCode = $error->errorCode;
    }

    public function innerException(): SdkError
    {
        return $this->innerException;
    }

    public function errorCode(): MomentoErrorCode
    {
        return $this->errorCode;
    }

    public function message(): string
    {
        return $this->message;
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

    public function __construct()
    {
        $this->baseType = get_parent_class($this);
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
}

abstract class CreateCacheResponse extends ResponseBase
{

    public function asSuccess(): CreateCacheResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    public function asError(): CreateCacheResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }

    public function asAlreadyExists(): CreateCacheResponseAlreadyExists|null
    {
        if ($this->isAlreadyExists()) {
            return $this;
        }
        return null;
    }

}

class CreateCacheResponseSuccess extends CreateCacheResponse
{
}

class CreateCacheResponseAlreadyExists extends CreateCacheResponse
{
}

class CreateCacheResponseError extends CreateCacheResponse
{
    use ErrorBody;
}

abstract class DeleteCacheResponse extends ResponseBase
{
    public function asSuccess(): DeleteCacheResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    public function asError(): DeleteCacheResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class DeleteCacheResponseSuccess extends DeleteCacheResponse
{
}

class DeleteCacheResponseError extends DeleteCacheResponse
{
    use ErrorBody;
}

abstract class ListCachesResponse extends ResponseBase
{

    public function asSuccess(): ListCachesResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    public function asError(): ListCachesResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }

}

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

    public function caches(): array
    {
        return $this->caches;
    }

    public function nextToken(): string
    {
        return $this->nextToken;
    }
}

class ListCachesResponseError extends ListCachesResponse
{
    use ErrorBody;
}

abstract class CacheSetResponse extends ResponseBase
{
    public function asSuccess(): CacheSetResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheSetResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class CacheSetResponseSuccess extends CacheSetResponse
{
    private string $key;
    private string $value;

    public function __construct(_SetResponse $grpcSetResponse, string $key, string $value)
    {
        parent::__construct();
        $this->key = $key;
        $this->value = $value;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function value(): string
    {
        return $this->value;
    }

}

class CacheSetResponseError extends CacheSetResponse
{
    use ErrorBody;
}

abstract class CacheGetResponse extends ResponseBase
{

    public function asHit(): CacheGetResponseHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    public function asMiss(): CacheGetResponseMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheGetResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class CacheGetResponseHit extends CacheGetResponse
{
    private string $value;

    public function __construct(_GetResponse $grpcGetResponse)
    {
        parent::__construct();
        $this->value = $grpcGetResponse->getCacheBody();
    }

    public function value(): string
    {
        return $this->value;
    }

}

class CacheGetResponseMiss extends CacheGetResponse
{
}

class CacheGetResponseError extends CacheGetResponse
{
    use ErrorBody;
}

abstract class CacheDeleteResponse extends ResponseBase
{
    public function asSuccess(): CacheDeleteResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheDeleteResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class CacheDeleteResponseSuccess extends CacheDeleteResponse
{
}

class CacheDeleteResponseError extends CacheDeleteResponse
{
    use ErrorBody;
}

abstract class CacheListFetchResponse extends ResponseBase
{
    public function asHit(): CacheListFetchResponseHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    public function asMiss(): CacheListFetchResponseMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheListFetchResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class CacheListFetchResponseHit extends CacheListFetchResponse
{

    private array $values = [];

    public function __construct(_ListFetchResponse $response)
    {
        parent::__construct();
        if ($response->getFound()) {
            foreach ($response->getFound()->getValues() as $value) {
                $this->values[] = $value;
            }
        }
    }

    public function values(): array
    {
        return $this->values;
    }

}

class CacheListFetchResponseMiss extends CacheListFetchResponse
{
}

class CacheListFetchResponseError extends CacheListFetchResponse
{
    use ErrorBody;
}

abstract class CacheListPushFrontResponse extends ResponseBase
{
    public function asSuccess(): CacheListPushFrontResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheListPushFrontResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class CacheListPushFrontResponseSuccess extends CacheListPushFrontResponse
{
}

class CacheListPushFrontResponseError extends CacheListPushFrontResponse
{
    use ErrorBody;
}

abstract class CacheListPushBackResponse extends ResponseBase
{
    public function asSuccess(): CacheListPushBackResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheListPushBackResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class CacheListPushBackResponseSuccess extends CacheListPushBackResponse
{
}

class CacheListPushBackResponseError extends CacheListPushBackResponse
{
    use ErrorBody;
}

abstract class CacheDictionarySetResponse extends ResponseBase
{
    public function asSuccess(): CacheDictionarySetResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheDictionarySetResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class CacheDictionarySetResponseSuccess extends CacheDictionarySetResponse
{
}

class CacheDictionarySetResponseError extends CacheDictionarySetResponse
{
    use ErrorBody;
}

abstract class CacheDictionaryGetResponse extends ResponseBase
{
    public function asHit(): CacheDictionaryGetResponseHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    public function asMiss(): CacheDictionaryGetResponseMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheDictionaryGetResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class CacheDictionaryGetResponseHit extends CacheDictionaryGetResponse
{
    private string $value;

    public function __construct(_DictionaryGetResponse $response)
    {
        parent::__construct();
        $this->value = $response->getFound()->getItems()[0]->getCacheBody();

    }

    public function value(): string
    {
        return $this->value;
    }

}

class CacheDictionaryGetResponseMiss extends CacheDictionaryGetResponse
{
}

class CacheDictionaryGetResponseError extends CacheDictionaryGetResponse
{
    use ErrorBody;
}

abstract class CacheDictionaryDeleteResponse extends ResponseBase
{
    public function asSuccess(): CacheDictionaryDeleteResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheDictionaryDeleteResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class CacheDictionaryDeleteResponseSuccess extends CacheDictionaryDeleteResponse
{
}

class CacheDictionaryDeleteResponseError extends CacheDictionaryDeleteResponse
{
    use ErrorBody;
}
