<?php

namespace Momento\Cache;

use Cache_client\_DeleteRequest;
use Cache_client\_DictionaryDeleteRequest;
use Cache_client\_DictionaryDeleteRequest\All;
use Cache_client\_DictionaryFieldValuePair;
use Cache_client\_DictionaryGetRequest;
use Cache_client\_DictionarySetRequest;
use Cache_client\_GetRequest;
use Cache_client\_ListEraseRequest;
use Cache_client\_ListFetchRequest;
use Cache_client\_ListLengthRequest;
use Cache_client\_ListPopBackRequest;
use Cache_client\_ListPopFrontRequest;
use Cache_client\_ListPushBackRequest;
use Cache_client\_ListPushFrontRequest;
use Cache_client\_ListRange;
use Cache_client\_ListRemoveRequest;
use Cache_client\_SetRequest;
use Cache_client\ECacheResult;
use Exception;
use Grpc\UnaryCall;
use Momento\Cache\CacheOperationTypes\CacheDeleteResponse;
use Momento\Cache\CacheOperationTypes\CacheDeleteResponseError;
use Momento\Cache\CacheOperationTypes\CacheDeleteResponseSuccess;
use Momento\Cache\CacheOperationTypes\CacheDictionaryDeleteResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryDeleteResponseError;
use Momento\Cache\CacheOperationTypes\CacheDictionaryDeleteResponseSuccess;
use Momento\Cache\CacheOperationTypes\CacheDictionaryGetResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryGetResponseError;
use Momento\Cache\CacheOperationTypes\CacheDictionaryGetResponseHit;
use Momento\Cache\CacheOperationTypes\CacheDictionaryGetResponseMiss;
use Momento\Cache\CacheOperationTypes\CacheDictionarySetResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionarySetResponseError;
use Momento\Cache\CacheOperationTypes\CacheDictionarySetResponseSuccess;
use Momento\Cache\CacheOperationTypes\CacheGetResponse;
use Momento\Cache\CacheOperationTypes\CacheGetResponseError;
use Momento\Cache\CacheOperationTypes\CacheGetResponseHit;
use Momento\Cache\CacheOperationTypes\CacheGetResponseMiss;
use Momento\Cache\CacheOperationTypes\CacheListEraseResponse;
use Momento\Cache\CacheOperationTypes\CacheListEraseResponseError;
use Momento\Cache\CacheOperationTypes\CacheListEraseResponseSuccess;
use Momento\Cache\CacheOperationTypes\CacheListFetchResponse;
use Momento\Cache\CacheOperationTypes\CacheListFetchResponseError;
use Momento\Cache\CacheOperationTypes\CacheListFetchResponseHit;
use Momento\Cache\CacheOperationTypes\CacheListFetchResponseMiss;
use Momento\Cache\CacheOperationTypes\CacheListLengthResponse;
use Momento\Cache\CacheOperationTypes\CacheListLengthResponseError;
use Momento\Cache\CacheOperationTypes\CacheListLengthResponseSuccess;
use Momento\Cache\CacheOperationTypes\CacheListPopBackResponse;
use Momento\Cache\CacheOperationTypes\CacheListPopBackResponseError;
use Momento\Cache\CacheOperationTypes\CacheListPopBackResponseHit;
use Momento\Cache\CacheOperationTypes\CacheListPopBackResponseMiss;
use Momento\Cache\CacheOperationTypes\CacheListPopFrontResponse;
use Momento\Cache\CacheOperationTypes\CacheListPopFrontResponseError;
use Momento\Cache\CacheOperationTypes\CacheListPopFrontResponseHit;
use Momento\Cache\CacheOperationTypes\CacheListPopFrontResponseMiss;
use Momento\Cache\CacheOperationTypes\CacheListPushBackResponse;
use Momento\Cache\CacheOperationTypes\CacheListPushBackResponseError;
use Momento\Cache\CacheOperationTypes\CacheListPushBackResponseSuccess;
use Momento\Cache\CacheOperationTypes\CacheListPushFrontResponse;
use Momento\Cache\CacheOperationTypes\CacheListPushFrontResponseError;
use Momento\Cache\CacheOperationTypes\CacheListPushFrontResponseSuccess;
use Momento\Cache\CacheOperationTypes\CacheListRemoveValueResponse;
use Momento\Cache\CacheOperationTypes\CacheListRemoveValueResponseError;
use Momento\Cache\CacheOperationTypes\CacheListRemoveValueResponseSuccess;
use Momento\Cache\CacheOperationTypes\CacheSetResponse;
use Momento\Cache\CacheOperationTypes\CacheSetResponseError;
use Momento\Cache\CacheOperationTypes\CacheSetResponseSuccess;
use Momento\Cache\Errors\InternalServerError;
use Momento\Cache\Errors\SdkError;
use Momento\Cache\Errors\UnknownError;
use Momento\Utilities\_ErrorConverter;
use function Momento\Utilities\validateCacheName;
use function Momento\Utilities\validateDictionaryName;
use function Momento\Utilities\validateFieldName;
use function Momento\Utilities\validateListName;
use function Momento\Utilities\validateOperationTimeout;
use function Momento\Utilities\validateRange;
use function Momento\Utilities\validateTruncateSize;
use function Momento\Utilities\validateTtl;
use function Momento\Utilities\validateValueName;

class _ScsDataClient
{

    private static int $DEFAULT_DEADLINE_SECONDS = 5;
    // TODO: is looks like PHP gRPC wants microsecond timeout values,
    // but python's wanted seconds. Need to take a closer look to make sure
    // this is accurate.
    private static int $TIMEOUT_MULTIPLIER = 1000000;
    private int $deadline_seconds;
    private int $defaultTtlSeconds;
    private _DataGrpcManager $grpcManager;

    public function __construct(string $authToken, string $endpoint, int $defaultTtlSeconds, ?int $operationTimeoutMs)
    {
        validateTtl($defaultTtlSeconds);
        validateOperationTimeout($operationTimeoutMs);
        $this->defaultTtlSeconds = $defaultTtlSeconds;
        $this->deadline_seconds = $operationTimeoutMs ? $operationTimeoutMs / 1000.0 : self::$DEFAULT_DEADLINE_SECONDS;
        $this->grpcManager = new _DataGrpcManager($authToken, $endpoint);
    }

    private function ttlToMillis(?int $ttl = null): int
    {
        if (!$ttl) {
            $ttl = $this->defaultTtlSeconds;
        }
        validateTtl($ttl);
        return $ttl * 1000;
    }

    private function processCall(UnaryCall $call): mixed
    {
        [$response, $status] = $call->wait();
        if ($status->code !== 0) {
            throw _ErrorConverter::convert($status->code, $status->details, $call->getMetadata());
        }
        return $response;
    }

    public function set(string $cacheName, string $key, string $value, int $ttlSeconds = null): CacheSetResponse
    {
        try {
            validateCacheName($cacheName);
            $ttlMillis = $this->ttlToMillis($ttlSeconds);
            $setRequest = new _SetRequest();
            $setRequest->setCacheKey($key);
            $setRequest->setCacheBody($value);
            $setRequest->setTtlMilliseconds($ttlMillis);
            $call = $this->grpcManager->client->Set(
                $setRequest, ["cache" => [$cacheName]], ["timeout" => $this->deadline_seconds * self::$TIMEOUT_MULTIPLIER]
            );
            $response = $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheSetResponseError($e);
        } catch (Exception $e) {
            return new CacheSetResponseError(new UnknownError($e->getMessage()));
        }
        return new CacheSetResponseSuccess($response, $key, $value);
    }

    public function get(string $cacheName, string $key): CacheGetResponse
    {
        try {
            validateCacheName($cacheName);
            $getRequest = new _GetRequest();
            $getRequest->setCacheKey($key);
            $call = $this->grpcManager->client->Get(
                $getRequest, ["cache" => [$cacheName]], ["timeout" => $this->deadline_seconds * self::$TIMEOUT_MULTIPLIER]
            );
            $response = $this->processCall($call);
            $ecacheResult = $response->getResult();
            if ($ecacheResult == ECacheResult::Hit) {
                return new CacheGetResponseHit($response);
            } elseif ($ecacheResult == ECacheResult::Miss) {
                return new CacheGetResponseMiss();
            } else {
                throw new InternalServerError("CacheService returned an unexpected result: $ecacheResult");
            }
        } catch (SdkError $e) {
            return new CacheGetResponseError($e);
        } catch (Exception $e) {
            return new CacheGetResponseError(new UnknownError($e->getMessage()));
        }
    }

    public function delete(string $cacheName, string $key): CacheDeleteResponse
    {
        try {
            validateCacheName($cacheName);
            $deleteRequest = new _DeleteRequest();
            $deleteRequest->setCacheKey($key);
            $call = $this->grpcManager->client->Delete(
                $deleteRequest, ["cache" => [$cacheName]], ["timeout" => $this->deadline_seconds * self::$TIMEOUT_MULTIPLIER]
            );
            $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheDeleteResponseError($e);
        } catch (Exception $e) {
            return new CacheDeleteResponseError(new UnknownError($e->getMessage()));
        }
        return new CacheDeleteResponseSuccess();
    }

    public function listFetch(string $cacheName, string $listName): CacheListFetchResponse
    {
        try {
            validateCacheName($cacheName);
            validateListName($listName);
            $listFetchRequest = new _ListFetchRequest();
            $listFetchRequest->setListName($listName);
            $call = $this->grpcManager->client->ListFetch(
                $listFetchRequest, ["cache" => [$cacheName]], ["timeout" => $this->deadline_seconds * self::$TIMEOUT_MULTIPLIER]
            );
            $response = $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheListFetchResponseError($e);
        } catch (Exception $e) {
            return new CacheListFetchResponseError(new UnknownError($e->getMessage()));
        }
        if (!$response->hasFound()) {
            return new CacheListFetchResponseMiss();
        }
        return new CacheListFetchResponseHit($response);
    }

    public function listPushFront(
        string $cacheName, string $listName, string $value, bool $refreshTtl, ?int $truncateBackToSize = null, ?int $ttlSeconds = null
    ): CacheListPushFrontResponse
    {
        try {
            validateCacheName($cacheName);
            validateListName($listName);
            validateTruncateSize($truncateBackToSize);
            $ttlMillis = $this->ttlToMillis($ttlSeconds);
            $listPushFrontRequest = new _ListPushFrontRequest();
            $listPushFrontRequest->setListName($listName);
            $listPushFrontRequest->setValue($value);
            $listPushFrontRequest->setRefreshTtl($refreshTtl);
            $listPushFrontRequest->setTtlMilliseconds($ttlMillis);
            if (!is_null($truncateBackToSize)) {
                $listPushFrontRequest->setTruncateTailToSize($truncateBackToSize);
            }
            $call = $this->grpcManager->client->ListPushFront(
                $listPushFrontRequest, ["cache" => [$cacheName]], ["timeout" => $this->deadline_seconds * self::$TIMEOUT_MULTIPLIER]
            );
            $response = $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheListPushFrontResponseError($e);
        } catch (Exception $e) {
            return new CacheListPushFrontResponseError(new UnknownError($e->getMessage()));
        }
        return new CacheListPushFrontResponseSuccess($response);
    }

    public function listPushBack(
        string $cacheName, string $listName, string $value, bool $refreshTtl, ?int $truncateFrontToSize = null, ?int $ttlSeconds = null
    ): CacheListPushBackResponse
    {
        try {
            validateCacheName($cacheName);
            validateListName($listName);
            validateTruncateSize($truncateFrontToSize);
            $ttlMillis = $this->ttlToMillis($ttlSeconds);
            $listPushBackRequest = new _ListPushBackRequest();
            $listPushBackRequest->setListName($listName);
            $listPushBackRequest->setValue($value);
            $listPushBackRequest->setRefreshTtl($refreshTtl);
            $listPushBackRequest->setTtlMilliseconds($ttlMillis);
            if (!is_null($truncateFrontToSize)) {
                $listPushBackRequest->setTruncateHeadToSize($truncateFrontToSize);
            }
            $call = $this->grpcManager->client->ListPushBack(
                $listPushBackRequest, ["cache" => [$cacheName]], ["timeout" => $this->deadline_seconds * self::$TIMEOUT_MULTIPLIER]
            );
            $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheListPushBackResponseError($e);
        } catch (Exception $e) {
            return new CacheListPushBackResponseError(new UnknownError($e->getMessage()));
        }
        return new CacheListPushBackResponseSuccess();
    }

    public function listPopFront(string $cacheName, string $listName): CacheListPopFrontResponse
    {
        try {
            validateCacheName($cacheName);
            validateListName($listName);
            $listPopFrontRequest = new _ListPopFrontRequest();
            $listPopFrontRequest->setListName($listName);
            $call = $this->grpcManager->client->ListPopFront(
                $listPopFrontRequest, ["cache" => [$cacheName]], ["timeout" => $this->deadline_seconds * self::$TIMEOUT_MULTIPLIER]
            );
            $response = $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheListPopFrontResponseError($e);
        } catch (Exception $e) {
            return new CacheListPopFrontResponseError(new UnknownError($e->getMessage()));
        }
        if (!$response->hasFound()) {
            return new CacheListPopFrontResponseMiss();
        }
        return new CacheListPopFrontResponseHit($response);
    }

    public function listPopBack(string $cacheName, string $listName): CacheListPopBackResponse
    {
        try {
            validateCacheName($cacheName);
            validateListName($listName);
            $listPopBackRequest = new _ListPopBackRequest();
            $listPopBackRequest->setListName($listName);
            $call = $this->grpcManager->client->ListPopBack(
                $listPopBackRequest, ["cache" => [$cacheName]], ["timeout" => $this->deadline_seconds * self::$TIMEOUT_MULTIPLIER]
            );
            $response = $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheListPopBackResponseError($e);
        } catch (Exception $e) {
            return new CacheListPopBackResponseError(new UnknownError($e->getMessage()));
        }
        if (!$response->hasFound()) {
            return new CacheListPopBackResponseMiss();
        }
        return new CacheListPopBackResponseHit($response);
    }

    public function listRemoveValue(string $cacheName, string $listName, string $value): CacheListRemoveValueResponse
    {
        try {
            validateCacheName($cacheName);
            validateListName($listName);
            $listRemoveValueRequest = new _ListRemoveRequest();
            $listRemoveValueRequest->setListName($listName);
            $listRemoveValueRequest->setAllElementsWithValue($value);
            $call = $this->grpcManager->client->ListRemove(
                $listRemoveValueRequest, ["cache" => [$cacheName]], ["timeout" => $this->deadline_seconds * self::$TIMEOUT_MULTIPLIER]
            );
            $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheListRemoveValueResponseError($e);
        } catch (Exception $e) {
            return new CacheListRemoveValueResponseError(new UnknownError($e->getMessage()));
        }
        return new CacheListRemoveValueResponseSuccess();
    }

    public function listLength(string $cacheName, string $listName): CacheListLengthResponse
    {
        try {
            validateCacheName($cacheName);
            validateListName($listName);
            $listLengthRequest = new _ListLengthRequest();
            $listLengthRequest->setListName($listName);
            $call = $this->grpcManager->client->ListLength(
                $listLengthRequest, ["cache" => [$cacheName]], ["timeout" => $this->deadline_seconds * self::$TIMEOUT_MULTIPLIER]
            );
            $response = $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheListLengthResponseError($e);
        } catch (Exception $e) {
            return new CacheListLengthResponseError(new UnknownError($e->getMessage()));
        }
        return new CacheListLengthResponseSuccess($response);
    }

    public function listErase(string $cacheName, string $listName, ?int $beginIndex = null, ?int $count = null): CacheListEraseResponse
    {
        try {
            validateCacheName($cacheName);
            validateListName($listName);
            validateRange($beginIndex, $count);
            $listEraseRequest = new _ListEraseRequest();
            $listEraseRequest->setListName($listName);
            if (!is_null($beginIndex) && !is_null($count)) {
                $listRanges = new _ListEraseRequest\_ListRanges();
                $listRange = new _ListRange();
                $listRange->setBeginIndex($beginIndex);
                $listRange->setCount($count);
                $listRanges->setRanges([$listRange]);
                $listEraseRequest->setSome($listRanges);
            } else {
                $all = new _ListEraseRequest\_All();
                $listEraseRequest->setAll($all);
            }
            $call = $this->grpcManager->client->ListErase(
                $listEraseRequest, ["cache" => [$cacheName]], ["timeout" => $this->deadline_seconds * self::$TIMEOUT_MULTIPLIER]
            );
            $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheListEraseResponseError($e);
        } catch (Exception $e) {
            return new CacheListEraseResponseError(new UnknownError($e->getMessage()));
        }
        return new CacheListEraseResponseSuccess();
    }

    public function dictionarySet(string $cacheName, string $dictionaryName, string $field, string $value, bool $refreshTtl, ?int $ttlSeconds = null): CacheDictionarySetResponse
    {
        try {
            validateCacheName($cacheName);
            validateDictionaryName($dictionaryName);
            validateFieldName($field);
            validateValueName($value);
            $ttlMillis = $this->ttlToMillis($ttlSeconds);
            $dictionarySetRequest = new _DictionarySetRequest();
            $dictionarySetRequest->setDictionaryName($dictionaryName);
            $dictionarySetRequest->setItems([$this->toSingletonFieldValuePair($field, $value)]);
            $dictionarySetRequest->setRefreshTtl($refreshTtl);
            $dictionarySetRequest->setTtlMilliseconds($ttlMillis);
            $call = $this->grpcManager->client->DictionarySet($dictionarySetRequest, ["cache" => [$cacheName]], ["timeout" => $this->deadline_seconds * self::$TIMEOUT_MULTIPLIER]);
            $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheDictionarySetResponseError($e);
        } catch (Exception $e) {
            return new CacheDictionarySetResponseError(new UnknownError($e->getMessage()));
        }
        return new CacheDictionarySetResponseSuccess();
    }

    private function toSingletonFieldValuePair(string $field, string $value): _DictionaryFieldValuePair
    {
        $singletonPair = new _DictionaryFieldValuePair();
        $singletonPair->setField($field);
        $singletonPair->setValue($value);
        return $singletonPair;
    }

    public function dictionaryGet(string $cacheName, string $dictionaryName, string $field): CacheDictionaryGetResponse
    {
        try {
            validateCacheName($cacheName);
            validateDictionaryName($dictionaryName);
            validateFieldName($field);
            $dictionaryGetRequest = new _DictionaryGetRequest();
            $dictionaryGetRequest->setDictionaryName($dictionaryName);
            $dictionaryGetRequest->setFields([$field]);
            $call = $this->grpcManager->client->DictionaryGet($dictionaryGetRequest, ["cache" => [$cacheName]], ["timeout" => $this->deadline_seconds * self::$TIMEOUT_MULTIPLIER]);
            $dictionaryGetResponse = $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheDictionaryGetResponseError($e);
        } catch (Exception $e) {
            return new CacheDictionaryGetResponseError(new UnknownError($e->getMessage()));
        }
        if ($dictionaryGetResponse->hasMissing()) {
            return new CacheDictionaryGetResponseMiss();
        }
        if ($dictionaryGetResponse->getFound()->getItems()->count() == 0) {
            return new CacheDictionaryGetResponseError(new UnknownError("_DictionaryGetResponseResponse contained no data but was found"));
        }
        if ($dictionaryGetResponse->getFound()->getItems()[0]->getResult() == ECacheResult::Miss) {
            return new CacheDictionaryGetResponseMiss();
        }
        return new CacheDictionaryGetResponseHit($dictionaryGetResponse);
    }

    public function dictionaryDelete(string $cacheName, string $dictionaryName): CacheDictionaryDeleteResponse
    {
        try {
            validateCacheName($cacheName);
            validateDictionaryName($dictionaryName);
            $dictionaryDeleteRequest = new _DictionaryDeleteRequest();
            $dictionaryDeleteRequest->setDictionaryName($dictionaryName);
            $all = new All();
            $dictionaryDeleteRequest->setAll($all);
            $call = $this->grpcManager->client->DictionaryDelete($dictionaryDeleteRequest, ["cache" => [$cacheName]], ["timeout" => $this->deadline_seconds * self::$TIMEOUT_MULTIPLIER]);
            $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheDictionaryDeleteResponseError($e);
        } catch (Exception $e) {
            return new CacheDictionaryDeleteResponseError(new UnknownError($e->getMessage()));
        }
        return new CacheDictionaryDeleteResponseSuccess();
    }
}
