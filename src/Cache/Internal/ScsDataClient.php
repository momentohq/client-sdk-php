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
use Cache_client\_GetBatchRequest;
use Cache_client\_GetRequest;
use Cache_client\_IncrementRequest;
use Cache_client\_ItemGetTtlRequest;
use Cache_client\_KeysExistRequest;
use Cache_client\_ListFetchRequest;
use Cache_client\_ListLengthRequest;
use Cache_client\_ListPopBackRequest;
use Cache_client\_ListPopFrontRequest;
use Cache_client\_ListPushBackRequest;
use Cache_client\_ListPushFrontRequest;
use Cache_client\_ListRemoveRequest;
use Cache_client\_SetBatchRequest;
use Cache_client\_SetContainsRequest;
use Cache_client\_SetDifferenceRequest;
use Cache_client\_SetDifferenceRequest\_Subtrahend;
use Cache_client\_SetDifferenceRequest\_Subtrahend\_Set;
use Cache_client\_SetFetchRequest;
use Cache_client\_SetIfRequest;
use Cache_client\_SetLengthRequest;
use Cache_client\_SetRequest;
use Cache_client\_SetUnionRequest;
use Cache_client\_SortedSetElement;
use Cache_client\_SortedSetFetchRequest;
use Cache_client\_SortedSetGetScoreRequest;
use Cache_client\_SortedSetIncrementRequest;
use Cache_client\_SortedSetLengthByScoreRequest;
use Cache_client\_SortedSetPutRequest;
use Cache_client\_SortedSetRemoveRequest;
use Cache_client\_SortedSetUnionStoreRequest;
use Cache_client\_SortedSetUnionStoreRequest\AggregateFunction;
use Cache_client\_SortedSetUnionStoreRequest\_Source;
use Cache_client\_UpdateTtlRequest;
use Cache_client\ECacheResult;
use Common\_Unbounded;
use Common\Absent;
use Common\AbsentOrEqual;
use Common\Equal;
use Common\NotEqual;
use Common\Present;
use Common\PresentAndNotEqual;
use Exception;
use Grpc\UnaryCall;
use Momento\Auth\ICredentialProvider;
use Momento\Cache\CacheOperationTypes\DecreaseTtlError;
use Momento\Cache\CacheOperationTypes\DecreaseTtlMiss;
use Momento\Cache\CacheOperationTypes\DecreaseTtlNotSet;
use Momento\Cache\CacheOperationTypes\DecreaseTtlResponse;
use Momento\Cache\CacheOperationTypes\DecreaseTtlSet;
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
use Momento\Cache\CacheOperationTypes\GetBatchError;
use Momento\Cache\CacheOperationTypes\GetBatchResponse;
use Momento\Cache\CacheOperationTypes\GetBatchSuccess;
use Momento\Cache\CacheOperationTypes\IncreaseTtlError;
use Momento\Cache\CacheOperationTypes\IncreaseTtlMiss;
use Momento\Cache\CacheOperationTypes\IncreaseTtlNotSet;
use Momento\Cache\CacheOperationTypes\IncreaseTtlResponse;
use Momento\Cache\CacheOperationTypes\IncreaseTtlSet;
use Momento\Cache\CacheOperationTypes\ItemGetTtlError;
use Momento\Cache\CacheOperationTypes\ItemGetTtlHit;
use Momento\Cache\CacheOperationTypes\ItemGetTtlMiss;
use Momento\Cache\CacheOperationTypes\ItemGetTtlResponse;
use Momento\Cache\CacheOperationTypes\ResponseFuture;
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
use Momento\Cache\CacheOperationTypes\SetAddElementsResponse;
use Momento\Cache\CacheOperationTypes\SetAddElementsError;
use Momento\Cache\CacheOperationTypes\SetAddElementsSuccess;
use Momento\Cache\CacheOperationTypes\SetContainsElementsResponse;
use Momento\Cache\CacheOperationTypes\SetContainsElementsError;
use Momento\Cache\CacheOperationTypes\SetContainsElementsHit;
use Momento\Cache\CacheOperationTypes\SetContainsElementsMiss;
use Momento\Cache\CacheOperationTypes\SetBatchError;
use Momento\Cache\CacheOperationTypes\SetBatchResponse;
use Momento\Cache\CacheOperationTypes\SetBatchSuccess;
use Momento\Cache\CacheOperationTypes\SetFetchResponse;
use Momento\Cache\CacheOperationTypes\SetFetchError;
use Momento\Cache\CacheOperationTypes\SetFetchHit;
use Momento\Cache\CacheOperationTypes\SetFetchMiss;
use Momento\Cache\CacheOperationTypes\SetIfAbsentOrEqualError;
use Momento\Cache\CacheOperationTypes\SetIfAbsentOrEqualResponse;
use Momento\Cache\CacheOperationTypes\SetIfAbsentOrEqualStored;
use Momento\Cache\CacheOperationTypes\SetIfAbsentOrEqualNotStored;
use Momento\Cache\CacheOperationTypes\SetIfAbsentStored;
use Momento\Cache\CacheOperationTypes\SetIfAbsentNotStored;
use Momento\Cache\CacheOperationTypes\SetIfAbsentError;
use Momento\Cache\CacheOperationTypes\SetIfAbsentResponse;
use Momento\Cache\CacheOperationTypes\SetIfEqualError;
use Momento\Cache\CacheOperationTypes\SetIfEqualResponse;
use Momento\Cache\CacheOperationTypes\SetIfEqualStored;
use Momento\Cache\CacheOperationTypes\SetIfEqualNotStored;
use Momento\Cache\CacheOperationTypes\SetIfNotEqualError;
use Momento\Cache\CacheOperationTypes\SetIfNotEqualResponse;
use Momento\Cache\CacheOperationTypes\SetIfNotEqualStored;
use Momento\Cache\CacheOperationTypes\SetIfNotEqualNotStored;
use Momento\Cache\CacheOperationTypes\SetIfPresentAndNotEqualNotStored;
use Momento\Cache\CacheOperationTypes\SetIfPresentAndNotEqualStored;
use Momento\Cache\CacheOperationTypes\SetIfPresentAndNotEqualError;
use Momento\Cache\CacheOperationTypes\SetIfPresentAndNotEqualResponse;
use Momento\Cache\CacheOperationTypes\SetIfPresentResponse;
use Momento\Cache\CacheOperationTypes\SetIfPresentStored;
use Momento\Cache\CacheOperationTypes\SetIfPresentNotStored;
use Momento\Cache\CacheOperationTypes\SetIfPresentError;
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
use Momento\Cache\CacheOperationTypes\SortedSetFetchError;
use Momento\Cache\CacheOperationTypes\SortedSetFetchHit;
use Momento\Cache\CacheOperationTypes\SortedSetFetchMiss;
use Momento\Cache\CacheOperationTypes\SortedSetFetchResponse;
use Momento\Cache\CacheOperationTypes\SortedSetGetScoreHit;
use Momento\Cache\CacheOperationTypes\SortedSetGetScoreMiss;
use Momento\Cache\CacheOperationTypes\SortedSetGetScoreError;
use Momento\Cache\CacheOperationTypes\SortedSetGetScoreResponse;
use Momento\Cache\CacheOperationTypes\SortedSetIncrementScoreError;
use Momento\Cache\CacheOperationTypes\SortedSetIncrementScoreResponse;
use Momento\Cache\CacheOperationTypes\SortedSetIncrementScoreSuccess;
use Momento\Cache\CacheOperationTypes\SortedSetLengthByScoreError;
use Momento\Cache\CacheOperationTypes\SortedSetLengthByScoreHit;
use Momento\Cache\CacheOperationTypes\SortedSetLengthByScoreMiss;
use Momento\Cache\CacheOperationTypes\SortedSetLengthByScoreResponse;
use Momento\Cache\CacheOperationTypes\SortedSetPutElementError;
use Momento\Cache\CacheOperationTypes\SortedSetPutElementResponse;
use Momento\Cache\CacheOperationTypes\SortedSetPutElementsError;
use Momento\Cache\CacheOperationTypes\SortedSetPutElementsResponse;
use Momento\Cache\CacheOperationTypes\SortedSetPutElementsSuccess;
use Momento\Cache\CacheOperationTypes\SortedSetPutElementSuccess;
use Momento\Cache\CacheOperationTypes\SortedSetRemoveElementError;
use Momento\Cache\CacheOperationTypes\SortedSetRemoveElementResponse;
use Momento\Cache\CacheOperationTypes\SortedSetRemoveElementSuccess;
use Momento\Cache\CacheOperationTypes\SortedSetRemoveElementsError;
use Momento\Cache\CacheOperationTypes\SortedSetRemoveElementsResponse;
use Momento\Cache\CacheOperationTypes\SortedSetRemoveElementsSuccess;
use Momento\Cache\CacheOperationTypes\SortedSetUnionStoreResponse;
use Momento\Cache\CacheOperationTypes\SortedSetUnionStoreSuccess;
use Momento\Cache\CacheOperationTypes\SortedSetUnionStoreError;
use Momento\Cache\CacheOperationTypes\UpdateTtlError;
use Momento\Cache\CacheOperationTypes\UpdateTtlMiss;
use Momento\Cache\CacheOperationTypes\UpdateTtlResponse;
use Momento\Cache\CacheOperationTypes\UpdateTtlSet;
use Momento\Cache\Errors\InternalServerError;
use Momento\Cache\Errors\InvalidArgumentError;
use Momento\Cache\Errors\SdkError;
use Momento\Cache\Errors\UnknownError;
use Momento\Config\IConfiguration;
use Momento\Requests\CollectionTtl;
use Momento\Requests\SortedSetUnionStoreAggregateFunction;
use Momento\Utilities\_ErrorConverter;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;
use function Momento\Utilities\validateCacheName;
use function Momento\Utilities\validateDictionaryName;
use function Momento\Utilities\validateElement;
use function Momento\Utilities\validateElements;
use function Momento\Utilities\validateFieldName;
use function Momento\Utilities\validateFields;
use function Momento\Utilities\validateKeys;
use function Momento\Utilities\validateListName;
use function Momento\Utilities\validateNullOrEmpty;
use function Momento\Utilities\validateOperationTimeout;
use function Momento\Utilities\validateSetName;
use function Momento\Utilities\validateSortedSetElements;
use function Momento\Utilities\validateSortedSetName;
use function Momento\Utilities\validateSortedSetOrder;
use function Momento\Utilities\validateSortedSetRanks;
use function Momento\Utilities\validateSortedSetScore;
use function Momento\Utilities\validateSortedSetScores;
use function Momento\Utilities\validateSortedSetValues;
use function Momento\Utilities\validateTruncateSize;
use function Momento\Utilities\validateTtl;
use function Momento\Utilities\validateValueName;

class ScsDataClient implements LoggerAwareInterface
{

    private static int $DEFAULT_DEADLINE_MILLISECONDS = 5000;
    private int $deadline_milliseconds;
    // Used to convert deadline_milliseconds into microseconds for gRPC
    private static int $TIMEOUT_MULTIPLIER = 1000;
    private $defaultTtlSeconds;
    private DataGrpcManager $grpcManager;
    private LoggerInterface $logger;
    private $timeout;

    public function __construct(IConfiguration $configuration, ICredentialProvider $authProvider, $defaultTtlSeconds)
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
        $this->grpcManager = new DataGrpcManager($authProvider, $configuration);
        $this->setLogger($configuration->getLoggerFactory()->getLogger(get_class($this)));
    }

    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * @param int|float|null $ttl
     * @return int
     * @throws \Momento\Cache\Errors\InvalidArgumentError
     */
    private function ttlToMillis($ttl = null): int
    {
        if (!$ttl) {
            $ttl = $this->defaultTtlSeconds;
        }
        validateTtl($ttl);
        return (int)round($ttl * 1000);
    }

    private function returnCollectionTtl(?CollectionTtl $ttl): CollectionTtl
    {
        if (!$ttl) {
            return CollectionTtl::fromCacheTtl();
        }
        return $ttl;
    }

    private function processCall(UnaryCall $call)
    {
        [$response, $status] = $call->wait();
        if ($status->code !== 0) {
            $this->logger->debug("Data client error: {$status->details}");
            throw _ErrorConverter::convert($status, $call->getMetadata());
        }
        return $response;
    }

    /**
     * @param int|float|null $ttlSeconds
     *
     * @return ResponseFuture<SetResponse>
     */
    public function set(string $cacheName, string $key, string $value, $ttlSeconds = null): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateNullOrEmpty($key, "Key");
            $ttlMillis = $this->ttlToMillis($ttlSeconds);
            $setRequest = new _SetRequest();
            $setRequest->setCacheKey($key);
            $setRequest->setCacheBody($value);
            $setRequest->setTtlMilliseconds($ttlMillis);
            $this->logger->debug(sprintf("set %s %s %u", $key, $value, $ttlMillis));
            $call = $this->grpcManager->client->Set(
                $setRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SetError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SetError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): SetResponse {
                try {
                    $this->processCall($call);
                } catch (SdkError $e) {
                    return new SetError($e);
                } catch (Exception $e) {
                    return new SetError(new UnknownError($e->getMessage(), 0, $e));
                }

                return new SetSuccess();
            }
        );
    }

    /**
     * @return ResponseFuture<GetResponse>
     */
    public function get(string $cacheName, string $key): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateNullOrEmpty($key, "Key");
            $getRequest = new _GetRequest();
            $getRequest->setCacheKey($key);
            $this->logger->debug("get $key");
            $call = $this->grpcManager->client->Get(
                $getRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new GetError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new GetError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): GetResponse {
                try {
                    $response = $this->processCall($call);
                    $result = $response->getResult();
                    if ($result == ECacheResult::Hit) {
                        return new GetHit($response);
                    } elseif ($result == ECacheResult::Miss) {
                        return new GetMiss();
                    } else {
                        throw new InternalServerError("CacheService returned an unexpected result: $result");
                    }
                } catch (SdkError $e) {
                    return new GetError($e);
                } catch (Exception $e) {
                    return new GetError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @param string $cacheName
     * @param string $key
     * @param string $value
     * @param int|float|null $ttlSeconds
     * @return ResponseFuture<SetIfPresentResponse>
     */
    public function setIfPresent(string $cacheName, string $key, string $value, $ttlSeconds = null): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateNullOrEmpty($key, "Key");
            $ttlMillis = $this->ttlToMillis($ttlSeconds);
            $setIfPresentRequest = new _SetIfRequest();
            $setIfPresentRequest->setCacheKey($key);
            $setIfPresentRequest->setCacheBody($value);
            $setIfPresentRequest->setTtlMilliseconds($ttlMillis);
            $setIfPresentRequest->setPresent(new Present());
            $this->logger->debug(sprintf("setIfPresent %s %s %u", $key, $value, $ttlMillis));
            $call = $this->grpcManager->client->SetIf(
                $setIfPresentRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SetIfPresentError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SetIfPresentError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): SetIfPresentResponse {
                try {
                    $response = $this->processCall($call);

                    if ($response->hasStored()) {
                        return new SetIfPresentStored();
                    }

                    return new SetIfPresentNotStored();
                } catch (SdkError $e) {
                    return new SetIfPresentError($e);
                } catch (Exception $e) {
                    return new SetIfPresentError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @param string $cacheName
     * @param string $key
     * @param string $value
     * @param string $notEqual
     * @param int|float|null $ttlSeconds
     * @return ResponseFuture<SetIfPresentAndNotEqualResponse>
     */
    public function setIfPresentAndNotEqual(string $cacheName, string $key, string $value, string $notEqual, $ttlSeconds = null): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateNullOrEmpty($key, "Key");
            $ttlMillis = $this->ttlToMillis($ttlSeconds);
            $setIfPresentRequest = new _SetIfRequest();
            $setIfPresentRequest->setCacheKey($key);
            $setIfPresentRequest->setCacheBody($value);
            $setIfPresentRequest->setTtlMilliseconds($ttlMillis);
            $setIfPresentRequest->setPresentAndNotEqual(new PresentAndNotEqual());
            $setIfPresentRequest->getPresentAndNotEqual()->setValueToCheck($notEqual);
            $this->logger->debug(sprintf("setIfPresentAndNotEqual %s %s %s %u", $key, $value, $notEqual, $ttlMillis));
            $call = $this->grpcManager->client->SetIf(
                $setIfPresentRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SetIfPresentAndNotEqualError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SetIfPresentAndNotEqualError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): SetIfPresentAndNotEqualResponse {
                try {
                    $response = $this->processCall($call);

                    if ($response->hasStored()) {
                        return new SetIfPresentAndNotEqualStored();
                    }

                    return new SetIfPresentAndNotEqualNotStored();
                } catch (SdkError $e) {
                    return new SetIfPresentAndNotEqualError($e);
                } catch (Exception $e) {
                    return new SetIfPresentAndNotEqualError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @param string $cacheName
     * @param string $key
     * @param string $value
     * @param int|float|null $ttlSeconds
     * @return ResponseFuture<SetIfAbsentResponse>
     */
    public function setIfAbsent(string $cacheName, string $key, string $value, $ttlSeconds = null): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateNullOrEmpty($key, "Key");
            $ttlMillis = $this->ttlToMillis($ttlSeconds);
            $setIfAbsentRequest = new _SetIfRequest();
            $setIfAbsentRequest->setCacheKey($key);
            $setIfAbsentRequest->setCacheBody($value);
            $setIfAbsentRequest->setTtlMilliseconds($ttlMillis);
            $setIfAbsentRequest->setAbsent(new Absent());
            $this->logger->debug(sprintf("setIfAbsent %s %s %u", $key, $value, $ttlMillis));
            $call = $this->grpcManager->client->SetIf(
                $setIfAbsentRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SetIfAbsentError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SetIfAbsentError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): SetIfAbsentResponse {
                try {
                    $response = $this->processCall($call);

                    if ($response->hasStored()) {
                        return new SetIfAbsentStored();
                    }

                    return new SetIfAbsentNotStored();
                } catch (SdkError $e) {
                    return new SetIfAbsentError($e);
                } catch (Exception $e) {
                    return new SetIfAbsentError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @param string $cacheName
     * @param string $key
     * @param string $value
     * @param string $equal
     * @param int|float|null $ttlSeconds
     * @return ResponseFuture<SetIfAbsentOrEqualResponse>
     */
    public function setIfAbsentOrEqual(string $cacheName, string $key, string $value, string $equal, $ttlSeconds = null): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateNullOrEmpty($key, "Key");
            $ttlMillis = $this->ttlToMillis($ttlSeconds);
            $setIfAbsentRequest = new _SetIfRequest();
            $setIfAbsentRequest->setCacheKey($key);
            $setIfAbsentRequest->setCacheBody($value);
            $setIfAbsentRequest->setTtlMilliseconds($ttlMillis);
            $setIfAbsentRequest->setAbsentOrEqual(new AbsentOrEqual());
            $setIfAbsentRequest->getAbsentOrEqual()->setValueToCheck($equal);
            $this->logger->debug(sprintf("setIfAbsentOrEqual %s %s %s %u", $key, $value, $equal, $ttlMillis));
            $call = $this->grpcManager->client->SetIf(
                $setIfAbsentRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SetIfAbsentOrEqualError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SetIfAbsentOrEqualError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): SetIfAbsentOrEqualResponse {
                try {
                    $response = $this->processCall($call);

                    if ($response->hasStored()) {
                        return new SetIfAbsentOrEqualStored();
                    }

                    return new SetIfAbsentOrEqualNotStored();
                } catch (SdkError $e) {
                    return new SetIfAbsentOrEqualError($e);
                } catch (Exception $e) {
                    return new SetIfAbsentOrEqualError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @param string $cacheName
     * @param string $key
     * @param string $value
     * @param string $equal
     * @param int|float|null $ttlSeconds
     * @return ResponseFuture<SetIfEqualResponse>
     */
    public function setIfEqual(string $cacheName, string $key, string $value, string $equal, $ttlSeconds = null): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateNullOrEmpty($key, "Key");
            $ttlMillis = $this->ttlToMillis($ttlSeconds);
            $setIfAbsentRequest = new _SetIfRequest();
            $setIfAbsentRequest->setCacheKey($key);
            $setIfAbsentRequest->setCacheBody($value);
            $setIfAbsentRequest->setTtlMilliseconds($ttlMillis);
            $setIfAbsentRequest->setEqual(new Equal());
            $setIfAbsentRequest->getEqual()->setValueToCheck($equal);
            $this->logger->debug(sprintf("setIfEqual %s %s %s %u", $key, $value, $equal, $ttlMillis));
            $call = $this->grpcManager->client->SetIf(
                $setIfAbsentRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SetIfEqualError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SetIfEqualError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): SetIfEqualResponse {
                try {
                    $response = $this->processCall($call);

                    if ($response->hasStored()) {
                        return new SetIfEqualStored();
                    }

                    return new SetIfEqualNotStored();
                } catch (SdkError $e) {
                    return new SetIfEqualError($e);
                } catch (Exception $e) {
                    return new SetIfEqualError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @param string $cacheName
     * @param string $key
     * @param string $value
     * @param string $equal
     * @param int|float|null $ttlSeconds
     * @return ResponseFuture<SetIfNotEqualResponse>
     */
    public function setIfNotEqual(string $cacheName, string $key, string $value, string $equal, $ttlSeconds = null): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateNullOrEmpty($key, "Key");
            $ttlMillis = $this->ttlToMillis($ttlSeconds);
            $setIfAbsentRequest = new _SetIfRequest();
            $setIfAbsentRequest->setCacheKey($key);
            $setIfAbsentRequest->setCacheBody($value);
            $setIfAbsentRequest->setTtlMilliseconds($ttlMillis);
            $setIfAbsentRequest->setNotEqual(new NotEqual());
            $setIfAbsentRequest->getNotEqual()->setValueToCheck($equal);
            $this->logger->debug(sprintf("setIfNotEqual %s %s %s %u", $key, $value, $equal, $ttlMillis));
            $call = $this->grpcManager->client->SetIf(
                $setIfAbsentRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SetIfNotEqualError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SetIfNotEqualError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): SetIfNotEqualResponse {
                try {
                    $response = $this->processCall($call);

                    if ($response->hasStored()) {
                        return new SetIfNotEqualStored();
                    }

                    return new SetIfNotEqualNotStored();
                } catch (SdkError $e) {
                    return new SetIfNotEqualError($e);
                } catch (Exception $e) {
                    return new SetIfNotEqualError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * SetIfNotExists is deprecated on the service. Here we call the new SetIfAbsent
     * and return SetIfNotExists responses.
     * @param string $cacheName
     * @param string $key
     * @param string $value
     * @param int|float|null $ttlSeconds
     * @return ResponseFuture<SetIfNotExistsResponse>
     * @deprecated Use SetIfAbsent instead
     */
    public function setIfNotExists(string $cacheName, string $key, string $value, $ttlSeconds = null): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateNullOrEmpty($key, "Key");
            $ttlMillis = $this->ttlToMillis($ttlSeconds);
            $setIfNotExistsRequest = new _SetIfRequest();
            $setIfNotExistsRequest->setAbsent(new Absent());
            $setIfNotExistsRequest->setCacheKey($key);
            $setIfNotExistsRequest->setCacheBody($value);
            $setIfNotExistsRequest->setTtlMilliseconds($ttlMillis);
            $this->logger->debug(sprintf("setIfNotExists %s %s %u", $key, $value, $ttlMillis));
            $call = $this->grpcManager->client->SetIf(
                $setIfNotExistsRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SetIfNotExistsError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SetIfNotExistsError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): SetIfNotExistsResponse {
                try {
                    $response = $this->processCall($call);

                    if ($response->hasStored()) {
                        return new SetIfNotExistsStored();
                    }

                    return new SetIfNotExistsNotStored();
                } catch (SdkError $e) {
                    return new SetIfNotExistsError($e);
                } catch (Exception $e) {
                    return new SetIfNotExistsError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @return ResponseFuture<DeleteResponse>
     */
    public function delete(string $cacheName, string $key): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateNullOrEmpty($key, "Key");
            $deleteRequest = new _DeleteRequest();
            $deleteRequest->setCacheKey($key);
            $this->logger->debug("delete $key");
            $call = $this->grpcManager->client->Delete(
                $deleteRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new DeleteError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new DeleteError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): DeleteResponse {
                try {
                    $this->processCall($call);

                    return new DeleteSuccess();
                } catch (SdkError $e) {
                    return new DeleteError($e);
                } catch (Exception $e) {
                    return new DeleteError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @return ResponseFuture<KeysExistResponse>
     */
    public function keysExist(string $cacheName, array $keys): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateKeys($keys);
            $keysExistRequest = new _KeysExistRequest();
            $keysExistRequest->setCacheKeys($keys);
            $this->logger->debug("keysExist");
            $call = $this->grpcManager->client->KeysExist(
                $keysExistRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new KeysExistError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new KeysExistError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call, $keys): KeysExistResponse {
                try {
                    $response = $this->processCall($call);

                    return new KeysExistSuccess($response, $keys);
                } catch (SdkError $e) {
                    return new KeysExistError($e);
                } catch (Exception $e) {
                    return new KeysExistError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @return ResponseFuture<KeyExistsResponse>
     */
    public function keyExists(string $cacheName, string $key): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateKeys([$key]);
            $keysExistRequest = new _KeysExistRequest();
            $keysExistRequest->setCacheKeys([$key]);
            $this->logger->debug("keyExists $key");
            $call = $this->grpcManager->client->KeysExist(
                $keysExistRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new KeyExistsError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new KeyExistsError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): KeyExistsResponse {
                try {
                    $response = $this->processCall($call);

                    return new KeyExistsSuccess($response->getExists()[0]);
                } catch (SdkError $e) {
                    return new KeyExistsError($e);
                } catch (Exception $e) {
                    return new KeyExistsError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @param int|float|null $ttlSeconds
     *
     * @return ResponseFuture<IncrementResponse>
     */
    public function increment(string $cacheName, string $key, int $amount, $ttlSeconds = null): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateNullOrEmpty($key, "Key");
            $ttlMillis = $this->ttlToMillis($ttlSeconds);
            $incrementRequest = new _IncrementRequest();
            $incrementRequest->setCacheKey($key);
            $incrementRequest->setAmount($amount);
            $incrementRequest->setTtlMilliseconds($ttlMillis);
            $this->logger->debug(sprintf("increment %s %u %u", $key, $amount, $ttlMillis));
            $call = $this->grpcManager->client->Increment(
                $incrementRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new IncrementError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new IncrementError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): IncrementResponse {
                try {
                    $response = $this->processCall($call);

                    return new IncrementSuccess($response);
                } catch (SdkError $e) {
                    return new IncrementError($e);
                } catch (Exception $e) {
                    return new IncrementError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    public function listFetch(string $cacheName, string $listName): ListFetchResponse
    {
        try {
            validateCacheName($cacheName);
            validateListName($listName);
            $listFetchRequest = new _ListFetchRequest();
            $listFetchRequest->setListName($listName);
            $this->logger->debug("listFetch $listName");
            $call = $this->grpcManager->client->ListFetch(
                $listFetchRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
            $response = $this->processCall($call);
        } catch (SdkError $e) {
            return new ListFetchError($e);
        } catch (Exception $e) {
            return new ListFetchError(new UnknownError($e->getMessage(), 0, $e));
        }
        if (!$response->hasFound()) {
            return new ListFetchMiss();
        }
        return new ListFetchHit($response);
    }

    public function listPushFront(
        string $cacheName,
        string $listName,
        string $value,
        ?int $truncateBackToSize = null,
        ?CollectionTtl $ttl = null
    ): ListPushFrontResponse {
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
            $this->logger->debug(sprintf("listPushFront %s %s %u %u", $listName, $value, $truncateBackToSize, $ttlMillis));
            $call = $this->grpcManager->client->ListPushFront(
                $listPushFrontRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
            $response = $this->processCall($call);
        } catch (SdkError $e) {
            return new ListPushFrontError($e);
        } catch (Exception $e) {
            return new ListPushFrontError(new UnknownError($e->getMessage(), 0, $e));
        }
        return new ListPushFrontSuccess($response);
    }

    public function listPushBack(
        string $cacheName,
        string $listName,
        string $value,
        ?int $truncateFrontToSize = null,
        ?CollectionTtl $ttl = null
    ): ListPushBackResponse {
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
            $this->logger->debug(sprintf("listPushBack %s %s %u %u", $listName, $value, $truncateFrontToSize, $ttlMillis));
            $call = $this->grpcManager->client->ListPushBack(
                $listPushBackRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
            $response = $this->processCall($call);
        } catch (SdkError $e) {
            return new ListPushBackError($e);
        } catch (Exception $e) {
            return new ListPushBackError(new UnknownError($e->getMessage(), 0, $e));
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
            $this->logger->debug("listPopFront $listName");
            $call = $this->grpcManager->client->ListPopFront(
                $listPopFrontRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
            $response = $this->processCall($call);
        } catch (SdkError $e) {
            return new ListPopFrontError($e);
        } catch (Exception $e) {
            return new ListPopFrontError(new UnknownError($e->getMessage(), 0, $e));
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
            $this->logger->debug("listPopBack $listName");
            $call = $this->grpcManager->client->ListPopBack(
                $listPopBackRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
            $response = $this->processCall($call);
        } catch (SdkError $e) {
            return new ListPopBackError($e);
        } catch (Exception $e) {
            return new ListPopBackError(new UnknownError($e->getMessage(), 0, $e));
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
            $this->logger->debug("listRemoveValue $listName $value");
            $call = $this->grpcManager->client->ListRemove(
                $listRemoveValueRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
            $this->processCall($call);
        } catch (SdkError $e) {
            return new ListRemoveValueError($e);
        } catch (Exception $e) {
            return new ListRemoveValueError(new UnknownError($e->getMessage(), 0, $e));
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
            $this->logger->debug("listLength $listName");
            $call = $this->grpcManager->client->ListLength(
                $listLengthRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout]
            );
            $response = $this->processCall($call);
        } catch (SdkError $e) {
            return new ListLengthError($e);
        } catch (Exception $e) {
            return new ListLengthError(new UnknownError($e->getMessage(), 0, $e));
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
            $this->logger->debug(sprintf("dictionarySetField %s %s %s %u", $dictionaryName, $field, $value, $ttlMillis));
            $call = $this->grpcManager->client->DictionarySet(
                $dictionarySetFieldRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
            $this->processCall($call);
        } catch (SdkError $e) {
            return new DictionarySetFieldError($e);
        } catch (Exception $e) {
            return new DictionarySetFieldError(new UnknownError($e->getMessage(), 0, $e));
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
            $this->logger->debug("dictionaryGetField $dictionaryName $field");
            $call = $this->grpcManager->client->DictionaryGet(
                $dictionaryGetFieldRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
            $dictionaryGetFieldResponse = $this->processCall($call);
        } catch (SdkError $e) {
            return new DictionaryGetFieldError($e);
        } catch (Exception $e) {
            return new DictionaryGetFieldError(new UnknownError($e->getMessage(), 0, $e));
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
            $this->logger->debug("dictionaryFetch $dictionaryName");
            $call = $this->grpcManager->client->DictionaryFetch(
                $dictionaryFetchRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
            $dictionaryFetchResponse = $this->processCall($call);
        } catch (SdkError $e) {
            return new DictionaryFetchError($e);
        } catch (Exception $e) {
            return new DictionaryFetchError(new UnknownError($e->getMessage(), 0, $e));
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
            $this->logger->debug("dictionarySetFields $dictionaryName $ttlMillis");
            $call = $this->grpcManager->client->DictionarySet(
                $dictionarySetFieldsRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
            $this->processCall($call);
        } catch (SdkError $e) {
            return new DictionarySetFieldsError($e);
        } catch (Exception $e) {
            return new DictionarySetFieldsError(new UnknownError($e->getMessage(), 0, $e));
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
            $this->logger->debug("dictionaryGetFields $dictionaryName");
            $call = $this->grpcManager->client->DictionaryGet(
                $dictionaryGetFieldsRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
            $dictionaryGetFieldsResponse = $this->processCall($call);
        } catch (SdkError $e) {
            return new DictionaryGetFieldsError($e);
        } catch (Exception $e) {
            return new DictionaryGetFieldsError(new UnknownError($e->getMessage(), 0, $e));
        }
        if ($dictionaryGetFieldsResponse->hasFound()) {
            return new DictionaryGetFieldsHit($dictionaryGetFieldsResponse, $fields);
        }
        return new DictionaryGetFieldsMiss();
    }

    public function dictionaryIncrement(
        string $cacheName,
        string $dictionaryName,
        string $field,
        int $amount = 1,
        ?CollectionTtl $ttl = null
    ): DictionaryIncrementResponse {
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
            $this->logger->debug(sprintf("dictionaryIncrement %s %s %u %u", $dictionaryName, $field, $amount, $ttlMillis));
            $call = $this->grpcManager->client->DictionaryIncrement(
                $dictionaryIncrementRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
            $response = $this->processCall($call);
        } catch (SdkError $e) {
            return new DictionaryIncrementError($e);
        } catch (Exception $e) {
            return new DictionaryIncrementError(new UnknownError($e->getMessage(), 0, $e));
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
            $this->logger->debug("dictionaryRemoveField $dictionaryName $field");
            $call = $this->grpcManager->client->DictionaryDelete(
                $dictionaryRemoveFieldRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
            $this->processCall($call);
        } catch (SdkError $e) {
            return new DictionaryRemoveFieldError($e);
        } catch (Exception $e) {
            return new DictionaryRemoveFieldError(new UnknownError($e->getMessage(), 0, $e));
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
            $this->logger->debug("dictionaryRemoveFields $dictionaryName");
            $call = $this->grpcManager->client->DictionaryDelete(
                $dictionaryRemoveFieldsRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
            $this->processCall($call);
        } catch (SdkError $e) {
            return new DictionaryRemoveFieldsError($e);
        } catch (Exception $e) {
            return new DictionaryRemoveFieldsError(new UnknownError($e->getMessage(), 0, $e));
        }
        return new DictionaryRemoveFieldsSuccess();
    }

    /**
     * @return ResponseFuture<SetAddElementResponse>
     */
    public function setAddElement(string $cacheName, string $setName, string $element, ?CollectionTtl $ttl = null): ResponseFuture
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
            $this->logger->debug(sprintf("setAddElement %s %s %u", $setName, $element, $ttlMillis));
            $call = $this->grpcManager->client->SetUnion(
                $setAddElementRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SetAddElementError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SetAddElementError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): SetAddElementResponse {
                try {
                    $this->processCall($call);

                    return new SetAddElementSuccess();
                } catch (SdkError $e) {
                    return new SetAddElementError($e);
                } catch (Exception $e) {
                    return new SetAddElementError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @param list<string> $elements
     * @return ResponseFuture<SetAddElementsResponse>
     */
    public function setAddElements(string $cacheName, string $setName, array $elements, ?CollectionTtl $ttl = null): ResponseFuture
    {
        try {
            $collectionTtl = $this->returnCollectionTtl($ttl);
            validateCacheName($cacheName);
            validateSetName($setName);
            validateElements($elements);
            $ttlMillis = $this->ttlToMillis($collectionTtl->getTtl());
            validateTtl($ttlMillis);
            $setAddElementsRequest = new _SetUnionRequest();
            $setAddElementsRequest->setSetName($setName);
            $setAddElementsRequest->setRefreshTtl($collectionTtl->getRefreshTtl());
            $setAddElementsRequest->setTtlMilliseconds($ttlMillis);
            $setAddElementsRequest->setElements($elements);
            $this->logger->debug(sprintf("setAddElements %s %u", $setName, $ttlMillis));
            $call = $this->grpcManager->client->SetUnion(
                $setAddElementsRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SetAddElementsError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SetAddElementsError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): SetAddElementsResponse {
                try {
                    $this->processCall($call);

                    return new SetAddElementsSuccess();
                } catch (SdkError $e) {
                    return new SetAddElementsError($e);
                } catch (Exception $e) {
                    return new SetAddElementsError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @param list<string> $elements
     * @return ResponseFuture<SetContainsElementsResponse>
     */
    public function setContainsElements(string $cacheName, string $setName, array $elements): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateSetName($setName);
            validateElements($elements);
            $setContainsElementsRequest = new _SetContainsRequest();
            $setContainsElementsRequest->setSetName($setName);
            $setContainsElementsRequest->setElements($elements);
            $this->logger->debug("setContainsElements $setName");
            $call = $this->grpcManager->client->SetContains(
                $setContainsElementsRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SetContainsElementsError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SetContainsElementsError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call, $elements): SetContainsElementsResponse {
                try {
                    $response = $this->processCall($call);

                    if ($response->hasFound()) {
                        return new SetContainsElementsHit($response, $elements);
                    }
                    return new SetContainsElementsMiss();
                } catch (SdkError $e) {
                    return new SetContainsElementsError($e);
                } catch (Exception $e) {
                    return new SetContainsElementsError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @return ResponseFuture<SetFetchResponse>
     */
    public function setFetch(string $cacheName, string $setName): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateSetName($setName);
            $setFetchRequest = new _SetFetchRequest();
            $setFetchRequest->setSetName($setName);
            $this->logger->debug("setFetch $setName");
            $call = $this->grpcManager->client->SetFetch(
                $setFetchRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SetFetchError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SetFetchError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): SetFetchResponse {
                try {
                    $response = $this->processCall($call);

                    if ($response->hasFound()) {
                        return new SetFetchHit($response);
                    }
                    return new SetFetchMiss();
                } catch (SdkError $e) {
                    return new SetFetchError($e);
                } catch (Exception $e) {
                    return new SetFetchError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @return ResponseFuture<SetLengthResponse>
     */
    public function setLength(string $cacheName, string $setName): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateSetName($setName);
            $setLengthRequest = new _SetLengthRequest();
            $setLengthRequest->setSetName($setName);
            $this->logger->debug("setLength $setName");
            $call = $this->grpcManager->client->SetLength(
                $setLengthRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SetLengthError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SetLengthError(new UnknownError($e->getMessage())));
        }

        return ResponseFuture::createPending(
            function () use ($call): SetLengthResponse {
                try {
                    $response = $this->processCall($call);

                    return new SetLengthSuccess($response);
                } catch (SdkError $e) {
                    return new SetLengthError($e);
                } catch (Exception $e) {
                    return new SetLengthError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @return ResponseFuture<SetRemoveElementResponse>
     */
    public function setRemoveElement(string $cacheName, string $setName, string $element): ResponseFuture
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
            $this->logger->debug("setRemoveElement $setName $element");
            $call = $this->grpcManager->client->SetDifference(
                $setRemoveElementRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SetRemoveElementError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SetRemoveElementError(new UnknownError($e->getMessage())));
        }

        return ResponseFuture::createPending(
            function () use ($call): SetRemoveElementResponse {
                try {
                    $this->processCall($call);

                    return new SetRemoveElementSuccess();
                } catch (SdkError $e) {
                    return new SetRemoveElementError($e);
                } catch (Exception $e) {
                    return new SetRemoveElementError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @return ResponseFuture<SortedSetPutElementResponse>
     */
    public function sortedSetPutElement(string $cacheName, string $sortedSetName, string $value, $score, ?CollectionTtl $ttl = null): ResponseFuture
    {
        try {
            $collectionTtl = $this->returnCollectionTtl($ttl);
            validateCacheName($cacheName);
            validateSortedSetName($sortedSetName);
            validateValueName($value);
            validateSortedSetScore($score);
            $ttlMillis = $this->ttlToMillis($collectionTtl->getTtl());
            validateTtl($ttlMillis);
            $element = new _SortedSetElement([
                'value' => $value,
                'score' => $score,
            ]);
            $sortedSetPutElementRequest = new _SortedSetPutRequest();
            $sortedSetPutElementRequest->setSetName($sortedSetName);
            $sortedSetPutElementRequest->setRefreshTtl($collectionTtl->getRefreshTtl());
            $sortedSetPutElementRequest->setTtlMilliseconds($ttlMillis);
            $sortedSetPutElementRequest->setElements([$element]);
            $this->logger->debug(sprintf("sortedSetPutElement %s %s %f %u", $sortedSetName, $value, $score, $ttlMillis));
            $call = $this->grpcManager->client->SortedSetPut(
                $sortedSetPutElementRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SortedSetPutElementError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SortedSetPutElementError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): SortedSetPutElementResponse {
                try {
                    $this->processCall($call);

                    return new SortedSetPutElementSuccess();
                } catch (SdkError $e) {
                    return new SortedSetPutElementError($e);
                } catch (Exception $e) {
                    return new SortedSetPutElementError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @return ResponseFuture<SortedSetPutElementsResponse>
     */
    public function sortedSetPutElements(string $cacheName, string $sortedSetName, array $elements, ?CollectionTtl $ttl = null): ResponseFuture
    {
        try {
            $collectionTtl = $this->returnCollectionTtl($ttl);
            validateCacheName($cacheName);
            validateSortedSetName($sortedSetName);
            $ttlMillis = $this->ttlToMillis($collectionTtl->getTtl());
            validateTtl($ttlMillis);
            validateSortedSetElements($elements);
            $sortedSetElements = array_map(function ($value, $score) {
                return new _SortedSetElement([
                    'value' => $value,
                    'score' => $score,
                ]);
            }, array_keys($elements), $elements);
            $sortedSetPutElementRequest = new _SortedSetPutRequest();
            $sortedSetPutElementRequest->setSetName($sortedSetName);
            $sortedSetPutElementRequest->setRefreshTtl($collectionTtl->getRefreshTtl());
            $sortedSetPutElementRequest->setTtlMilliseconds($ttlMillis);
            $sortedSetPutElementRequest->setElements($sortedSetElements);
            $this->logger->debug(sprintf("sortedSetPutElements %s %u", $sortedSetName, $ttlMillis));
            $call = $this->grpcManager->client->SortedSetPut(
                $sortedSetPutElementRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SortedSetPutElementsError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SortedSetPutElementsError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): SortedSetPutElementsResponse {
                try {
                    $this->processCall($call);

                    return new SortedSetPutElementsSuccess();
                } catch (SdkError $e) {
                    return new SortedSetPutElementsError($e);
                } catch (Exception $e) {
                    return new SortedSetPutElementsError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @return ResponseFuture<SortedSetLengthByScoreResponse>
     */
    public function sortedSetLengthByScore(string $cacheName, string $sortedSetName, $minScore = null, $maxScore = null, bool $inclusiveMin = true, bool $inclusiveMax = true): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateSortedSetName($sortedSetName);
            validateSortedSetScores($minScore, $maxScore);
            $sortedSetLengthByScoreRequest = new _SortedSetLengthByScoreRequest();
            $sortedSetLengthByScoreRequest->setSetName($sortedSetName);

            if (!is_null($minScore)) {
                if ($inclusiveMin) {
                    $sortedSetLengthByScoreRequest->setInclusiveMin($minScore);
                } else {
                    $sortedSetLengthByScoreRequest->setExclusiveMin($minScore);
                }
            } else {
                $sortedSetLengthByScoreRequest->setUnboundedMin(new _Unbounded());
            }
            if (!is_null($maxScore)) {
                if ($inclusiveMax) {
                    $sortedSetLengthByScoreRequest->setInclusiveMax($maxScore);
                } else {
                    $sortedSetLengthByScoreRequest->setExclusiveMax($maxScore);
                }
            } else {
                $sortedSetLengthByScoreRequest->setUnboundedMax(new _Unbounded());
            }

            $this->logger->debug(sprintf("sortedSetLengthByScore %s %f %f %u %u", $sortedSetName, $minScore, $maxScore, $inclusiveMin, $inclusiveMax));
            $call = $this->grpcManager->client->SortedSetLengthByScore(
                $sortedSetLengthByScoreRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SortedSetLengthByScoreError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SortedSetLengthByScoreError(new UnknownError($e->getMessage())));
        }

        return ResponseFuture::createPending(
            function () use ($call): SortedSetLengthByScoreResponse {
                try {
                    $response = $this->processCall($call);

                    if ($response->hasFound()) {
                        return new SortedSetLengthByScoreHit($response);
                    }
                    return new SortedSetLengthByScoreMiss();
                } catch (SdkError $e) {
                    return new SortedSetLengthByScoreError($e);
                } catch (Exception $e) {
                    return new SortedSetLengthByScoreError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    public function sortedSetIncrementScore(string $cacheName, string $sortedSetName, string $value, $amount, ?CollectionTtl $ttl): ResponseFuture
    {
        try {
            $collectionTtl = $this->returnCollectionTtl($ttl);
            validateCacheName($cacheName);
            validateSortedSetName($sortedSetName);
            validateValueName($value);
            validateSortedSetScore($amount);
            $ttlMillis = $this->ttlToMillis($collectionTtl->getTtl());
            validateTtl($ttlMillis);

            $sortedSetIncrementScoreRequest = new _SortedSetIncrementRequest();
            $sortedSetIncrementScoreRequest->setSetName($sortedSetName);
            $sortedSetIncrementScoreRequest->setValue($value);
            $sortedSetIncrementScoreRequest->setAmount($amount);
            $sortedSetIncrementScoreRequest->setRefreshTtl($collectionTtl->getRefreshTtl());
            $sortedSetIncrementScoreRequest->setTtlMilliseconds($ttlMillis);
            $this->logger->debug(sprintf("sortedSetIncrementScore %s %s %f %u", $sortedSetName, $value, $amount, $ttlMillis));
            $call = $this->grpcManager->client->SortedSetIncrement(
                $sortedSetIncrementScoreRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SortedSetIncrementScoreError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SortedSetIncrementScoreError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): SortedSetIncrementScoreResponse {
                try {
                    $response = $this->processCall($call);

                    return new SortedSetIncrementScoreSuccess($response->getScore());
                } catch (SdkError $e) {
                    return new SortedSetIncrementScoreError($e);
                } catch (Exception $e) {
                    return new SortedSetIncrementScoreError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @return ResponseFuture<SortedSetFetchResponse>
     */
    public function sortedSetFetchByRank(string $cacheName, string $sortedSetName, ?int $startRank = 0, ?int $endRank = null, int $order = SORT_ASC): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateSortedSetName($sortedSetName);
            validateSortedSetRanks($startRank, $endRank);
            validateSortedSetOrder($order);

            $sortedSetFetchRequest = new _SortedSetFetchRequest();
            $sortedSetFetchRequest->setSetName($sortedSetName);
            $sortedSetFetchRequest->setWithScores(true);

            $byIndex = new _SortedSetFetchRequest\_ByIndex();
            if (!is_null($startRank)) {
                $byIndex->setInclusiveStartIndex($startRank);
            } else {
                $byIndex->setUnboundedStart(new _Unbounded());
            }
            if (!is_null($endRank)) {
                $byIndex->setExclusiveEndIndex($endRank);
            } else {
                $byIndex->setUnboundedEnd(new _Unbounded());
            }
            $sortedSetFetchRequest->setByIndex($byIndex);

            if ($order == SORT_DESC) {
                $sortedSetFetchRequest->setOrder(_SortedSetFetchRequest\Order::DESCENDING);
            } else {
                $sortedSetFetchRequest->setOrder(_SortedSetFetchRequest\Order::ASCENDING);
            }
            $this->logger->debug(sprintf("sortedSetFetchByRank %s %u %u %u", $sortedSetName, $startRank, $endRank, $order));
            $call = $this->grpcManager->client->SortedSetFetch(
                $sortedSetFetchRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SortedSetFetchError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SortedSetFetchError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): SortedSetFetchResponse {
                try {
                    $response = $this->processCall($call);

                    if ($response->hasFound()) {
                        return new SortedSetFetchHit($response);
                    }
                    return new SortedSetFetchMiss();
                } catch (SdkError $e) {
                    return new SortedSetFetchError($e);
                } catch (Exception $e) {
                    return new SortedSetFetchError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @return ResponseFuture<SortedSetFetchResponse>
     */
    public function sortedSetFetchByScore(string $cacheName, string $sortedSetName, $minScore = null, $maxScore = null, bool $inclusiveMin = true, bool $inclusiveMax = true, int $order = SORT_ASC, ?int $offset = null, ?int $count = null): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateSortedSetName($sortedSetName);
            validateSortedSetScores($minScore, $maxScore);
            validateSortedSetOrder($order);

            $sortedSetFetchRequest = new _SortedSetFetchRequest();
            $sortedSetFetchRequest->setSetName($sortedSetName);
            $sortedSetFetchRequest->setWithScores(true);

            $byScore = new _SortedSetFetchRequest\_ByScore();

            if (is_null($minScore)) {
                $byScore->setUnboundedMin(new _Unbounded());
            } else {
                $byScore->setMinScore(new _SortedSetFetchRequest\_ByScore\_Score([
                    'score' => $minScore,
                    'exclusive' => !$inclusiveMin,
                ]));
            }
            if (is_null($maxScore)) {
                $byScore->setUnboundedMax(new _Unbounded());
            } else {
                $byScore->setMaxScore(new _SortedSetFetchRequest\_ByScore\_Score([
                    'score' => $maxScore,
                    'exclusive' => !$inclusiveMax,
                ]));
            }

            if (is_null($offset)) {
                $byScore->setOffset(0);
            } else {
                $byScore->setOffset($offset);
            }

            if (is_null($count)) {
                // a negative count returns all elements.
                $byScore->setCount(-1);
            } else {
                $byScore->setCount($count);
            }

            $sortedSetFetchRequest->setByScore($byScore);

            if ($order == SORT_DESC) {
                $sortedSetFetchRequest->setOrder(_SortedSetFetchRequest\Order::DESCENDING);
            } else {
                $sortedSetFetchRequest->setOrder(_SortedSetFetchRequest\Order::ASCENDING);
            }
            $this->logger->debug(sprintf("sortedSetFetchByScore %s %f %f %u %u %u %u %u", $sortedSetName, $minScore, $maxScore, $inclusiveMin, $inclusiveMax, $order, $offset, $count));
            $call = $this->grpcManager->client->SortedSetFetch(
                $sortedSetFetchRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SortedSetFetchError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SortedSetFetchError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): SortedSetFetchResponse {
                try {
                    $response = $this->processCall($call);

                    if ($response->hasFound()) {
                        return new SortedSetFetchHit($response);
                    }
                    return new SortedSetFetchMiss();
                } catch (SdkError $e) {
                    return new SortedSetFetchError($e);
                } catch (Exception $e) {
                    return new SortedSetFetchError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @return ResponseFuture<SortedSetRemoveElementResponse>
     */
    public function sortedSetRemoveElement(string $cacheName, string $sortedSetName, string $value): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateSortedSetName($sortedSetName);
            validateValueName($value);
            $sortedSetRemoveElementRequest = new _SortedSetRemoveRequest();
            $sortedSetRemoveElementRequest->setSetName($sortedSetName);
            $sortedSetRemoveElementRequest->setSome(new _SortedSetRemoveRequest\_Some());
            $sortedSetRemoveElementRequest->getSome()->setValues([$value]);

            $this->logger->debug("sortedSetRemoveElement $sortedSetName $value");
            $call = $this->grpcManager->client->SortedSetRemove(
                $sortedSetRemoveElementRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SortedSetRemoveElementError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SortedSetRemoveElementError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): SortedSetRemoveElementResponse {
                try {
                    $this->processCall($call);

                    return new SortedSetRemoveElementSuccess();
                } catch (SdkError $e) {
                    return new SortedSetRemoveElementError($e);
                } catch (Exception $e) {
                    return new SortedSetRemoveElementError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    public function sortedSetRemoveElements(string $cacheName, string $sortedSetName, array $values): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateSortedSetName($sortedSetName);
            validateSortedSetValues($values);
            $sortedSetRemoveElementsRequest = new _SortedSetRemoveRequest();
            $sortedSetRemoveElementsRequest->setSetName($sortedSetName);
            $sortedSetRemoveElementsRequest->setSome(new _SortedSetRemoveRequest\_Some());
            $sortedSetRemoveElementsRequest->getSome()->setValues($values);
            $this->logger->debug("sortedSetRemoveElements $sortedSetName");
            $call = $this->grpcManager->client->SortedSetRemove(
                $sortedSetRemoveElementsRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SortedSetRemoveElementsError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SortedSetRemoveElementsError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): SortedSetRemoveElementsResponse {
                try {
                    $this->processCall($call);

                    return new SortedSetRemoveElementsSuccess();
                } catch (SdkError $e) {
                    return new SortedSetRemoveElementsError($e);
                } catch (Exception $e) {
                    return new SortedSetRemoveElementsError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @return ResponseFuture<SortedSetGetScoreResponse>
     */
    public function sortedSetGetScore(string $cacheName, string $sortedSetName, string $value): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateSortedSetName($sortedSetName);
            validateValueName($value);
            $sortedSetGetScoreRequest = new _SortedSetGetScoreRequest();
            $sortedSetGetScoreRequest->setSetName($sortedSetName);
            $sortedSetGetScoreRequest->setValues([$value]);
            $this->logger->debug("sortedSetGetScore $sortedSetName $value");
            $call = $this->grpcManager->client->SortedSetGetScore(
                $sortedSetGetScoreRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SortedSetGetScoreError($value, $e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SortedSetGetScoreError($value, new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call, $value): SortedSetGetScoreResponse {
                try {
                    $response = $this->processCall($call);

                    if ($response->hasFound()) {
                        if ($response->getFound()->getElements()->count() == 0) {
                            return new SortedSetGetScoreError($value, new UnknownError("_SortedSetGetScoreResponseResponse contained no data but was found"));
                        } else {
                            $element = $response->getFound()->getElements()[0];
                            if ($element->getResult() == ECacheResult::Hit) {
                                return new SortedSetGetScoreHit($value, $element->getScore());
                            } else {
                                return new SortedSetGetScoreMiss($value);
                            }
                        }
                    } else {
                        return new SortedSetGetScoreMiss($value);
                    }
                } catch (SdkError $e) {
                    return new SortedSetGetScoreError($value, $e);
                } catch (Exception $e) {
                    return new SortedSetGetScoreError($value, new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @return ResponseFuture<SortedSetUnionStoreResponse>
     */
    public function sortedSetUnionStore(
        string $cacheName,
        string $destination,
        array $sources,
        ?int $aggregate = null,
        ?int $ttlSeconds = null
    ): ResponseFuture {
        try {
            // The number of source sets is currently limited to 2. I'm just adding this validation and
            // some validation of the contents of $sources here for now, but will move to _DataValidation
            // eventually.
            if (count($sources) > 2) {
                throw new InvalidArgumentError("The number of source sets is currently limited to 2");
            }
            validateCacheName($cacheName);
            validateSortedSetName($destination);
            $ttlMillis = $this->ttlToMillis($ttlSeconds);
            if ($aggregate === null) {
                $aggregate = SortedSetUnionStoreAggregateFunction::SUM;
            }
            $grpcSources = [];
            foreach ($sources as $source) {
                if (!array_key_exists('setName', $source) || !array_key_exists('weight', $source)) {
                    throw new InvalidArgumentError("Each source must have 'setName' and 'weight' keys");
                }
                validateSortedSetName($source['setName']);
                if (!is_numeric($source['weight'])) {
                    throw new InvalidArgumentError("Each source must have a 'weight' key with a float value");
                }
                if (!is_float($source['weight'])) {
                    $source['weight'] = (float)$source['weight'];
                }
                $grpcSource = new _Source();
                $grpcSource->setSetName($source['setName']);
                $grpcSource->setWeight($source['weight']);
                $grpcSources[] = $grpcSource;
            }

            $ttlMillis = $this->ttlToMillis($ttlMillis);
            validateTtl($ttlMillis);
            $sortedSetUnionStoreRequest = new _SortedSetUnionStoreRequest();
            $sortedSetUnionStoreRequest->setSetName($destination);
            $sortedSetUnionStoreRequest->setTtlMilliseconds($ttlMillis);
            $sortedSetUnionStoreRequest->setSources($grpcSources);
            $sortedSetUnionStoreRequest->setAggregate($aggregate);
            $this->logger->debug(sprintf("sortedSetUnionStore %s %u %u", $destination, $aggregate, $ttlSeconds));
            $call = $this->grpcManager->client->SortedSetUnionStore(
                $sortedSetUnionStoreRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SortedSetUnionStoreError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SortedSetUnionStoreError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): SortedSetUnionStoreResponse {
                try {
                    $response = $this->processCall($call);
                    return new SortedSetUnionStoreSuccess($response);
                } catch (SdkError $e) {
                    return new SortedSetUnionStoreError($e);
                } catch (Exception $e) {
                    return new SortedSetUnionStoreError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @return ResponseFuture<GetBatchResponse>
     */
    public function getBatch(string $cacheName, array $keys): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateKeys($keys);

            $getRequests = [];
            foreach ($keys as $key) {
                $getRequest = new _GetRequest();
                $getRequest->setCacheKey($key);
                $getRequests[] = $getRequest;
            }

            $getBatchRequest = new _GetBatchRequest();
            $getBatchRequest->setItems($getRequests);
            $this->logger->debug("getBatch $cacheName");
            $call = $this->grpcManager->client->GetBatch($getBatchRequest, ['cache' => [$cacheName]], ['timeout' => $this->timeout]);
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new GetBatchError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new GetBatchError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): GetBatchResponse {
                try {
                    $results = [];
                    $responses = $call->responses();
                    foreach ($responses as $response) {
                        if ($response->getResult() == ECacheResult::Hit) {
                            $value = new GetHit($response);
                        } elseif ($response->getResult() == ECacheResult::Miss) {
                            $value = new GetMiss();
                        } else {
                            $value = new GetError(new UnknownError(strval($response->getResult())));
                        }
                        $results[] = $value;
                    }
                    return new GetBatchSuccess($results);
                } catch (SdkError $e) {
                    return new GetBatchError($e);
                } catch (Exception $e) {
                    return new GetBatchError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    /**
     * @param int|float $ttlSeconds
     *
     * @return ResponseFuture<SetBatchResponse>
     */
    public function setBatch(string $cacheName, array $items, int $ttlSeconds = 0): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateKeys(array_keys($items));

            $ttlMillis = $this->ttlToMillis($ttlSeconds);
            $setRequests = [];
            foreach ($items as $key => $value) {
                $setRequest = new _SetRequest();
                $setRequest->setCacheKey($key);
                $setRequest->setCacheBody($value);
                $setRequest->setTtlMilliseconds($ttlMillis);
                $setRequests[] = $setRequest;
            }
            $setBatchRequest = new _SetBatchRequest();
            $setBatchRequest->setItems($setRequests);
            $this->logger->debug(sprintf("setBatch %s %u", $cacheName, $ttlSeconds));
            $call = $this->grpcManager->client->SetBatch($setBatchRequest, ['cache' => [$cacheName]], ['timeout' => $this->timeout]);
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new SetBatchError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new SetBatchError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): SetBatchResponse {
                try {
                    $results = [];
                    $responses = $call->responses();
                    foreach ($responses as $response) {
                        if ($response->getResult() == ECacheResult::Ok) {
                            $value = new SetSuccess();
                        } else {
                            $value = new SetError(new UnknownError(strval($response->getResult())));
                        }
                        $results[] = $value;
                    }

                    $status = $call->getStatus();
                    if ($status->code != 0) {
                        return new SetBatchError(new UnknownError($status->details));
                    }
                    return new SetBatchSuccess($results);
                } catch (SdkError $e) {
                    return new SetBatchError($e);
                } catch (Exception $e) {
                    return new SetBatchError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    public function itemGetTtl(string $cacheName, string $key): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateKeys([$key]);
            $itemGetTtlRequest = new _ItemGetTtlRequest();
            $itemGetTtlRequest->setCacheKey($key);
            $this->logger->debug("itemGetTtl $cacheName $key");
            $call = $this->grpcManager->client->ItemGetTtl(
                $itemGetTtlRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new ItemGetTtlError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new ItemGetTtlError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): ItemGetTtlResponse {
                try {
                    $response = $this->processCall($call);

                    if (!$response->hasFound()) {
                        return new ItemGetTtlMiss();
                    }
                    return new ItemGetTtlHit($response);
                } catch (SdkError $e) {
                    return new ItemGetTtlError($e);
                } catch (Exception $e) {
                    return new ItemGetTtlError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    public function updateTtl(string $cacheName, string $key, int $ttlMilliseconds): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateKeys([$key]);
            $updateTtlRequest = new _UpdateTtlRequest();
            $updateTtlRequest->setCacheKey($key);
            $updateTtlRequest->setOverwriteToMilliseconds($ttlMilliseconds);
            $this->logger->debug(sprintf("updateTtl %s %s %u", $cacheName, $key, $ttlMilliseconds));
            $call = $this->grpcManager->client->UpdateTtl(
                $updateTtlRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new UpdateTtlError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new UpdateTtlError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): UpdateTtlResponse {
                try {
                    $response = $this->processCall($call);

                    if ($response->hasMissing()) {
                        return new UpdateTtlMiss();
                    }
                    return new UpdateTtlSet();
                } catch (SdkError $e) {
                    return new UpdateTtlError($e);
                } catch (Exception $e) {
                    return new UpdateTtlError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    public function increaseTtl(string $cacheName, string $key, int $ttlMilliseconds): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateKeys([$key]);
            $increaseTtlRequest = new _UpdateTtlRequest();
            $increaseTtlRequest->setCacheKey($key);
            $increaseTtlRequest->setIncreaseToMilliseconds($ttlMilliseconds);
            $this->logger->debug(sprintf("increaseTtl %s %s %u", $cacheName, $key, $ttlMilliseconds));
            $call = $this->grpcManager->client->UpdateTtl(
                $increaseTtlRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new IncreaseTtlError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new IncreaseTtlError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): IncreaseTtlResponse {
                try {
                    $response = $this->processCall($call);

                    if ($response->hasMissing()) {
                        return new IncreaseTtlMiss();
                    } elseif ($response->hasNotSet()) {
                        return new IncreaseTtlNotSet();
                    }
                    return new IncreaseTtlSet();
                } catch (SdkError $e) {
                    return new IncreaseTtlError($e);
                } catch (Exception $e) {
                    return new IncreaseTtlError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    public function decreaseTtl(string $cacheName, string $key, int $ttlMilliseconds): ResponseFuture
    {
        try {
            validateCacheName($cacheName);
            validateKeys([$key]);
            $decreaseTtlRequest = new _UpdateTtlRequest();
            $decreaseTtlRequest->setCacheKey($key);
            $decreaseTtlRequest->setDecreaseToMilliseconds($ttlMilliseconds);
            $this->logger->debug(sprintf("decreaseTtl %s %s %u", $cacheName, $key, $ttlMilliseconds));
            $call = $this->grpcManager->client->UpdateTtl(
                $decreaseTtlRequest,
                ["cache" => [$cacheName]],
                ["timeout" => $this->timeout],
            );
        } catch (SdkError $e) {
            return ResponseFuture::createResolved(new DecreaseTtlError($e));
        } catch (Exception $e) {
            return ResponseFuture::createResolved(new DecreaseTtlError(new UnknownError($e->getMessage(), 0, $e)));
        }

        return ResponseFuture::createPending(
            function () use ($call): DecreaseTtlResponse {
                try {
                    $response = $this->processCall($call);

                    if ($response->hasMissing()) {
                        return new DecreaseTtlMiss();
                    } elseif ($response->hasNotSet()) {
                        return new DecreaseTtlNotSet();
                    }
                    return new DecreaseTtlSet();
                } catch (SdkError $e) {
                    return new DecreaseTtlError($e);
                } catch (Exception $e) {
                    return new DecreaseTtlError(new UnknownError($e->getMessage(), 0, $e));
                }
            }
        );
    }

    public function close(): void
    {
        $this->grpcManager->close();
    }
}
