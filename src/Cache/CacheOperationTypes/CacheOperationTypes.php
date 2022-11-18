<?php
declare(strict_types=1);

namespace Momento\Cache\CacheOperationTypes;

use Cache_client\_DictionaryFetchResponse;
use Cache_client\_DictionaryGetResponse;
use Cache_client\_DictionaryIncrementResponse;
use Cache_client\_GetResponse;
use Cache_client\_ListFetchResponse;
use Cache_client\_ListLengthResponse;
use Cache_client\_ListPopBackResponse;
use Cache_client\_ListPopFrontResponse;
use Cache_client\_ListPushBackResponse;
use Cache_client\_ListPushFrontResponse;
use Cache_client\_SetFetchResponse;
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

    public function innerException(): SdkError
    {
        return $this->innerException;
    }

    public function errorCode(): string
    {
        return $this->errorCode;
    }

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

    protected function shortValue(string $value): string
    {
        if (strlen($value) <= $this->valueSubstringLength) {
            return $value;
        }
        return mb_substr($value, 0, $this->valueSubstringLength) . "...";
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

    public function __toString()
    {
        $cacheNames = array_map(fn($i) => $i->name(), $this->caches);
        return get_class($this) . ": " . join(', ', $cacheNames);
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

    public function valueString(): string
    {
        return $this->value;
    }

    public function __toString()
    {
        return get_class($this) . ": key {$this->shortValue($this->key)} = {$this->shortValue($this->value)}";
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

    public function valueString(): string
    {
        return $this->value;
    }

    public function __toString()
    {
        return parent::__toString() . ": {$this->shortValue($this->value)}";
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

    public function valuesArray(): array
    {
        return $this->values;
    }

    public function __toString()
    {
        return parent::__toString() . ": {$this->count} items";
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
    private int $listLength;

    public function __construct(_ListPushFrontResponse $response)
    {
        parent::__construct();
        $this->listLength = $response->getListLength();
    }

    public function listLength(): int
    {
        return $this->listLength;
    }

    public function __toString()
    {
        return parent::__toString() . ": " . $this->listLength . " items";
    }
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
    private int $listLength;

    public function __construct(_ListPushBackResponse $response)
    {
        parent::__construct();
        $this->listLength = $response->getListLength();
    }

    public function listLength(): int
    {
        return $this->listLength;
    }

    public function __toString()
    {
        return parent::__toString() . ": " . $this->listLength . " items";
    }
}

class CacheListPushBackResponseError extends CacheListPushBackResponse
{
    use ErrorBody;
}

abstract class CacheListPopFrontResponse extends ResponseBase
{
    public function asHit(): CacheListPopFrontResponseHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    public function asMiss(): CacheListPopFrontResponseMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheListPopFrontResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class CacheListPopFrontResponseHit extends CacheListPopFrontResponse
{
    private string $value;

    public function __construct(_ListPopFrontResponse $response)
    {
        parent::__construct();
        $this->value = $response->getFound()->getFront();
    }

    public function valueString(): string
    {
        return $this->value;
    }

    public function __toString()
    {
        return parent::__toString() . ": {$this->shortValue($this->value)}";
    }
}

class CacheListPopFrontResponseMiss extends CacheListPopFrontResponse
{
}

class CacheListPopFrontResponseError extends CacheListPopFrontResponse
{
    use ErrorBody;
}

abstract class CacheListPopBackResponse extends ResponseBase
{
    public function asHit(): CacheListPopBackResponseHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    public function asMiss(): CacheListPopBackResponseMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheListPopBackResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class CacheListPopBackResponseHit extends CacheListPopBackResponse
{
    private string $value;

    public function __construct(_ListPopBackResponse $response)
    {
        parent::__construct();
        $this->value = $response->getFound()->getBack();
    }

    public function valueString(): string
    {
        return $this->value;
    }

    public function __toString()
    {
        return parent::__toString() . ": {$this->shortValue($this->value)}";
    }
}

class CacheListPopBackResponseMiss extends CacheListPopBackResponse
{
}

class CacheListPopBackResponseError extends CacheListPopBackResponse
{
    use ErrorBody;
}

abstract class CacheListRemoveValueResponse extends ResponseBase
{
    public function asSuccess(): CacheListRemoveValueResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheListRemoveValueResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class CacheListRemoveValueResponseSuccess extends CacheListRemoveValueResponse
{
}

class CacheListRemoveValueResponseError extends CacheListRemoveValueResponse
{
    use ErrorBody;
}

abstract class CacheListLengthResponse extends ResponseBase
{
    public function asSuccess(): CacheListLengthResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheListLengthResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class CacheListLengthResponseSuccess extends CacheListLengthResponse
{
    private int $length;

    public function __construct(_ListLengthResponse $response)
    {
        parent::__construct();
        $this->length = $response->getFound() ? $response->getFound()->getLength() : 0;
    }

    public function length(): int
    {
        return $this->length;
    }

    public function __toString()
    {
        return parent::__toString() . ": {$this->length}";
    }
}

class CacheListLengthResponseError extends CacheListLengthResponse
{
    use ErrorBody;
}

abstract class CacheListEraseResponse extends ResponseBase
{
    public function asSuccess(): CacheListEraseResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheListEraseResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class CacheListEraseResponseSuccess extends CacheListEraseResponse
{
}

class CacheListEraseResponseError extends CacheListEraseResponse
{
    use ErrorBody;
}

abstract class CacheDictionarySetFieldResponse extends ResponseBase
{
    public function asSuccess(): CacheDictionarySetFieldResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheDictionarySetFieldResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class CacheDictionarySetFieldResponseSuccess extends CacheDictionarySetFieldResponse
{
}

class CacheDictionarySetFieldResponseError extends CacheDictionarySetFieldResponse
{
    use ErrorBody;
}

abstract class CacheDictionaryGetFieldResponse extends ResponseBase
{
    public function asHit(): CacheDictionaryGetFieldResponseHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    public function asMiss(): CacheDictionaryGetFieldResponseMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheDictionaryGetFieldResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class CacheDictionaryGetFieldResponseHit extends CacheDictionaryGetFieldResponse
{
    private string $value;

    public function __construct(_DictionaryGetResponse $response = null, ?string $cacheBody = null)
    {
        parent::__construct();
        if (!is_null($response) && is_null($cacheBody)) {
            $this->value = $response->getFound()->getItems()[0]->getCacheBody();
        }
        if (is_null($response) && !is_null($cacheBody)) {
            $this->value = $cacheBody;
        }
    }

    public function valueString(): string
    {
        return $this->value;
    }

    public function __toString()
    {
        return parent::__toString() . ": " . $this->shortValue($this->value);
    }
}

class CacheDictionaryGetFieldResponseMiss extends CacheDictionaryGetFieldResponse
{
}

class CacheDictionaryGetFieldResponseError extends CacheDictionaryGetFieldResponse
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

abstract class CacheDictionaryFetchResponse extends ResponseBase
{
    public function asHit(): CacheDictionaryFetchResponseHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    public function asMiss(): CacheDictionaryFetchResponseMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheDictionaryFetchResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

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

class CacheDictionaryFetchResponseMiss extends CacheDictionaryFetchResponse
{
}

class CacheDictionaryFetchResponseError extends CacheDictionaryFetchResponse
{
    use ErrorBody;
}

abstract class CacheDictionarySetFieldsResponse extends ResponseBase
{
    public function asSuccess(): CacheDictionarySetFieldsResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheDictionarySetFieldsResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class CacheDictionarySetFieldsResponseSuccess extends CacheDictionarySetFieldsResponse
{
}

class CacheDictionarySetFieldsResponseError extends CacheDictionarySetFieldsResponse
{
    use ErrorBody;
}

abstract class CacheDictionaryGetFieldsResponse extends ResponseBase
{
    public function asHit(): CacheDictionaryGetFieldsResponseHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    public function asMiss(): CacheDictionaryGetFieldsResponseMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }


    public function asError(): CacheDictionaryGetFieldsResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

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
                $this->responses[] = new CacheDictionaryGetFieldResponseHit(null, $response->getCacheBody());
                $this->valuesDictionary[$fields[$counter]] = $response->getCacheBody();
            } elseif ($response->getResult() == ECacheResult::Miss) {
                $this->responses[] = new CacheDictionaryGetFieldResponseMiss();
            } else {
                $this->responses[] = new CacheDictionaryGetFieldResponseError(new UnknownError(strval($response->getResult())));
            }
            $counter++;
        }
    }

    public function responses(): array
    {
        return $this->responses;
    }

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

class CacheDictionaryGetFieldsResponseMiss extends CacheDictionaryGetFieldsResponse
{
}

class CacheDictionaryGetFieldsResponseError extends CacheDictionaryGetFieldsResponse
{
    use ErrorBody;
}

abstract class CacheDictionaryIncrementResponse extends ResponseBase
{
    public function asSuccess(): CacheDictionaryIncrementResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheDictionaryIncrementResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class CacheDictionaryIncrementResponseSuccess extends CacheDictionaryIncrementResponse
{

    private int $value;

    public function __construct(_DictionaryIncrementResponse $response)
    {
        parent::__construct();
        $this->value = $response->getValue();
    }

    public function valueInt(): int
    {
        return $this->value;
    }

    public function __toString()
    {
        return parent::__toString() . ": " . $this->value;
    }
}

class CacheDictionaryIncrementResponseError extends CacheDictionaryIncrementResponse
{
    use ErrorBody;
}

abstract class CacheDictionaryRemoveFieldResponse extends ResponseBase
{
    public function asSuccess(): CacheDictionaryRemoveFieldResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheDictionaryRemoveFieldResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class CacheDictionaryRemoveFieldResponseSuccess extends CacheDictionaryRemoveFieldResponse
{
}

class CacheDictionaryRemoveFieldResponseError extends CacheDictionaryRemoveFieldResponse
{
    use ErrorBody;
}

abstract class CacheDictionaryRemoveFieldsResponse extends ResponseBase
{
    public function asSuccess(): CacheDictionaryRemoveFieldsResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheDictionaryRemoveFieldsResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class CacheDictionaryRemoveFieldsResponseSuccess extends CacheDictionaryRemoveFieldsResponse
{
}

class CacheDictionaryRemoveFieldsResponseError extends CacheDictionaryRemoveFieldsResponse
{
    use ErrorBody;
}

abstract class CacheSetAddElementResponse extends ResponseBase
{
    public function asSuccess(): CacheSetAddElementResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheSetAddElementResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class CacheSetAddElementResponseSuccess extends CacheSetAddElementResponse
{
}

class CacheSetAddElementResponseError extends CacheSetAddElementResponse
{
    use ErrorBody;
}

abstract class CacheSetFetchResponse extends ResponseBase
{

    public function asHit(): CacheSetFetchResponseHit|null
    {
        if ($this->isHit()) {
            return $this;
        }
        return null;
    }

    public function asMiss(): CacheSetFetchResponseMiss|null
    {
        if ($this->isMiss()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheSetFetchResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

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

    public function valueArray(): array
    {
        return $this->stringSet;
    }

    public function __toString()
    {
        $numElements = count($this->stringSet);
        return parent::__toString() . ": $numElements elements";
    }
}

class CacheSetFetchResponseMiss extends CacheSetFetchResponse
{
}

class CacheSetFetchResponseError extends CacheSetFetchResponse
{
    use ErrorBody;
}

abstract class CacheSetRemoveElementResponse extends ResponseBase
{
    public function asSuccess(): CacheSetRemoveElementResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheSetRemoveElementResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class CacheSetRemoveElementResponseSuccess extends CacheSetRemoveElementResponse
{
}

class CacheSetRemoveElementResponseError extends CacheSetRemoveElementResponse
{
    use ErrorBody;
}

abstract class CacheSetDeleteResponse extends ResponseBase
{
    public function asSuccess(): CacheSetDeleteResponseSuccess|null
    {
        if ($this->isSuccess()) {
            return $this;
        }
        return null;
    }

    public function asError(): CacheSetDeleteResponseError|null
    {
        if ($this->isError()) {
            return $this;
        }
        return null;
    }
}

class CacheSetDeleteResponseSuccess extends CacheSetDeleteResponse
{
}

class CacheSetDeleteResponseError extends CacheSetDeleteResponse
{
    use ErrorBody;
}
