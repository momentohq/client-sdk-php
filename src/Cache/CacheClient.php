<?php
declare(strict_types=1);

namespace Momento\Cache;

use Momento\Auth\ICredentialProvider;
use Momento\Cache\CacheOperationTypes\CacheDeleteResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryFetchResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryGetFieldResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryGetFieldsResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryIncrementResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryRemoveFieldResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionaryRemoveFieldsResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionarySetFieldResponse;
use Momento\Cache\CacheOperationTypes\CacheDictionarySetFieldsResponse;
use Momento\Cache\CacheOperationTypes\CacheGetResponse;
use Momento\Cache\CacheOperationTypes\CacheKeyExistsResponse;
use Momento\Cache\CacheOperationTypes\CacheKeysExistResponse;
use Momento\Cache\CacheOperationTypes\CacheListFetchResponse;
use Momento\Cache\CacheOperationTypes\CacheListLengthResponse;
use Momento\Cache\CacheOperationTypes\CacheListPopBackResponse;
use Momento\Cache\CacheOperationTypes\CacheListPopFrontResponse;
use Momento\Cache\CacheOperationTypes\CacheListPushBackResponse;
use Momento\Cache\CacheOperationTypes\CacheListPushFrontResponse;
use Momento\Cache\CacheOperationTypes\CacheListRemoveValueResponse;
use Momento\Cache\CacheOperationTypes\CacheSetAddElementResponse;
use Momento\Cache\CacheOperationTypes\CacheSetFetchResponse;
use Momento\Cache\CacheOperationTypes\CacheSetIfNotExistsResponse;
use Momento\Cache\CacheOperationTypes\CacheSetRemoveElementResponse;
use Momento\Cache\CacheOperationTypes\CacheSetResponse;
use Momento\Cache\CacheOperationTypes\CreateCacheResponse;
use Momento\Cache\CacheOperationTypes\DeleteCacheResponse;
use Momento\Cache\CacheOperationTypes\ListCachesResponse;
use Momento\Cache\Internal\ScsControlClient;
use Momento\Cache\Internal\ScsDataClient;
use Momento\Config\IConfiguration;
use Momento\Logging\ILoggerFactory;
use Momento\Requests\CollectionTtl;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerInterface;

/**
 * Client to perform operations against Momento Serverless Cache.
 */
class CacheClient implements LoggerAwareInterface
{

    protected IConfiguration $configuration;
    protected ILoggerFactory $loggerFactory;
    protected LoggerInterface $logger;
    private ScsControlClient $controlClient;
    private ScsDataClient $dataClient;

    /**
     * @param IConfiguration $configuration Configuration to use for transport.
     * @param ICredentialProvider $authProvider Momento authentication provider.
     * @param int $defaultTtlSeconds Default time to live for the item in cache in seconds.
     */
    public function __construct(
        IConfiguration $configuration, ICredentialProvider $authProvider, int $defaultTtlSeconds
    )
    {
        $this->configuration = $configuration;
        $this->loggerFactory = $configuration->getLoggerFactory();
        $this->setLogger($this->loggerFactory->getLogger(get_class($this)));
        $this->controlClient = new ScsControlClient($this->loggerFactory, $authProvider);
        $this->dataClient = new ScsDataClient(
            $this->configuration,
            $authProvider,
            $defaultTtlSeconds
        );
    }

    /**
     * Assigns a LoggerInterface logging object to the client.
     *
     * @param LoggerInterface $logger Object to use for logging
     * @return void
     */
    public function setLogger(LoggerInterface $logger): void
    {
        $this->logger = $logger;
    }

    /**
     * Creates a cache if it doesn't exist.
     *
     * @param string $cacheName Name of the cache to create
     * @return CreateCacheResponse Represents the result of the create cache operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * CreateCacheResponseSuccess<br>
     * * CreateCacheResponseError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function createCache(string $cacheName): CreateCacheResponse
    {
        return $this->controlClient->createCache($cacheName);
    }

    /**
     * List existing caches.
     *
     * @param string|null $nextToken
     * @return ListCachesResponse Represents the result of the list caches operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * ListCachesResponseSuccess<br>
     * * ListCachesResponseError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function listCaches(?string $nextToken = null): ListCachesResponse
    {
        return $this->controlClient->listCaches($nextToken);
    }

    /**
     * Delete a cache.
     *
     * @param string $cacheName Name of the cache to delete.
     * @return DeleteCacheResponse Represents the result of the delete cache operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * DeleteCacheResponseSuccess<br>
     * * DeleteCacheResponseError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function deleteCache(string $cacheName): DeleteCacheResponse
    {
        return $this->controlClient->deleteCache($cacheName);
    }

    /**
     * Set the value in cache with a given time to live (TTL) seconds.
     *
     * @param string $cacheName Name of the cache in which to set the value.
     * @param string $key The key to set.
     * @param string $value The value to be stored.
     * @param int $ttlSeconds TTL for the item in cache. This TTL takes precedence over the TTL used when initializing a cache client.
     *   Defaults to client TTL. If specified must be strictly positive.
     * @return CacheSetResponse Represents the result of the set operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * CacheSetResponseSuccess<br>
     * * CacheSetResponseError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function set(string $cacheName, string $key, string $value, int $ttlSeconds = 0): CacheSetResponse
    {
        return $this->dataClient->set($cacheName, $key, $value, $ttlSeconds);
    }

    /**
     * Gets the cache value stored for a given key.
     *
     * @param string $cacheName Name of the cache to perform the lookup in.
     * @param string $key The key to look up.
     * @return CacheGetResponse Represents the result of the get operation and stores the retrieved value. This
     * result is resolved to a type-safe object of one of the following types:<br>
     * * CacheGetResponseHit<br>
     * * CacheGetResponseMiss<br>
     * * CacheSetResponseError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($hit = $response->asHit()) {<br>
     * &nbsp;&nbsp;$value = $hit->valueString();<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error response<br>
     * }</code>
     */
    public function get(string $cacheName, string $key): CacheGetResponse
    {
        return $this->dataClient->get($cacheName, $key);
    }

    /**
     * Associates the given key with the given value. If a value for the key is
     * already present it is not replaced with the new value.
     *
     * @param string $cacheName Name of the cache to store the key and value in
     * @param string $key The key to set.
     * @param string $value The value to be stored.
     * @param int $ttlSeconds TTL for the item in cache. This TTL takes precedence over the TTL used when initializing a cache client.
     *   Defaults to client TTL. If specified must be strictly positive.
     * @return CacheSetIfNotExistsResponse Represents the result of the setIfNotExists operation. This
     * result is resolved to a type-safe object of one of the following types:<br>
     * * CacheSetIfNotExistsResponseStored<br>
     * * CacheSetIfNotExistsResponseNotStored<br>
     * * CacheSetIfNotExistsResponseError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($hit = $response->asStored()) {<br>
     * &nbsp;&nbsp;$value = $hit->valueString();<br>
     * } elseif ($error = $response->asNotStored()) {<br>
     * &nbsp;&nbsp;// key was already set in the cache<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error response<br>
     * }</code>
     */
    public function setIfNotExists(string $cacheName, string $key, string $value, int $ttlSeconds = 0): CacheSetIfNotExistsResponse
    {
        return $this->dataClient->setIfNotExists($cacheName, $key, $value, $ttlSeconds);
    }

    /**
     * Removes the key from the cache.
     *
     * @param string $cacheName Name of the cache from which to remove the key
     * @param string $key The key to remove
     * @return CacheDeleteResponse Represents the result of the delete operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * CacheDeleteResponseSuccess<br>
     * * CacheDeleteResponseError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function delete(string $cacheName, string $key): CacheDeleteResponse
    {
        return $this->dataClient->delete($cacheName, $key);
    }

    /**
     * Check to see if multiple keys exist in the cache.
     *
     * @param string $cacheName Name of the cache in which to look for keys
     * @param array $keys List of keys to check
     * @return CacheKeysExistResponse Represents the result of the keys exist operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * CacheKeysExistResponseSuccess<br>
     * * CacheKeysExistResponseError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * } elseif ($success = $response->asSuccess()) {<br>
     * &nbsp;&nbsp;// get a list of booleans representing the existence of the key at that index<br>
     * &nbsp;&nbsp;$asList = $success->exists();<br>
     * &nbsp;&nbsp;// get a dict with the key names as keys and boolean values<br>
     * &nbsp;&nbsp;$asDict = $success->existsDictionary();<br>
     * }</code>
     */
    public function keysExist(string $cacheName, array $keys): CacheKeysExistResponse
    {
        return $this->dataClient->keysExist($cacheName, $keys);
    }

    /**
     * Check to see if a key exists in the cache.
     *
     * @param string $cacheName Name of the cache in which to look for the key
     * @param string $key The key to check
     * @return CacheKeyExistsResponse Represents the result of the keys exist operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * CacheKeyExistsResponseSuccess<br>
     * * CacheKeyExistsResponseError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * } elseif ($success = $response->asSuccess()) {<br>
     * &nbsp;&nbsp;$keyIsInCache = $success->exists();<br>
     * }</code>
     */
    public function keyExists(string $cacheName, string $key): CacheKeyExistsResponse
    {
        return $this->dataClient->keyExists($cacheName, $key);
    }

    /**
     * Fetch the entire list from the cache.
     *
     * @param string $cacheName Name of the cache to perform the lookup in.
     * @param string $listName The list to fetch.
     * @return CacheListFetchResponse Represents the result of the list fetch operation and the associated list.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * CacheListFetchResponseHit<br>
     * * CacheListFetchResponseMiss<br>
     * * CacheListFetchResponseError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($hit = $response->asHit()) {<br>
     * &nbsp;&nbsp;$theList = $hit->valuesArray();<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function listFetch(string $cacheName, string $listName): CacheListFetchResponse
    {
        return $this->dataClient->listFetch($cacheName, $listName);
    }

    /**
     * Push a value to the beginning of a list.
     *
     * @param string $cacheName Name of the cache that contains the list.
     * @param string $listName The list to push the value on.
     * @param string $value The value to push to the front of the list.
     * @param int|null $truncateBackToSize Ensure the list does not exceed this length. Remove excess from the end of the list. Must be a positive number.
     * @param CollectionTtl|null $ttl Specifies if collection TTL is refreshed when updated and the TTL value to which it is set.
     * @return CacheListPushFrontResponse Represents the result of the operation and the length of the list after the push.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * CacheListPushFrontResponseSuccess<br>
     * * CacheListPushFrontResponseError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($success = $response->asSuccess()) {<br>
     * &nbsp;&nbsp;$listLength = $success->listLength();<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function listPushFront(
        string $cacheName, string $listName, string $value, ?int $truncateBackToSize = null, ?CollectionTtl $ttl = null
    ): CacheListPushFrontResponse
    {
        return $this->dataClient->listPushFront($cacheName, $listName, $value, $truncateBackToSize, $ttl);
    }

    /**
     * Push a value to the end of a list.
     *
     * @param string $cacheName Name of the cache that contains the list.
     * @param string $listName The list to push the value on.
     * @param string $value The value to push to the front of the list.
     * @param int|null $truncateFrontToSize Ensure the list does not exceed this length. Remove excess from the front of the list. Must be a positive number.
     * @param CollectionTtl|null $ttl Specifies if collection TTL is refreshed when updated and the TTL value to which it is set.
     * @return CacheListPushBackResponse Represents the result of the operation and the length of the list after the push.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * CacheListPushBackResponseSuccess<br>
     * * CacheListPushBackResponseError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($success = $response->asSuccess()) {<br>
     * &nbsp;&nbsp;$listLength = $success->listLength();<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function listPushBack(
        string $cacheName, string $listName, string $value, ?int $truncateFrontToSize = null, ?CollectionTtl $ttl = null
    ): CacheListPushBackResponse
    {
        return $this->dataClient->listPushBack($cacheName, $listName, $value, $truncateFrontToSize, $ttl);
    }

    /**
     * Pop a value from the beginning of a list.
     *
     * @param string $cacheName Name of the cache that contains the list.
     * @param string $listName The list to pop the value from.
     * @return CacheListPopFrontResponse Represents the result of the operation and the popped value.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * CacheListPopFrontResponseHit<br>
     * * CacheListPopFrontResponseMiss<br>
     * * CacheListPushFrontResponseError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($hit = $response->asHit()) {<br>
     * &nbsp;&nbsp;$poppedValue = $hit->valueString();<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function listPopFront(string $cacheName, string $listName): CacheListPopFrontResponse
    {
        return $this->dataClient->listPopFront($cacheName, $listName);
    }

    /**
     * Pop a value from the back of a list.
     *
     * @param string $cacheName Name of the cache that contains the list.
     * @param string $listName The list to pop the value from.
     * @return CacheListPopBackResponse Represents the result of the operation and the popped value.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * CacheListPopBackResponseHit<br>
     * * CacheListPopBackResponseMiss<br>
     * * CacheListPushBackResponseError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($hit = $response->asHit()) {<br>
     * &nbsp;&nbsp;$poppedValue = $hit->valueString();<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function listPopBack(string $cacheName, string $listName): CacheListPopBackResponse
    {
        return $this->dataClient->listPopBack($cacheName, $listName);
    }

    /**
     * Remove all elements from a list that are equal to a particular value.
     *
     * @param string $cacheName Name of the cache that contains the list.
     * @param string $listName The list from which to remove the matching elements.
     * @param string $value The value to completely remove from the list.
     * @return CacheListRemoveValueResponse Represents the result of the list remove value operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * CacheListRemoveValueResponseSuccess<br>
     * * CacheListRemoveValueResponseError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function listRemoveValue(string $cacheName, string $listName, string $value): CacheListRemoveValueResponse
    {
        return $this->dataClient->listRemoveValue($cacheName, $listName, $value);
    }

    /**
     * Calculates the length of the list in the cache.
     *
     * @param string $cacheName Name of the cache that contains the list.
     * @param string $listName The list to calculate the length.
     * @return CacheListLengthResponse Represents the result of the list length operation and contains the list length.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * CacheListLengthResponseSuccess<br>
     * * CacheListLengthResponseError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($hit = $response->asHit()) {<br>
     * &nbsp;&nbsp;$theLength = $hit->length();<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function listLength(string $cacheName, string $listName): CacheListLengthResponse
    {
        return $this->dataClient->listLength($cacheName, $listName);
    }

    /**
     * Set a dictionary field to a value.
     *
     * @param string $cacheName Name of the cache that contains the dictionary.
     * @param string $dictionaryName The dictionary to set the field in. Will be created if it doesn't exist.
     * @param string $field The field in the dictionary to set.
     * @param string $value The value to be stored.
     * @param CollectionTtl|null $ttl TTL for the dictionary in cache. This TTL takes precedence over the TTL used when initializing a cache client. Defaults to client TTL.
     * @return CacheDictionarySetFieldResponse Represents the result of the dictionary set field operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * CacheDictionarySetFieldResponseSuccess<br>
     * * CacheDictionarySetFieldResponseError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function dictionarySetField(string $cacheName, string $dictionaryName, string $field, string $value, ?CollectionTtl $ttl = null): CacheDictionarySetFieldResponse
    {
        return $this->dataClient->dictionarySetField($cacheName, $dictionaryName, $field, $value, $ttl);
    }

    /**
     * Get the cache value stored for the given dictionary and field.
     *
     * @param string $cacheName Name of the cache that contains the dictionary.
     * @param string $dictionaryName The dictionary to get the field from.
     * @param string $field The field in the dictionary to get.
     * @return CacheDictionaryGetFieldResponse Represents the result of the get field operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * CacheDictionaryGetFieldResponseHit<br>
     * * CacheDictionaryGetFieldResponseMiss<br>
     * * CacheDictionaryGetFieldResponseError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($hit = $response->asHit()) {<br>
     * &nbsp;&nbsp;$theValue = $hit->valueString();<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function dictionaryGetField(string $cacheName, string $dictionaryName, string $field): CacheDictionaryGetFieldResponse
    {
        return $this->dataClient->dictionaryGetField($cacheName, $dictionaryName, $field);
    }

    /**
     * Fetch an entire dictionary from the cache.
     *
     * @param string $cacheName Name of the cache that contains the dictionary.
     * @param string $dictionaryName The dictionary to fetch.
     * @return CacheDictionaryFetchResponse Represents the result of the fetch operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * CacheDictionaryFetchResponseHit<br>
     * * CacheDictionaryFetchResponseMiss<br>
     * * CacheDictionaryFetchResponseError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($hit = $response->asHit()) {<br>
     * &nbsp;&nbsp;$theValue = $hit->valuesDictionary();<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function dictionaryFetch(string $cacheName, string $dictionaryName): CacheDictionaryFetchResponse
    {
        return $this->dataClient->dictionaryFetch($cacheName, $dictionaryName);
    }

    /**
     * Set several dictionary field-value pairs in the cache.
     *
     * @param string $cacheName Name of the cache that contains the dictionary.
     * @param string $dictionaryName The dictionary to set the field in. Will be created if it doesn't exist.
     * @param array $items The field-value pairs o be stored.
     * @param CollectionTtl|null $ttl TTL for the dictionary in cache. This TTL takes precedence over the TTL used when initializing a cache client. Defaults to client TTL.
     * @return CacheDictionarySetFieldsResponse Represents the result of the dictionary set field operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * CacheDictionarySetFieldsResponseSuccess<br>
     * * CacheDictionarySetFieldsResponseError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function dictionarySetFields(string $cacheName, string $dictionaryName, array $items, ?CollectionTtl $ttl = null): CacheDictionarySetFieldsResponse
    {
        return $this->dataClient->dictionarySetFields($cacheName, $dictionaryName, $items, $ttl);
    }

    /**
     * Get several dictionary values from the cache.
     *
     * @param string $cacheName Name of the cache that contains the dictionary.
     * @param string $dictionaryName The dictionary to get the fields from.
     * @param array $fields The fields in the dictionary to lookup.
     * @return CacheDictionaryGetFieldsResponse Represents the result of the get fields operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * CacheDictionaryGetFieldsResponseHit<br>
     * * CacheDictionaryGetFieldsResponseMiss<br>
     * * CacheDictionaryGetFieldsResponseError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($hit = $response->asHit()) {<br>
     * &nbsp;&nbsp;// get a list of responses corresponding to the list of requested values<br>
     * &nbsp;&nbsp;// each item in the list is an instance of one of the following:<br>
     * &nbsp;&nbsp;// - CacheDictionaryGetFieldResponseHit<br>
     * &nbsp;&nbsp;// - CacheDictionaryGetFieldResponseMiss<br>
     * &nbsp;&nbsp;// - CacheDictionaryGetFieldResponseError<br>
     * &nbsp;&nbsp;$responseTypes = $hit->responses();
     * &nbsp;&nbsp;// get a dictionary of responses mapping requested field name keys to their values<br>
     * &nbsp;&nbsp;// fields that were not found in the cache dictionary are omitted<br>
     * &nbsp;&nbsp;$valuesDict = $hit->valuesDictionary();
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function dictionaryGetFields(string $cacheName, string $dictionaryName, array $fields): CacheDictionaryGetFieldsResponse
    {
        return $this->dataClient->dictionaryGetFields($cacheName, $dictionaryName, $fields);
    }

    /**
     * Add an integer quantity to a dictionary value.
     *
     * Incrementing the value of a missing field sets the value to the supplied amount.
     * Incrementing a field that isn't the representation of an integer will fail with a FailedPrecondition error.
     *
     * @param string $cacheName Name of the cache that contains the dictionary.
     * @param string $dictionaryName The dictionary to increment the field in.
     * @param string $field The field to increment.
     * @param int $amount The quantity, negative, positive, or zero, to increment the field by. Defaults to 1.
     * @param CollectionTtl|null $ttl TTL for the dictionary in cache. This TTL takes precedence over the TTL used when initializing a cache client. Defaults to client TTL.
     * @return CacheDictionaryIncrementResponse Represents the result of the dictionary increment operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * CacheDictionaryIncrementResponseSuccess<br>
     * * CacheDictionaryIncrementResponseError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($success = $response->asSuccess()) {<br>
     * &nbsp;&nbsp;$newValue = $success->valueInt();
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function dictionaryIncrement(
        string $cacheName, string $dictionaryName, string $field, int $amount = 1, ?CollectionTtl $ttl = null
    ): CacheDictionaryIncrementResponse
    {
        return $this->dataClient->dictionaryIncrement($cacheName, $dictionaryName, $field, $amount, $ttl);
    }

    /**
     * Remove a field from a dictionary.
     *
     * @param string $cacheName Name of the cache that contains the dictionary.
     * @param string $dictionaryName The dictionary from which to remove the field.
     * @param string $field The field to remove.
     * @return CacheDictionaryRemoveFieldResponse Represents the result of the dictionary remove field operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * CacheDictionaryRemoveFieldResponseSuccess<br>
     * * CacheDictionaryRemoveFieldResponseError<br>
     * if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function dictionaryRemoveField(string $cacheName, string $dictionaryName, string $field): CacheDictionaryRemoveFieldResponse
    {
        return $this->dataClient->dictionaryRemoveField($cacheName, $dictionaryName, $field);
    }

    /**
     * Remove multiple fields from a dictionary.
     *
     * @param string $cacheName Name of the cache that contains the dictionary.
     * @param string $dictionaryName The dictionary from which to remove the fields.
     * @param array $fields The fields to remove.
     * @return CacheDictionaryRemoveFieldsResponse Represents the result of the dictionary remove fields operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * CacheDictionaryRemoveFieldsResponseSuccess<br>
     * * CacheDictionaryRemoveFieldsResponseError<br>
     * if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function dictionaryRemoveFields(string $cacheName, string $dictionaryName, array $fields): CacheDictionaryRemoveFieldsResponse
    {
        return $this->dataClient->dictionaryRemoveFields($cacheName, $dictionaryName, $fields);
    }

    /**
     * Add an element to a set.
     *
     * @param string $cacheName Name of the cache that contains the set.
     * @param string $setName The set to add the element to.
     * @param string $element The element to add.
     * @param CollectionTtl|null $ttl TTL for the dictionary in cache. This TTL takes precedence over the TTL used when initializing a cache client. Defaults to client TTL.
     * @return CacheSetAddElementResponse Represents the result of the set add element operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * CacheSetAddElementResponseSuccess<br>
     * * CacheSetAddElementResponseError<br>
     * if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function setAddElement(string $cacheName, string $setName, string $element, ?CollectionTtl $ttl = null): CacheSetAddElementResponse
    {
        return $this->dataClient->setAddElement($cacheName, $setName, $element, $ttl);
    }

    /**
     * Fetch an entire set from the cache.
     *
     * @param string $cacheName Name of the cache that contains the set.
     * @param string $setName The set to fetch.
     * @return CacheSetFetchResponse Represents the result of the set fetch operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * CacheSetFetchResponseHit<br>
     * * CacheSetFetchResponseMiss<br>
     * * CacheSetFetchResponseError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($hit = $response->asHit()) {<br>
     * &nbsp;&nbsp;$theSet = $response->valueArray();<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function setFetch(string $cacheName, string $setName): CacheSetFetchResponse
    {
        return $this->dataClient->setFetch($cacheName, $setName);
    }

    /**
     * Remove an element from a set.
     *
     * @param string $cacheName Name of the cache that contains the set.
     * @param string $setName The set from which to remove the element.
     * @param string $element The element to remove.
     * @return CacheSetRemoveElementResponse Represents the result of the set remove element operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * CacheSetRemoveElementResponseSuccess<br>
     * * CacheSetRemoveElementResponseError<br>
     * if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function setRemoveElement(string $cacheName, string $setName, string $element): CacheSetRemoveElementResponse
    {
        return $this->dataClient->setRemoveElement($cacheName, $setName, $element);
    }
}
