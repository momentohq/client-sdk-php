<?php
declare(strict_types=1);

namespace Momento\Cache\Internal;

use Cache_client\_DeleteRequest;
use Cache_client\_DictionaryDeleteRequest;
use Cache_client\_DictionaryFetchRequest;
use Cache_client\_DictionaryFieldValuePair;
use Cache_client\_DictionaryGetRequest;
use Cache_client\_DictionaryIncrementRequest;
use Cache_client\_DictionarySetRequest;
use Cache_client\_GetRequest;
use Cache_client\_IncrementRequest;
use Cache_client\_KeysExistRequest;
use Cache_client\_ListFetchRequest;
use Cache_client\_ListLengthRequest;
use Cache_client\_ListPopBackRequest;
use Cache_client\_ListPopFrontRequest;
use Cache_client\_ListPushBackRequest;
use Cache_client\_ListPushFrontRequest;
use Cache_client\_ListRemoveRequest;
use Cache_client\_SetDifferenceRequest;
use Cache_client\_SetDifferenceRequest\_Subtrahend;
use Cache_client\_SetDifferenceRequest\_Subtrahend\_Set;
use Cache_client\_SetFetchRequest;
use Cache_client\_SetIfNotExistsRequest;
use Cache_client\_SetLengthRequest;
use Cache_client\_SetRequest;
use Cache_client\_SetUnionRequest;
use Cache_client\ECacheResult;
use Exception;
use Grpc\UnaryCall;
use Momento\Auth\ICredentialProvider;
use Momento\Cache\CacheOperationTypes\DeleteResponse;
use Momento\Cache\CacheOperationTypes\DeleteError;
use Momento\Cache\CacheOperationTypes\DeleteSuccess;
use Momento\Cache\CacheOperationTypes\DictionaryFetchResponse;
use Momento\Cache\CacheOperationTypes\DictionaryFetchError;
use Momento\Cache\CacheOperationTypes\DictionaryFetchHit;
use Momento\Cache\CacheOperationTypes\DictionaryFetchMiss;
use Momento\Cache\CacheOperationTypes\DictionaryGetFieldResponse;
use Momento\Cache\CacheOperationTypes\DictionaryGetFieldError;
use Momento\Cache\CacheOperationTypes\DictionaryGetFieldHit;
use Momento\Cache\CacheOperationTypes\DictionaryGetFieldMiss;
use Momento\Cache\CacheOperationTypes\DictionaryGetFieldsResponse;
use Momento\Cache\CacheOperationTypes\DictionaryGetFieldsError;
use Momento\Cache\CacheOperationTypes\DictionaryGetFieldsHit;
use Momento\Cache\CacheOperationTypes\DictionaryGetFieldsMiss;
use Momento\Cache\CacheOperationTypes\DictionaryIncrementResponse;
use Momento\Cache\CacheOperationTypes\DictionaryIncrementError;
use Momento\Cache\CacheOperationTypes\DictionaryIncrementSuccess;
use Momento\Cache\CacheOperationTypes\DictionaryRemoveFieldResponse;
use Momento\Cache\CacheOperationTypes\DictionaryRemoveFieldError;
use Momento\Cache\CacheOperationTypes\DictionaryRemoveFieldSuccess;
use Momento\Cache\CacheOperationTypes\DictionaryRemoveFieldsResponse;
use Momento\Cache\CacheOperationTypes\DictionaryRemoveFieldsError;
use Momento\Cache\CacheOperationTypes\DictionaryRemoveFieldsSuccess;
use Momento\Cache\CacheOperationTypes\DictionarySetFieldResponse;
use Momento\Cache\CacheOperationTypes\DictionarySetFieldError;
use Momento\Cache\CacheOperationTypes\DictionarySetFieldSuccess;
use Momento\Cache\CacheOperationTypes\DictionarySetFieldsResponse;
use Momento\Cache\CacheOperationTypes\DictionarySetFieldsError;
use Momento\Cache\CacheOperationTypes\DictionarySetFieldsSuccess;
use Momento\Cache\CacheOperationTypes\GetResponse;
use Momento\Cache\CacheOperationTypes\GetError;
use Momento\Cache\CacheOperationTypes\GetHit;
use Momento\Cache\CacheOperationTypes\GetMiss;
use Momento\Cache\CacheOperationTypes\IncrementError;
use Momento\Cache\CacheOperationTypes\IncrementResponse;
use Momento\Cache\CacheOperationTypes\IncrementSuccess;
use Momento\Cache\CacheOperationTypes\KeyExistsResponse;
use Momento\Cache\CacheOperationTypes\KeyExistsError;
use Momento\Cache\CacheOperationTypes\KeyExistsSuccess;
use Momento\Cache\CacheOperationTypes\KeysExistResponse;
use Momento\Cache\CacheOperationTypes\KeysExistError;
use Momento\Cache\CacheOperationTypes\KeysExistSuccess;
use Momento\Cache\CacheOperationTypes\ListFetchResponse;
use Momento\Cache\CacheOperationTypes\ListFetchError;
use Momento\Cache\CacheOperationTypes\ListFetchHit;
use Momento\Cache\CacheOperationTypes\ListFetchMiss;
use Momento\Cache\CacheOperationTypes\ListLengthResponse;
use Momento\Cache\CacheOperationTypes\ListLengthError;
use Momento\Cache\CacheOperationTypes\ListLengthSuccess;
use Momento\Cache\CacheOperationTypes\ListPopBackResponse;
use Momento\Cache\CacheOperationTypes\ListPopBackError;
use Momento\Cache\CacheOperationTypes\ListPopBackHit;
use Momento\Cache\CacheOperationTypes\ListPopBackMiss;
use Momento\Cache\CacheOperationTypes\ListPopFrontResponse;
use Momento\Cache\CacheOperationTypes\ListPopFrontError;
use Momento\Cache\CacheOperationTypes\ListPopFrontHit;
use Momento\Cache\CacheOperationTypes\ListPopFrontMiss;
use Momento\Cache\CacheOperationTypes\ListPushBackResponse;
use Momento\Cache\CacheOperationTypes\ListPushBackError;
use Momento\Cache\CacheOperationTypes\ListPushBackSuccess;
use Momento\Cache\CacheOperationTypes\ListPushFrontResponse;
use Momento\Cache\CacheOperationTypes\ListPushFrontError;
use Momento\Cache\CacheOperationTypes\ListPushFrontSuccess;
use Momento\Cache\CacheOperationTypes\ListRemoveValueResponse;
use Momento\Cache\CacheOperationTypes\ListRemoveValueError;
use Momento\Cache\CacheOperationTypes\ListRemoveValueSuccess;
use Momento\Cache\CacheOperationTypes\SetAddElementResponse;
use Momento\Cache\CacheOperationTypes\SetAddElementError;
use Momento\Cache\CacheOperationTypes\SetAddElementSuccess;
use Momento\Cache\CacheOperationTypes\SetFetchResponse;
use Momento\Cache\CacheOperationTypes\SetFetchError;
use Momento\Cache\CacheOperationTypes\SetFetchHit;
use Momento\Cache\CacheOperationTypes\SetFetchMiss;
use Momento\Cache\CacheOperationTypes\SetIfNotExistsResponse;
use Momento\Cache\CacheOperationTypes\SetIfNotExistsError;
use Momento\Cache\CacheOperationTypes\SetIfNotExistsNotStored;
use Momento\Cache\CacheOperationTypes\SetIfNotExistsStored;
use Momento\Cache\CacheOperationTypes\SetLengthError;
use Momento\Cache\CacheOperationTypes\SetLengthResponse;
use Momento\Cache\CacheOperationTypes\SetLengthSuccess;
use Momento\Cache\CacheOperationTypes\SetRemoveElementResponse;
use Momento\Cache\CacheOperationTypes\SetRemoveElementError;
use Momento\Cache\CacheOperationTypes\SetRemoveElementSuccess;
use Momento\Cache\CacheOperationTypes\SetResponse;
use Momento\Cache\CacheOperationTypes\SetError;
use Momento\Cache\CacheOperationTypes\SetSuccess;
use Momento\Cache\Errors\InternalServerError;
use Momento\Cache\Errors\SdkError;
use Momento\Cache\Errors\UnknownError;
use Momento\Config\IConfiguration;
use Momento\Requests\CollectionTtl;
use Momento\Utilities\_ErrorConverter;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use function Momento\Utilities\validateCacheName;
use function Momento\Utilities\validateDictionaryName;
use function Momento\Utilities\validateElement;
use function Momento\Utilities\validateFieldName;
use function Momento\Utilities\validateFields;
use function Momento\Utilities\validateKeys;
use function Momento\Utilities\validateListName;
use function Momento\Utilities\validateNullOrEmpty;
use function Momento\Utilities\validateOperationTimeout;
use function Momento\Utilities\validateSetName;
use function Momento\Utilities\validateTruncateSize;
use function Momento\Utilities\validateTtl;
use function Momento\Utilities\validateValueName;

class ScsDataClient implements LoggerAwareInterface
{

    private static int $DEFAULT_DEADLINE_MILLISECONDS = 5000;
    private int $deadline_milliseconds;
    // Used to convert deadline_milliseconds into microseconds for gRPC
    private static int $TIMEOUT_MULTIPLIER = 1000;
    private int $defaultTtlSeconds;
    private DataGrpcManager $grpcManager;
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
        $this->grpcManager = new DataGrpcManager($authProvider);
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

    private function returnCollectionTtl(?CollectionTtl $ttl): CollectionTtl
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
            $this->logger->debug("Data client error: {$status->details}");
            throw _ErrorConverter::convert($status->code, $status->details, $call->getMetadata());
        }
        return $response;
    }

    public function set(string $cacheName, string $key, string $value, int $ttlSeconds = null): SetResponse
    {
        try {
            validateCacheName($cacheName);
            validateNullOrEmpty($key, "Key");
            $ttlMillis = $this->ttlToMillis($ttlSeconds);
            $setRequest = new _SetRequest();
            $setRequest->setCacheKey($key);
            $setRequest->setCacheBody($value);
            $setRequest->setTtlMilliseconds($ttlMillis);
            $call = $this->grpcManager->client->Set(
                $setRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]
            );
            $this->processCall($call);
        } catch (SdkError $e) {
            return new SetError($e);
        } catch (Exception $e) {
            return new SetError(new UnknownError($e->getMessage()));
        }
        return new SetSuccess();
    }

    public function get(string $cacheName, string $key): GetResponse
    {
        try {
            validateCacheName($cacheName);
            validateNullOrEmpty($key, "Key");
            $getRequest = new _GetRequest();
            $getRequest->setCacheKey($key);
            $call = $this->grpcManager->client->Get(
                $getRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]
            );
            $response = $this->processCall($call);
            $ecacheResult = $response->getResult();
            if ($ecacheResult == ECacheResult::Hit) {
                return new GetHit($response);
            } elseif ($ecacheResult == ECacheResult::Miss) {
                return new GetMiss();
            } else {
                throw new InternalServerError("CacheService returned an unexpected result: $ecacheResult");
            }
        } catch (SdkError $e) {
            return new GetError($e);
        } catch (Exception $e) {
            return new GetError(new UnknownError($e->getMessage()));
        }
    }

    public function setIfNotExists(string $cacheName, string $key, string $value, int $ttlSeconds = null): SetIfNotExistsResponse
    {
        try {
            validateCacheName($cacheName);
            validateNullOrEmpty($key, "Key");
            $ttlMillis = $this->ttlToMillis($ttlSeconds);
            $setIfNotExistsRequest = new _SetIfNotExistsRequest();
            $setIfNotExistsRequest->setCacheKey($key);
            $setIfNotExistsRequest->setCacheBody($value);
            $setIfNotExistsRequest->setTtlMilliseconds($ttlMillis);
            $call = $this->grpcManager->client->SetIfNotExists(
                $setIfNotExistsRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]
            );
            $setIfNotExistsResponse = $this->processCall($call);
        } catch (SdkError $e) {
            return new SetIfNotExistsError($e);
        } catch (Exception $e) {
            return new SetIfNotExistsError(new UnknownError($e->getMessage()));
        }
        if ($setIfNotExistsResponse->hasStored()) {
            return new SetIfNotExistsStored();
        }
        return new SetIfNotExistsNotStored();
    }


    public function delete(string $cacheName, string $key): DeleteResponse
    {
        try {
            validateCacheName($cacheName);
            validateNullOrEmpty($key, "Key");
            $deleteRequest = new _DeleteRequest();
            $deleteRequest->setCacheKey($key);
            $call = $this->grpcManager->client->Delete(
                $deleteRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]
            );
            $this->processCall($call);
        } catch (SdkError $e) {
            return new DeleteError($e);
        } catch (Exception $e) {
            return new DeleteError(new UnknownError($e->getMessage()));
        }
        return new DeleteSuccess();
    }

    public function keysExist(string $cacheName, array $keys): KeysExistResponse
    {
        try {
            validateCacheName($cacheName);
            validateKeys($keys);
            $keysExistRequest = new _KeysExistRequest();
            $keysExistRequest->setCacheKeys($keys);
            $call = $this->grpcManager->client->KeysExist(
                $keysExistRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]
            );
            $response = $this->processCall($call);
        } catch (SdkError $e) {
            return new KeysExistError($e);
        } catch (Exception $e) {
            return new KeysExistError(new UnknownError($e->getMessage()));
        }
        return new KeysExistSuccess($response, $keys);
    }

    public function keyExists(string $cacheName, string $key): KeyExistsResponse
    {
        $response = $this->keysExist($cacheName, [$key]);
        if ($response instanceof KeysExistError) {
            return new KeyExistsError($response->innerException());
        }
        return new KeyExistsSuccess($response->asSuccess()->exists()[0]);
    }

    public function increment(string $cacheName, string $key, int $amount, int $ttlSeconds=null) : IncrementResponse {
        try {
            validateCacheName($cacheName);
            validateNullOrEmpty($key, "Key");
            $ttlMillis = $this->ttlToMillis($ttlSeconds);
            $incrementRequest = new _IncrementRequest();
            $incrementRequest->setCacheKey($key);
            $incrementRequest->setAmount($amount);
            $incrementRequest->setTtlMilliseconds($ttlMillis);
            $call = $this->grpcManager->client->Increment(
                $incrementRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]
            );
            $response = $this->processCall($call);
        } catch (SdkError $e) {
            return new IncrementError($e);
        } catch (Exception $e) {
            return new IncrementError(new UnknownError($e->getMessage()));
        }
        return new IncrementSuccess($response);
    }

    public function listFetch(string $cacheName, string $listName): ListFetchResponse
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
            return new ListFetchError($e);
        } catch (Exception $e) {
            return new ListFetchError(new UnknownError($e->getMessage()));
        }
        if (!$response->hasFound()) {
            return new ListFetchMiss();
        }
        return new ListFetchHit($response);
    }

    public function listPushFront(
        string $cacheName, string $listName, string $value, ?int $truncateBackToSize = null, ?CollectionTtl $ttl = null
    ): ListPushFrontResponse
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
            return new ListPushFrontError($e);
        } catch (Exception $e) {
            return new ListPushFrontError(new UnknownError($e->getMessage()));
        }
        return new ListPushFrontSuccess($response);
    }

    public function listPushBack(
        string $cacheName, string $listName, string $value, ?int $truncateFrontToSize = null, ?CollectionTtl $ttl = null
    ): ListPushBackResponse
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
            return new ListPushBackError($e);
        } catch (Exception $e) {
            return new ListPushBackError(new UnknownError($e->getMessage()));
        }
        return new ListPushBackSuccess($response);
    }

    public function listPopFront(string $cacheName, string $listName): ListPopFrontResponse
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
            return new ListPopFrontError($e);
        } catch (Exception $e) {
            return new ListPopFrontError(new UnknownError($e->getMessage()));
        }
        if (!$response->hasFound()) {
            return new ListPopFrontMiss();
        }
        return new ListPopFrontHit($response);
    }

    public function listPopBack(string $cacheName, string $listName): ListPopBackResponse
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
            return new ListPopBackError($e);
        } catch (Exception $e) {
            return new ListPopBackError(new UnknownError($e->getMessage()));
        }
        if (!$response->hasFound()) {
            return new ListPopBackMiss();
        }
        return new ListPopBackHit($response);
    }

    public function listRemoveValue(string $cacheName, string $listName, string $value): ListRemoveValueResponse
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
            return new ListRemoveValueError($e);
        } catch (Exception $e) {
            return new ListRemoveValueError(new UnknownError($e->getMessage()));
        }
        return new ListRemoveValueSuccess();
    }

    public function listLength(string $cacheName, string $listName): ListLengthResponse
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
            return new ListLengthError($e);
        } catch (Exception $e) {
            return new ListLengthError(new UnknownError($e->getMessage()));
        }
        return new ListLengthSuccess($response);
    }

    public function dictionarySetField(string $cacheName, string $dictionaryName, string $field, string $value, ?CollectionTtl $ttl = null): DictionarySetFieldResponse
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
            return new DictionarySetFieldError($e);
        } catch (Exception $e) {
            return new DictionarySetFieldError(new UnknownError($e->getMessage()));
        }
        return new DictionarySetFieldSuccess();
    }

    private function toSingletonFieldValuePair(string $field, string $value): _DictionaryFieldValuePair
    {
        $singletonPair = new _DictionaryFieldValuePair();
        $singletonPair->setField($field);
        $singletonPair->setValue($value);
        return $singletonPair;
    }

    public function dictionaryGetField(string $cacheName, string $dictionaryName, string $field): DictionaryGetFieldResponse
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
            return new DictionaryGetFieldError($e);
        } catch (Exception $e) {
            return new DictionaryGetFieldError(new UnknownError($e->getMessage()));
        }
        if ($dictionaryGetFieldResponse->hasMissing()) {
            return new DictionaryGetFieldMiss();
        }
        if ($dictionaryGetFieldResponse->getFound()->getItems()->count() == 0) {
            return new DictionaryGetFieldError(new UnknownError("_DictionaryGetResponseResponse contained no data but was found"));
        }
        if ($dictionaryGetFieldResponse->getFound()->getItems()[0]->getResult() == ECacheResult::Miss) {
            return new DictionaryGetFieldMiss();
        }
        return new DictionaryGetFieldHit($field, $dictionaryGetFieldResponse);
    }

    public function dictionaryFetch(string $cacheName, string $dictionaryName): DictionaryFetchResponse
    {
        try {
            validateCacheName($cacheName);
            validateDictionaryName($dictionaryName);
            $dictionaryFetchRequest = new _DictionaryFetchRequest();
            $dictionaryFetchRequest->setDictionaryName($dictionaryName);
            $call = $this->grpcManager->client->DictionaryFetch($dictionaryFetchRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]);
            $dictionaryFetchResponse = $this->processCall($call);
        } catch (SdkError $e) {
            return new DictionaryFetchError($e);
        } catch (Exception $e) {
            return new DictionaryFetchError(new UnknownError($e->getMessage()));
        }
        if ($dictionaryFetchResponse->hasFound()) {
            return new DictionaryFetchHit($dictionaryFetchResponse);
        }
        return new DictionaryFetchMiss();
    }

    public function dictionarySetFields(string $cacheName, string $dictionaryName, array $elements, ?CollectionTtl $ttl = null): DictionarySetFieldsResponse
    {
        try {
            $collectionTtl = $this->returnCollectionTtl($ttl);
            validateCacheName($cacheName);
            validateDictionaryName($dictionaryName);
            validateKeys(array_keys($elements));
            $ttlMillis = $this->ttlToMillis($collectionTtl->getTtl());
            $protoItems = [];
            foreach ($elements as $field => $value) {
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
            return new DictionarySetFieldsError($e);
        } catch (Exception $e) {
            return new DictionarySetFieldsError(new UnknownError($e->getMessage()));
        }
        return new DictionarySetFieldsSuccess();
    }

    public function dictionaryGetFields(string $cacheName, string $dictionaryName, array $fields): DictionaryGetFieldsResponse
    {
        try {
            validateCacheName($cacheName);
            validateDictionaryName($dictionaryName);
            validateFields($fields);
            $dictionaryGetFieldsRequest = new _DictionaryGetRequest();
            $dictionaryGetFieldsRequest->setDictionaryName($dictionaryName);
            $dictionaryGetFieldsRequest->setFields($fields);
            $call = $this->grpcManager->client->DictionaryGet($dictionaryGetFieldsRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]);
            $dictionaryGetFieldsResponse = $this->processCall($call);
        } catch (SdkError $e) {
            return new DictionaryGetFieldsError($e);
        } catch (Exception $e) {
            return new DictionaryGetFieldsError(new UnknownError($e->getMessage()));
        }
        if ($dictionaryGetFieldsResponse->hasFound()) {
            return new DictionaryGetFieldsHit($dictionaryGetFieldsResponse, fields: $fields);
        }
        return new DictionaryGetFieldsMiss();

    }

    public function dictionaryIncrement(
        string $cacheName, string $dictionaryName, string $field, int $amount = 1, ?CollectionTtl $ttl = null
    ): DictionaryIncrementResponse
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
            return new DictionaryIncrementError($e);
        } catch (Exception $e) {
            return new DictionaryIncrementError(new UnknownError($e->getMessage()));
        }
        return new DictionaryIncrementSuccess($response);
    }

    public function dictionaryRemoveField(string $cacheName, string $dictionaryName, string $field): DictionaryRemoveFieldResponse
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
            return new DictionaryRemoveFieldError($e);
        } catch (Exception $e) {
            return new DictionaryRemoveFieldError(new UnknownError($e->getMessage()));
        }
        return new DictionaryRemoveFieldSuccess();
    }

    public function dictionaryRemoveFields(string $cacheName, string $dictionaryName, array $fields): DictionaryRemoveFieldsResponse
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
            return new DictionaryRemoveFieldsError($e);
        } catch (Exception $e) {
            return new DictionaryRemoveFieldsError(new UnknownError($e->getMessage()));
        }
        return new DictionaryRemoveFieldsSuccess();
    }

    public function setAddElement(string $cacheName, string $setName, string $element, ?CollectionTtl $ttl = null): SetAddElementResponse
    {
        try {
            $collectionTtl = $this->returnCollectionTtl($ttl);
            validateCacheName($cacheName);
            validateSetName($setName);
            validateElement($element);
            $ttlMillis = $this->ttlToMillis($collectionTtl->getTtl());
            validateTtl($ttlMillis);
            $setAddElementRequest = new _SetUnionRequest();
            $setAddElementRequest->setSetName($setName);
            $setAddElementRequest->setRefreshTtl($collectionTtl->getRefreshTtl());
            $setAddElementRequest->setTtlMilliseconds($ttlMillis);
            $setAddElementRequest->setElements([$element]);
            $call = $this->grpcManager->client->SetUnion($setAddElementRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]);
            $this->processCall($call);
        } catch (SdkError $e) {
            return new SetAddElementError($e);
        } catch (Exception $e) {
            return new SetAddElementError(new UnknownError($e->getMessage()));
        }
        return new SetAddElementSuccess();
    }

    public function setFetch(string $cacheName, string $setName): SetFetchResponse
    {
        try {
            validateCacheName($cacheName);
            validateSetName($setName);
            $setFetchRequest = new _SetFetchRequest();
            $setFetchRequest->setSetName($setName);
            $call = $this->grpcManager->client->SetFetch($setFetchRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]);
            $setFetchResponse = $this->processCall($call);
        } catch (SdkError $e) {
            return new SetFetchError($e);
        } catch (Exception $e) {
            return new SetFetchError(new UnknownError($e->getMessage()));
        }
        if ($setFetchResponse->hasFound()) {
            return new SetFetchHit($setFetchResponse);
        }
        return new SetFetchMiss();
    }

    public function setLength(string $cacheName, string $setName): SetLengthResponse
    {
        try {
            validateCacheName($cacheName);
            validateSetName($setName);
            $setLengthRequest = new _SetLengthRequest();
            $setLengthRequest->setSetName($setName);
            $call = $this->grpcManager->client->SetLength(
                $setLengthRequest, ["cache" => [$cacheName]], ["timeout" => $this->timeout]
            );
            $response = $this->processCall($call);
        } catch (SdkError $e) {
            return new SetLengthError($e);
        } catch (Exception $e) {
            return new SetLengthError(new UnknownError($e->getMessage()));
        }
        return new SetLengthSuccess($response);
    }

    public function setRemoveElement(string $cacheName, string $setName, string $element): SetRemoveElementResponse
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
            return new SetRemoveElementError($e);
        } catch (Exception $e) {
            return new SetRemoveElementError(new UnknownError($e->getMessage()));
        }
        return new SetRemoveElementSuccess();
    }

    public function close(): void {
        $this->grpcManager->close();
    }
}
