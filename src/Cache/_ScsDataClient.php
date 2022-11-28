<?php
declare(strict_types=1);

namespace Momento\Cache;

use Cache_client\_DeleteRequest;
use Cache_client\_DictionaryDeleteRequest;
use Cache_client\_DictionaryDeleteRequest\All;
use Cache_client\_DictionaryFetchRequest;
use Cache_client\_DictionaryFieldValuePair;
use Cache_client\_DictionaryGetRequest;
use Cache_client\_DictionaryIncrementRequest;
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
use Cache_client\_SetDifferenceRequest;
use Cache_client\_SetDifferenceRequest\_Subtrahend;
use Cache_client\_SetDifferenceRequest\_Subtrahend\_Identity;
use Cache_client\_SetDifferenceRequest\_Subtrahend\_Set;
use Cache_client\_SetFetchRequest;
use Cache_client\_SetRequest;
use Cache_client\_SetUnionRequest;
use Cache_client\ECacheResult;
use Exception;
use Grpc\UnaryCall;
use Momento\Auth\ICredentialProvider;
use Momento\Cache\CacheOperationTypes\CacheDeleteResponse;
use Momento\Cache\CacheOperationTypes\CacheDeleteResponseError;
use Momento\Cache\CacheOperationTypes\CacheDeleteResponseSuccess;
use Momento\Cache\CacheOperationTypes\CacheDictionaryDeleteResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryDeleteResponseError;
use Momento\Cache\CacheOperationTypes\CacheDictionaryDeleteResponseSuccess;
use Momento\Cache\CacheOperationTypes\CacheDictionaryFetchResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryFetchResponseError;
use Momento\Cache\CacheOperationTypes\CacheDictionaryFetchResponseHit;
use Momento\Cache\CacheOperationTypes\CacheDictionaryFetchResponseMiss;
use Momento\Cache\CacheOperationTypes\CacheDictionaryGetFieldResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryGetFieldResponseError;
use Momento\Cache\CacheOperationTypes\CacheDictionaryGetFieldResponseHit;
use Momento\Cache\CacheOperationTypes\CacheDictionaryGetFieldResponseMiss;
use Momento\Cache\CacheOperationTypes\CacheDictionaryGetFieldsResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryGetFieldsResponseError;
use Momento\Cache\CacheOperationTypes\CacheDictionaryGetFieldsResponseHit;
use Momento\Cache\CacheOperationTypes\CacheDictionaryGetFieldsResponseMiss;
use Momento\Cache\CacheOperationTypes\CacheDictionaryIncrementResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryIncrementResponseError;
use Momento\Cache\CacheOperationTypes\CacheDictionaryIncrementResponseSuccess;
use Momento\Cache\CacheOperationTypes\CacheDictionaryRemoveFieldResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryRemoveFieldResponseError;
use Momento\Cache\CacheOperationTypes\CacheDictionaryRemoveFieldResponseSuccess;
use Momento\Cache\CacheOperationTypes\CacheDictionaryRemoveFieldsResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryRemoveFieldsResponseError;
use Momento\Cache\CacheOperationTypes\CacheDictionaryRemoveFieldsResponseSuccess;
use Momento\Cache\CacheOperationTypes\CacheDictionarySetFieldResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionarySetFieldResponseError;
use Momento\Cache\CacheOperationTypes\CacheDictionarySetFieldResponseSuccess;
use Momento\Cache\CacheOperationTypes\CacheDictionarySetFieldsResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionarySetFieldsResponseError;
use Momento\Cache\CacheOperationTypes\CacheDictionarySetFieldsResponseSuccess;
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
use Momento\Cache\CacheOperationTypes\CacheSetAddElementResponse;
use Momento\Cache\CacheOperationTypes\CacheSetAddElementResponseError;
use Momento\Cache\CacheOperationTypes\CacheSetAddElementResponseSuccess;
use Momento\Cache\CacheOperationTypes\CacheSetDeleteResponse;
use Momento\Cache\CacheOperationTypes\CacheSetDeleteResponseError;
use Momento\Cache\CacheOperationTypes\CacheSetDeleteResponseSuccess;
use Momento\Cache\CacheOperationTypes\CacheSetFetchResponse;
use Momento\Cache\CacheOperationTypes\CacheSetFetchResponseError;
use Momento\Cache\CacheOperationTypes\CacheSetFetchResponseHit;
use Momento\Cache\CacheOperationTypes\CacheSetFetchResponseMiss;
use Momento\Cache\CacheOperationTypes\CacheSetRemoveElementResponse;
use Momento\Cache\CacheOperationTypes\CacheSetRemoveElementResponseError;
use Momento\Cache\CacheOperationTypes\CacheSetRemoveElementResponseSuccess;
use Momento\Cache\CacheOperationTypes\CacheSetResponse;
use Momento\Cache\CacheOperationTypes\CacheSetResponseError;
use Momento\Cache\CacheOperationTypes\CacheSetResponseSuccess;
use Momento\Cache\Errors\InternalServerError;
use Momento\Cache\Errors\SdkError;
use Momento\Cache\Errors\UnknownError;
use Momento\Config\IConfiguration;
use Momento\Requests\CollectionTtl;
use Momento\Requests\CollectionTtlFactory;
use Momento\Utilities\_ErrorConverter;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use function Momento\Utilities\validateCacheName;
use function Momento\Utilities\validateDictionaryName;
use function Momento\Utilities\validateElement;
use function Momento\Utilities\validateFieldName;
use function Momento\Utilities\validateFields;
use function Momento\Utilities\validateFieldsKeys;
use function Momento\Utilities\validateItems;
use function Momento\Utilities\validateListName;
use function Momento\Utilities\validateOperationTimeout;
use function Momento\Utilities\validateRange;
use function Momento\Utilities\validateSetName;
use function Momento\Utilities\validateTruncateSize;
use function Momento\Utilities\validateTtl;
use function Momento\Utilities\validateValueName;

class _ScsDataClient implements LoggerAwareInterface
{

    private static int $DEFAULT_DEADLINE_MILLISECONDS = 5000;
    private int $deadline_milliseconds;
    // Used to convert deadline_milliseconds into microseconds for gRPC
    private static int $TIMEOUT_MULTIPLIER = 1000;
    private int $defaultTtlSeconds;
    private _DataGrpcManager $grpcManager;
    private LoggerInterface $logger;
    private int $timeout;

    public function __construct(IConfiguration $configuration, ICredentialProvider $authProvider, int $defaultTtlSeconds)
    {
        validateTtl($defaultTtlSeconds);
        $this->defaultTtlSeconds = $defaultTtlSeconds;
        $operationTimeoutMs = $configuration
            ->getTransportStrategy()
            ->getGrpcConfig()
            ->getDeadlineMilliseconds();
        validateOperationTimeout($operationTimeoutMs);
        $this->deadline_milliseconds = $operationTimeoutMs ?? self::$DEFAULT_DEADLINE_MILLISECONDS;
        $this->timeout = $this->deadline_milliseconds * self::$TIMEOUT_MULTIPLIER;
        $this->grpcManager = new _DataGrpcManager($authProvider);
        $this->setLogger($configuration->getLoggerFactory()->getLogger(get_class($this)));
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    private function ttlToMillis(?int $ttl = null): int
    {
        if (!$ttl) {
            $ttl = $this->defaultTtlSeconds;
        }
        validateTtl($ttl);
        return $ttl * 1000;
    }

    private function returnCollectionTtl($ttl): CollectionTtl
    {
        if (!$ttl) {
            return CollectionTtl::fromCacheTtl();
        }
        return $ttl;
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
                $setRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]
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
                $getRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]
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
                $deleteRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]
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
                $listFetchRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]
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
        string $cacheName, string $listName, string $value, ?int $truncateBackToSize = null, CollectionTtl $ttl = null
    ): CacheListPushFrontResponse
    {
        try {
            $collectionTtl = $this->returnCollectionTtl($ttl);
            validateCacheName($cacheName);
            validateListName($listName);
            validateTruncateSize($truncateBackToSize);
            $ttlMillis = $this->ttlToMillis($collectionTtl->getTtl());
            $listPushFrontRequest = new _ListPushFrontRequest();
            $listPushFrontRequest->setListName($listName);
            $listPushFrontRequest->setValue($value);
            $listPushFrontRequest->setRefreshTtl($collectionTtl->getRefreshTtl());
            $listPushFrontRequest->setTtlMilliseconds($ttlMillis);
            if (!is_null($truncateBackToSize)) {
                $listPushFrontRequest->setTruncateBackToSize($truncateBackToSize);
            }
            $call = $this->grpcManager->client->ListPushFront(
                $listPushFrontRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]
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
        string $cacheName, string $listName, string $value, ?int $truncateFrontToSize = null, CollectionTtl $ttl = null
    ): CacheListPushBackResponse
    {
        try {
            $collectionTtl = $this->returnCollectionTtl($ttl);
            validateCacheName($cacheName);
            validateListName($listName);
            validateTruncateSize($truncateFrontToSize);
            $ttlMillis = $this->ttlToMillis($collectionTtl->getTtl());
            $listPushBackRequest = new _ListPushBackRequest();
            $listPushBackRequest->setListName($listName);
            $listPushBackRequest->setValue($value);
            $listPushBackRequest->setRefreshTtl($collectionTtl->getRefreshTtl());
            $listPushBackRequest->setTtlMilliseconds($ttlMillis);
            if (!is_null($truncateFrontToSize)) {
                $listPushBackRequest->setTruncateFrontToSize($truncateFrontToSize);
            }
            $call = $this->grpcManager->client->ListPushBack(
                $listPushBackRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]
            );
            $response = $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheListPushBackResponseError($e);
        } catch (Exception $e) {
            return new CacheListPushBackResponseError(new UnknownError($e->getMessage()));
        }
        return new CacheListPushBackResponseSuccess($response);
    }

    public function listPopFront(string $cacheName, string $listName): CacheListPopFrontResponse
    {
        try {
            validateCacheName($cacheName);
            validateListName($listName);
            $listPopFrontRequest = new _ListPopFrontRequest();
            $listPopFrontRequest->setListName($listName);
            $call = $this->grpcManager->client->ListPopFront(
                $listPopFrontRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]
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
                $listPopBackRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]
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
                $listRemoveValueRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]
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
                $listLengthRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]
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
                $listEraseRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]
            );
            $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheListEraseResponseError($e);
        } catch (Exception $e) {
            return new CacheListEraseResponseError(new UnknownError($e->getMessage()));
        }
        return new CacheListEraseResponseSuccess();
    }

    public function dictionarySetField(string $cacheName, string $dictionaryName, string $field, string $value, CollectionTtl $ttl = null): CacheDictionarySetFieldResponse
    {
        try {
            $collectionTtl = $this->returnCollectionTtl($ttl);
            validateCacheName($cacheName);
            validateDictionaryName($dictionaryName);
            validateFieldName($field);
            validateValueName($value);
            $ttlMillis = $this->ttlToMillis($collectionTtl->getTtl());
            validateTtl($ttlMillis);
            $dictionarySetFieldRequest = new _DictionarySetRequest();
            $dictionarySetFieldRequest->setDictionaryName($dictionaryName);
            $dictionarySetFieldRequest->setItems([$this->toSingletonFieldValuePair($field, $value)]);
            $dictionarySetFieldRequest->setRefreshTtl($collectionTtl->getRefreshTtl());
            $dictionarySetFieldRequest->setTtlMilliseconds($ttlMillis);
            $call = $this->grpcManager->client->DictionarySet($dictionarySetFieldRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]);
            $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheDictionarySetFieldResponseError($e);
        } catch (Exception $e) {
            return new CacheDictionarySetFieldResponseError(new UnknownError($e->getMessage()));
        }
        return new CacheDictionarySetFieldResponseSuccess();
    }

    private function toSingletonFieldValuePair(string $field, string $value): _DictionaryFieldValuePair
    {
        $singletonPair = new _DictionaryFieldValuePair();
        $singletonPair->setField($field);
        $singletonPair->setValue($value);
        return $singletonPair;
    }

    public function dictionaryGetField(string $cacheName, string $dictionaryName, string $field): CacheDictionaryGetFieldResponse
    {
        try {
            validateCacheName($cacheName);
            validateDictionaryName($dictionaryName);
            validateFieldName($field);
            $dictionaryGetFieldRequest = new _DictionaryGetRequest();
            $dictionaryGetFieldRequest->setDictionaryName($dictionaryName);
            $dictionaryGetFieldRequest->setFields([$field]);
            $call = $this->grpcManager->client->DictionaryGet($dictionaryGetFieldRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]);
            $dictionaryGetFieldResponse = $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheDictionaryGetFieldResponseError($e);
        } catch (Exception $e) {
            return new CacheDictionaryGetFieldResponseError(new UnknownError($e->getMessage()));
        }
        if ($dictionaryGetFieldResponse->hasMissing()) {
            return new CacheDictionaryGetFieldResponseMiss();
        }
        if ($dictionaryGetFieldResponse->getFound()->getItems()->count() == 0) {
            return new CacheDictionaryGetFieldResponseError(new UnknownError("_DictionaryGetResponseResponse contained no data but was found"));
        }
        if ($dictionaryGetFieldResponse->getFound()->getItems()[0]->getResult() == ECacheResult::Miss) {
            return new CacheDictionaryGetFieldResponseMiss();
        }
        return new CacheDictionaryGetFieldResponseHit($dictionaryGetFieldResponse);
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
            $call = $this->grpcManager->client->DictionaryDelete($dictionaryDeleteRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]);
            $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheDictionaryDeleteResponseError($e);
        } catch (Exception $e) {
            return new CacheDictionaryDeleteResponseError(new UnknownError($e->getMessage()));
        }
        return new CacheDictionaryDeleteResponseSuccess();
    }

    public function dictionaryFetch(string $cacheName, string $dictionaryName): CacheDictionaryFetchResponse
    {
        try {
            validateCacheName($cacheName);
            validateDictionaryName($dictionaryName);
            $dictionaryFetchRequest = new _DictionaryFetchRequest();
            $dictionaryFetchRequest->setDictionaryName($dictionaryName);
            $call = $this->grpcManager->client->DictionaryFetch($dictionaryFetchRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]);
            $dictionaryFetchResponse = $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheDictionaryFetchResponseError($e);
        } catch (Exception $e) {
            return new CacheDictionaryFetchResponseError(new UnknownError($e->getMessage()));
        }
        if ($dictionaryFetchResponse->hasFound()) {
            return new CacheDictionaryFetchResponseHit($dictionaryFetchResponse);
        }
        return new CacheDictionaryFetchResponseMiss();
    }

    public function dictionarySetFields(string $cacheName, string $dictionaryName, array $items, CollectionTtl $ttl = null): CacheDictionarySetFieldsResponse
    {
        try {
            $collectionTtl = $this->returnCollectionTtl($ttl);
            validateCacheName($cacheName);
            validateDictionaryName($dictionaryName);
            validateItems($items);
            validateFieldsKeys($items);
            $ttlMillis = $this->ttlToMillis($collectionTtl->getTtl());
            $protoItems = [];
            foreach ($items as $field => $value) {
                $fieldValuePair = new _DictionaryFieldValuePair();
                $fieldValuePair->setField($field);
                $fieldValuePair->setValue($value);
                $protoItems[] = $fieldValuePair;
            }
            $dictionarySetFieldsRequest = new _DictionarySetRequest();
            $dictionarySetFieldsRequest->setDictionaryName($dictionaryName);
            $dictionarySetFieldsRequest->setRefreshTtl($collectionTtl->getRefreshTtl());
            $dictionarySetFieldsRequest->setItems($protoItems);
            $dictionarySetFieldsRequest->setTtlMilliseconds($ttlMillis);
            $call = $this->grpcManager->client->DictionarySet($dictionarySetFieldsRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]);
            $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheDictionarySetFieldsResponseError($e);
        } catch (Exception $e) {
            return new CacheDictionarySetFieldsResponseError(new UnknownError($e->getMessage()));
        }
        return new CacheDictionarySetFieldsResponseSuccess();
    }

    public function dictionaryGetFields(string $cacheName, string $dictionaryName, array $fields): CacheDictionaryGetFieldsResponse
    {
        try {
            validateCacheName($cacheName);
            validateDictionaryName($dictionaryName);
            validateItems($fields);
            $dictionaryGetFieldsRequest = new _DictionaryGetRequest();
            $dictionaryGetFieldsRequest->setDictionaryName($dictionaryName);
            $dictionaryGetFieldsRequest->setFields($fields);
            $call = $this->grpcManager->client->DictionaryGet($dictionaryGetFieldsRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]);
            $dictionaryGetFieldsResponse = $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheDictionaryGetFieldsResponseError($e);
        } catch (Exception $e) {
            return new CacheDictionaryGetFieldsResponseError(new UnknownError($e->getMessage()));
        }
        if ($dictionaryGetFieldsResponse->hasFound()) {
            return new CacheDictionaryGetFieldsResponseHit($dictionaryGetFieldsResponse, fields: $fields);
        }
        return new CacheDictionaryGetFieldsResponseMiss();

    }

    public function dictionaryIncrement(
        string $cacheName, string $dictionaryName, string $field, int $amount = 1, CollectionTtl $ttl = null
    ): CacheDictionaryIncrementResponse
    {
        try {
            $collectionTtl = $this->returnCollectionTtl($ttl);
            validateCacheName($cacheName);
            validateDictionaryName($dictionaryName);
            validateFieldName($field);
            $ttlMillis = $this->ttlToMillis($collectionTtl->getTtl());
            validateTtl($ttlMillis);
            $dictionaryIncrementRequest = new _DictionaryIncrementRequest();
            $dictionaryIncrementRequest
                ->setDictionaryName($dictionaryName)
                ->setField($field)
                ->setAmount($amount)
                ->setRefreshTtl($collectionTtl->getRefreshTtl())
                ->setTtlMilliseconds($ttlMillis);
            $call = $this->grpcManager->client->DictionaryIncrement(
                $dictionaryIncrementRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]
            );
            $response = $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheDictionaryIncrementResponseError($e);
        } catch (Exception $e) {
            return new CacheDictionaryIncrementResponseError(new UnknownError($e->getMessage()));
        }
        return new CacheDictionaryIncrementResponseSuccess($response);
    }

    public function dictionaryRemoveField(string $cacheName, string $dictionaryName, string $field): CacheDictionaryRemoveFieldResponse
    {
        try {
            validateCacheName($cacheName);
            validateDictionaryName($dictionaryName);
            validateFieldName($field);
            $dictionaryRemoveFieldRequest = new _DictionaryDeleteRequest();
            $some = new _DictionaryDeleteRequest\Some();
            $some->setFields([$field]);
            $dictionaryRemoveFieldRequest->setDictionaryName($dictionaryName);
            $dictionaryRemoveFieldRequest->setSome($some);
            $call = $this->grpcManager->client->DictionaryDelete(
                $dictionaryRemoveFieldRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]
            );
            $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheDictionaryRemoveFieldResponseError($e);
        } catch (Exception $e) {
            return new CacheDictionaryRemoveFieldResponseError(new UnknownError($e->getMessage()));
        }
        return new CacheDictionaryRemoveFieldResponseSuccess();
    }

    public function dictionaryRemoveFields(string $cacheName, string $dictionaryName, array $fields): CacheDictionaryRemoveFieldsResponse
    {
        try {
            validateCacheName($cacheName);
            validateDictionaryName($dictionaryName);
            validateFields($fields);
            $dictionaryRemoveFieldsRequest = new _DictionaryDeleteRequest();
            $some = new _DictionaryDeleteRequest\Some();
            $some->setFields($fields);
            $dictionaryRemoveFieldsRequest->setDictionaryName($dictionaryName);
            $dictionaryRemoveFieldsRequest->setSome($some);
            $call = $this->grpcManager->client->DictionaryDelete(
                $dictionaryRemoveFieldsRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]
            );
            $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheDictionaryRemoveFieldsResponseError($e);
        } catch (Exception $e) {
            return new CacheDictionaryRemoveFieldsResponseError(new UnknownError($e->getMessage()));
        }
        return new CacheDictionaryRemoveFieldsResponseSuccess();
    }

    public function setAddElement(string $cacheName, string $setName, string $element, ?bool $refreshTt = true, ?int $ttlSeconds = null): CacheSetAddElementResponse
    {
        try {
            validateCacheName($cacheName);
            validateSetName($setName);
            validateElement($element);
            $ttlMillis = $this->ttlToMillis($ttlSeconds);
            validateTtl($ttlMillis);
            $setAddElementRequest = new _SetUnionRequest();
            $setAddElementRequest->setSetName($setName);
            $setAddElementRequest->setRefreshTtl($refreshTt);
            $setAddElementRequest->setTtlMilliseconds($ttlMillis);
            $setAddElementRequest->setElements([$element]);
            $call = $this->grpcManager->client->SetUnion($setAddElementRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]);
            $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheSetAddElementResponseError($e);
        } catch (Exception $e) {
            return new CacheSetAddElementResponseError(new UnknownError($e->getMessage()));
        }
        return new CacheSetAddElementResponseSuccess();
    }

    public function setFetch(string $cacheName, string $setName): CacheSetFetchResponse
    {
        try {
            validateCacheName($cacheName);
            validateSetName($setName);
            $setFetchRequest = new _SetFetchRequest();
            $setFetchRequest->setSetName($setName);
            $call = $this->grpcManager->client->SetFetch($setFetchRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]);
            $setFetchResponse = $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheSetFetchResponseError($e);
        } catch (Exception $e) {
            return new CacheSetFetchResponseError(new UnknownError($e->getMessage()));
        }
        if ($setFetchResponse->hasFound()) {
            return new CacheSetFetchResponseHit($setFetchResponse);
        }
        return new CacheSetFetchResponseMiss();
    }

    public function setRemoveElement(string $cacheName, string $setName, string $element): CacheSetRemoveElementResponse
    {
        try {
            validateCacheName($cacheName);
            validateSetName($setName);
            validateElement($element);
            $setRemoveElementRequest = new _SetDifferenceRequest();
            $setRemoveElementRequest->setSetName($setName);
            $subtrahend = new _Subtrahend();
            $set = new _Set();
            $set->setElements([$element]);
            $subtrahend->setSet($set);
            $setRemoveElementRequest->setSubtrahend($subtrahend);
            $call = $this->grpcManager->client->SetDifference($setRemoveElementRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]);
            $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheSetRemoveElementResponseError($e);
        } catch (Exception $e) {
            return new CacheSetRemoveElementResponseError(new UnknownError($e->getMessage()));
        }
        return new CacheSetRemoveElementResponseSuccess();
    }

    public function setDelete(string $cacheName, string $setName): CacheSetDeleteResponse
    {
        try {
            validateCacheName($cacheName);
            validateSetName($setName);
            $setDeleteRequest = new _SetDifferenceRequest();
            $setDeleteRequest->setSetName($setName);
            $subtrahend = new _Subtrahend();
            $subtrahend->setIdentity(new _Identity());
            $setDeleteRequest->setSubtrahend($subtrahend);
            $call = $this->grpcManager->client->SetDifference($setDeleteRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]);
            $this->processCall($call);
        } catch (SdkError $e) {
            return new CacheSetDeleteResponseError($e);
        } catch (Exception $e) {
            return new CacheSetDeleteResponseError(new UnknownError($e->getMessage()));
        }
        return new CacheSetDeleteResponseSuccess();
    }
}
