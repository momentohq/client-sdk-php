<?php
namespace Momento\Cache\CacheOperationTypes;

use Cache_client\_GetResponse;
use Control_client\_ListCachesResponse;
use Cache_client\_SetResponse;
use Momento\Cache\Errors\MomentoErrorCode;
use Momento\Cache\Errors\SdkError;

trait ErrorBody {
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

    public function innerException() : SdkError
    {
        return $this->innerException;
    }

    public function errorCode() : MomentoErrorCode
    {
        return $this->errorCode;
    }

    public function message() : string
    {
        return $this->message;
    }
}

abstract class ResponseBase
{
    protected string $baseType;

    public function __construct() {
        $this->baseType = get_parent_class($this);
    }

    protected function isError() : bool
    {
        return get_class($this) == "{$this->baseType}Error";
    }

    protected function isSuccess() : bool
    {
        return get_class($this) == "{$this->baseType}Success";
    }

    protected function isAlreadyExists() : bool
    {
        return get_class($this) == "{$this->baseType}AlreadyExists";
    }

    protected function isHit() : bool
    {
        return get_class($this) == "{$this->baseType}Hit";
    }

    protected function isMiss() : bool
    {
        return get_class($this) == "{$this->baseType}Miss";
    }
}

abstract class CreateCacheResponse extends ResponseBase {

    public function asSuccess() : CreateCacheResponseSuccess|null
    {
        if ($this->isSuccess())
        {
            return $this;
        }
        return null;
    }

    public function asError() : CreateCacheResponseError|null
    {
        if ($this->isError())
        {
            return $this;
        }
        return null;
    }

    public function asAlreadyExists() : CreateCacheResponseAlreadyExists|null
    {
        if ($this->isAlreadyExists())
        {
            return $this;
        }
        return null;
    }

}

class CreateCacheResponseSuccess extends CreateCacheResponse { }

class CreateCacheResponseAlreadyExists extends CreateCacheResponse { }

class CreateCacheResponseError extends CreateCacheResponse
{
    use ErrorBody;
}

abstract class DeleteCacheResponse extends ResponseBase {
    public function asSuccess() : DeleteCacheResponseSuccess|null
    {
        if ($this->isSuccess())
        {
            return $this;
        }
        return null;
    }

    public function asError() : DeleteCacheResponseError|null
    {
        if ($this->isError())
        {
            return $this;
        }
        return null;
    }
}

class DeleteCacheResponseSuccess extends DeleteCacheResponse { }

class DeleteCacheResponseError extends DeleteCacheResponse
{
    use ErrorBody;
}

abstract class ListCachesResponse extends ResponseBase {

    public function asSuccess() : ListCachesResponseSuccess|null
    {
        if ($this->isSuccess())
        {
            return $this;
        }
        return null;
    }

    public function asError() : ListCachesResponseError|null
    {
        if ($this->isError())
        {
            return $this;
        }
        return null;
    }

}

class ListCachesResponseSuccess extends ListCachesResponse
{
    private string $nextToken;
    private array $caches = [];

    public function __construct(_ListCachesResponse $response) {
        parent::__construct();
        $this->nextToken = $response->getNextToken() ? $response->getNextToken() : "";
        foreach ($response->getCache() as $cache) {
            $this->caches[] = new CacheInfo($cache);
        }
    }

    public function caches() : array
    {
        return $this->caches;
    }

    public function nextToken() : string
    {
        return $this->nextToken;
    }
}

class ListCachesResponseError extends ListCachesResponse
{
    use ErrorBody;
}

class CacheInfo
{
    private string $name;

    public function __construct($grpcListedCache) {
        $this->name = $grpcListedCache->getCacheName();
    }

    public function name() : string {
        return $this->name;
    }
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

    public function key() : string {
        return $this->key;
    }

    public function value() : string {
        return $this->value;
    }

}

class CacheSetResponseError extends CacheSetResponse
{
    use ErrorBody;
}

abstract class CacheGetResponse extends ResponseBase
{

    public function asHit() : CacheGetResponseHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    public function asMiss() : CacheGetResponseMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    public function asError() : CacheGetResponseError|null
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

    public function __construct(_GetResponse $grpcGetResponse) {
        parent::__construct();
        $this->value = $grpcGetResponse->getCacheBody();
    }

    public function value() : string
    {
        return $this->value;
    }

}

class CacheGetResponseMiss extends CacheGetResponse { }

class CacheGetResponseError extends CacheGetResponse
{
    use ErrorBody;
}

abstract class CacheDeleteResponse extends ResponseBase {
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

class CacheDeleteResponseSuccess extends CacheDeleteResponse { }

class CacheDeleteResponseError extends CacheDeleteResponse
{
    use ErrorBody;
}
