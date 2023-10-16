<?php
declare(strict_types=1);

namespace Momento\Cache;

use Momento\Auth\ICredentialProvider;
use Momento\Cache\CacheOperationTypes\DeleteResponse;
use Momento\Cache\CacheOperationTypes\DictionaryFetchResponse;
use Momento\Cache\CacheOperationTypes\DictionaryGetFieldResponse;
use Momento\Cache\CacheOperationTypes\DictionaryGetFieldsResponse;
use Momento\Cache\CacheOperationTypes\DictionaryIncrementResponse;
use Momento\Cache\CacheOperationTypes\DictionaryRemoveFieldResponse;
use Momento\Cache\CacheOperationTypes\DictionaryRemoveFieldsResponse;
use Momento\Cache\CacheOperationTypes\DictionarySetFieldResponse;
use Momento\Cache\CacheOperationTypes\DictionarySetFieldsResponse;
use Momento\Cache\CacheOperationTypes\FlushCacheResponse;
use Momento\Cache\CacheOperationTypes\ResponseFuture;
use Momento\Cache\CacheOperationTypes\GetResponse;
use Momento\Cache\CacheOperationTypes\IncrementResponse;
use Momento\Cache\CacheOperationTypes\KeyExistsResponse;
use Momento\Cache\CacheOperationTypes\KeysExistResponse;
use Momento\Cache\CacheOperationTypes\ListFetchResponse;
use Momento\Cache\CacheOperationTypes\ListLengthResponse;
use Momento\Cache\CacheOperationTypes\ListPopBackResponse;
use Momento\Cache\CacheOperationTypes\ListPopFrontResponse;
use Momento\Cache\CacheOperationTypes\ListPushBackResponse;
use Momento\Cache\CacheOperationTypes\ListPushFrontResponse;
use Momento\Cache\CacheOperationTypes\ListRemoveValueResponse;
use Momento\Cache\CacheOperationTypes\SetAddElementResponse;
use Momento\Cache\CacheOperationTypes\SetAddElementsResponse;
use Momento\Cache\CacheOperationTypes\SetFetchResponse;
use Momento\Cache\CacheOperationTypes\SetIfNotExistsResponse;
use Momento\Cache\CacheOperationTypes\SetLengthResponse;
use Momento\Cache\CacheOperationTypes\SetRemoveElementResponse;
use Momento\Cache\CacheOperationTypes\SetResponse;
use Momento\Cache\CacheOperationTypes\CreateCacheResponse;
use Momento\Cache\CacheOperationTypes\DeleteCacheResponse;
use Momento\Cache\CacheOperationTypes\ListCachesResponse;
use Momento\Cache\Errors\InvalidArgumentError;
use Momento\Cache\Internal\IdleDataClientWrapper;
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

    /**
     * @var IdleDataClientWrapper[]
     */
    private array $dataClients;
    private int $nextDataClientIndex = 0;

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
        $dataClientFactory = new \stdClass();
        $dataClientFactory->callback = function() use ($authProvider, $defaultTtlSeconds) {
            return new ScsDataClient(
                $this->configuration,
                $authProvider,
                $defaultTtlSeconds
            );
        };
        $this->dataClients = [];

        $numGrpcChannels = $configuration->getTransportStrategy()->getGrpcConfig()->getNumGrpcChannels();
        $forceNewChannels = $configuration->getTransportStrategy()->getGrpcConfig()->getForceNewChannel();
        if (($numGrpcChannels > 1) && (! $forceNewChannels)) {
            throw new InvalidArgumentError("When setting NumGrpcChannels > 1, you must also set ForceNewChannel to true, or else the gRPC library will re-use the same channel.");
        }
        for ($i = 0; $i < $numGrpcChannels; $i++) {
            array_push($this->dataClients, new IdleDataClientWrapper($dataClientFactory, $this->configuration));
        }
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
     * * CreateCacheSuccess<br>
     * * CreateCacheError<br>
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
     * * ListCachesSuccess<br>
     * * ListCachesError<br>
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
     * * DeleteCacheSuccess<br>
     * * DeleteCacheError<br>
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
     * Flush a cache.
     *
     * @param string $cacheName Name of the cache to flush.
     * @return FlushCacheResponse Represents the result of the flush cache operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * FlushCacheSuccess<br>
     * * FlushCacheError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function flushCache(string $cacheName): FlushCacheResponse
    {
        return $this->controlClient->flushCache($cacheName);
    }

    /**
     * Set the value in cache with a given time to live (TTL) seconds.
     *
     * @param string $cacheName Name of the cache in which to set the value.
     * @param string $key The key to set.
     * @param string $value The value to be stored.
     * @param int $ttlSeconds TTL for the item in cache. This TTL takes precedence over the TTL used when initializing a cache client.
     *   Defaults to client TTL. If specified must be strictly positive.
     * @return ResponseFuture<SetResponse> A waitable future which will provide
     * the result of the set operation upon a blocking call to wait.
     * <code>$response = $responseFuture->wait();
     * }</code>
     * The response represents the result of the set operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * SetSuccess<br>
     * * SetError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     * If inspection of the response is not required, one need not call wait as
     * we implicitly wait for completion of the request on destruction of the
     * response future.
     */
    public function setAsync(string $cacheName, string $key, string $value, int $ttlSeconds = 0): ResponseFuture
    {
        return $this->getNextDataClient()->set($cacheName, $key, $value, $ttlSeconds);
    }

    /**
     * Set the value in cache with a given time to live (TTL) seconds.
     *
     * @param string $cacheName Name of the cache in which to set the value.
     * @param string $key The key to set.
     * @param string $value The value to be stored.
     * @param int $ttlSeconds TTL for the item in cache. This TTL takes precedence over the TTL used when initializing a cache client.
     *   Defaults to client TTL. If specified must be strictly positive.
     * @return SetResponse Represents the result of the set operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * SetSuccess<br>
     * * SetError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function set(string $cacheName, string $key, string $value, int $ttlSeconds = 0): SetResponse
    {
        return $this->setAsync($cacheName, $key, $value, $ttlSeconds)->wait();
    }

    /**
     * Gets the cache value stored for a given key.
     *
     * @param string $cacheName Name of the cache to perform the lookup in.
     * @param string $key The key to look up.
     * @return ResponseFuture<GetResponse> A waitable future which will provide
     * the result of the set operation upon a blocking call to wait.
     * <code>$response = $responseFuture->wait();
     * }</code>
     * The response represents the result of the get operation and stores the
     * retrieved value. This result is resolved to a type-safe object of one of
     * the following types:<br>
     * * GetHit<br>
     * * GetMiss<br>
     * * GetError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($hit = $response->asHit()) {<br>
     * &nbsp;&nbsp;$value = $hit->valueString();<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error response<br>
     * }</code>
     * If inspection of the response is not required, one need not call wait as
     * we implicitly wait for completion of the request on destruction of the
     * response future.
     */
    public function getAsync(string $cacheName, string $key): ResponseFuture
    {
        return $this->getNextDataClient()->get($cacheName, $key);
    }

    /**
     * Gets the cache value stored for a given key.
     *
     * @param string $cacheName Name of the cache to perform the lookup in.
     * @param string $key The key to look up.
     * @return GetResponse Represents the result of the get operation and stores the retrieved value. This
     * result is resolved to a type-safe object of one of the following types:<br>
     * * GetHit<br>
     * * GetMiss<br>
     * * GetError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($hit = $response->asHit()) {<br>
     * &nbsp;&nbsp;$value = $hit->valueString();<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error response<br>
     * }</code>
     */
    public function get(string $cacheName, string $key): GetResponse
    {
        return $this->getAsync($cacheName, $key)->wait();
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
     * @return ResponseFuture<SetIfNotExistsResponse> A waitable future which
     * will provide the result of the set operation upon a blocking call to
     * wait.
     * <code>$response = $responseFuture->wait();
     * }</code>
     * The response represents the result of the setIfNotExists operation. This
     * result is resolved to a type-safe object of one of the following
     * types:<br>
     * * SetIfNotExistsResponseStored<br>
     * * SetIfNotExistsResponseNotStored<br>
     * * SetIfNotExistsError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($hit = $response->asStored()) {<br>
     * &nbsp;&nbsp;$value = $hit->valueString();<br>
     * } elseif ($error = $response->asNotStored()) {<br>
     * &nbsp;&nbsp;// key was already set in the cache<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error response<br>
     * }</code>
     * If inspection of the response is not required, one need not call wait as
     * we implicitly wait for completion of the request on destruction of the
     * response future.
     */
    public function setIfNotExistsAsync(string $cacheName, string $key, string $value, int $ttlSeconds = 0): ResponseFuture
    {
        return $this->getNextDataClient()->setIfNotExists($cacheName, $key, $value, $ttlSeconds);
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
     * @return SetIfNotExistsResponse Represents the result of the setIfNotExists operation. This
     * result is resolved to a type-safe object of one of the following types:<br>
     * * SetIfNotExistsResponseStored<br>
     * * SetIfNotExistsResponseNotStored<br>
     * * SetIfNotExistsError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($hit = $response->asStored()) {<br>
     * &nbsp;&nbsp;$value = $hit->valueString();<br>
     * } elseif ($error = $response->asNotStored()) {<br>
     * &nbsp;&nbsp;// key was already set in the cache<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error response<br>
     * }</code>
     */
    public function setIfNotExists(string $cacheName, string $key, string $value, int $ttlSeconds = 0): SetIfNotExistsResponse
    {
        return $this->setIfNotExistsAsync($cacheName, $key, $value, $ttlSeconds)->wait();
    }

    /**
     * Removes the key from the cache.
     *
     * @param string $cacheName Name of the cache from which to remove the key
     * @param string $key The key to remove
     * @return ResponseFuture<DeleteResponse> A waitable future which will
     * provide the result of the set operation upon a blocking call to wait.
     * <code>$response = $responseFuture->wait();
     * }</code>
     * The response represents the result of the delete operation. This result
     * is resolved to a type-safe object of one of the following types:<br>
     * * DeleteSuccess<br>
     * * DeleteError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     * If inspection of the response is not required, one need not call wait as
     * we implicitly wait for completion of the request on destruction of the
     * response future.
     */
    public function deleteAsync(string $cacheName, string $key): ResponseFuture
    {
        return $this->getNextDataClient()->delete($cacheName, $key);
    }

    /**
     * Removes the key from the cache.
     *
     * @param string $cacheName Name of the cache from which to remove the key
     * @param string $key The key to remove
     * @return DeleteResponse Represents the result of the delete operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * DeleteSuccess<br>
     * * DeleteError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function delete(string $cacheName, string $key): DeleteResponse
    {
        return $this->deleteAsync($cacheName, $key)->wait();
    }

    /**
     * Check to see if multiple keys exist in the cache.
     *
     * @param string $cacheName Name of the cache in which to look for keys
     * @param array $keys List of keys to check
     * @return ResponseFuture<KeysExistResponse> A waitable future which will
     * provide the result of the set operation upon a blocking call to wait.
     * <code>$response = $responseFuture->wait();
     * }</code>
     * The response represents the result of the keys exist operation. This
     * result is resolved to a type-safe object of one of the following
     * types:<br>
     * * KeysExistSuccess<br>
     * * KeysExistError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * } elseif ($success = $response->asSuccess()) {<br>
     * &nbsp;&nbsp;// get a list of booleans representing the existence of the key at that index<br>
     * &nbsp;&nbsp;$asList = $success->exists();<br>
     * &nbsp;&nbsp;// get a dict with the key names as keys and boolean values<br>
     * &nbsp;&nbsp;$asDict = $success->existsDictionary();<br>
     * }</code>
     * If inspection of the response is not required, one need not call wait as
     * we implicitly wait for completion of the request on destruction of the
     * response future.
     */
    public function keysExistAsync(string $cacheName, array $keys): ResponseFuture
    {
        return $this->getNextDataClient()->keysExist($cacheName, $keys);
    }

    /**
     * Check to see if multiple keys exist in the cache.
     *
     * @param string $cacheName Name of the cache in which to look for keys
     * @param array $keys List of keys to check
     * @return KeysExistResponse Represents the result of the keys exist operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * KeysExistSuccess<br>
     * * KeysExistError<br>
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
    public function keysExist(string $cacheName, array $keys): KeysExistResponse
    {
        return $this->keysExistAsync($cacheName, $keys)->wait();
    }

    /**
     * Check to see if a key exists in the cache.
     *
     * @param string $cacheName Name of the cache in which to look for the key
     * @param string $key The key to check
     * @return ResponseFuture<KeyExistsResponse> A waitable future which will
     * provide the result of the set operation upon a blocking call to wait.
     * <code>$response = $responseFuture->wait();
     * }</code>
     * The response represents the result of the keys exist operation. This
     * result is resolved to a type-safe object of one of the following
     * types:<br>
     * * KeyExistsSuccess<br>
     * * KeyExistsError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * } elseif ($success = $response->asSuccess()) {<br>
     * &nbsp;&nbsp;$keyIsInCache = $success->exists();<br>
     * }</code>
     * If inspection of the response is not required, one need not call wait as
     * we implicitly wait for completion of the request on destruction of the
     * response future.
     */
    public function keyExistsAsync(string $cacheName, string $key): ResponseFuture
    {
        return $this->getNextDataClient()->keyExists($cacheName, $key);
    }

    /**
     * Check to see if a key exists in the cache.
     *
     * @param string $cacheName Name of the cache in which to look for the key
     * @param string $key The key to check
     * @return KeyExistsResponse Represents the result of the keys exist operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * KeyExistsSuccess<br>
     * * KeyExistsError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * } elseif ($success = $response->asSuccess()) {<br>
     * &nbsp;&nbsp;$keyIsInCache = $success->exists();<br>
     * }</code>
     */
    public function keyExists(string $cacheName, string $key): KeyExistsResponse
    {
        return $this->keyExistsAsync($cacheName, $key)->wait();
    }

    /**
     * Increment a key's value in the cache by a specified amount.
     *
     * @param string $cacheName Name of the cache in which to increment the key's value
     * @param string $key The key top increment
     * @param int $amount The amount to increment by. May be positive, negative, or zero. Defaults to 1.
     * @param int|null $ttlSeconds TTL for the item in cache. This TTL takes precedence over the TTL used when initializing a cache client.
     *   Defaults to client TTL. If specified must be strictly positive.
     * @return ResponseFuture<IncrementResponse> A waitable future which will
     * provide the result of the set operation upon a blocking call to wait.
     * <code>$response = $responseFuture->wait();
     * }</code>
     * The response represents the result of the keys exist operation. This
     * result is resolved to a type-safe object of one of the following
     * types:<br>
     * * IncrementSuccess<br>
     * * IncrementError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * } elseif ($success = $response->asSuccess()) {<br>
     * &nbsp;&nbsp;$keyIsInCache = $success->exists();<br>
     * }</code>
     * If inspection of the response is not required, one need not call wait as
     * we implicitly wait for completion of the request on destruction of the
     * response future.
     */
    public function incrementAsync(
        string $cacheName, string $key, int $amount = 1, ?int $ttlSeconds = null
    ): ResponseFuture
    {
        return $this->getNextDataClient()->increment($cacheName, $key, $amount, $ttlSeconds);
    }

    /**
     * Increment a key's value in the cache by a specified amount.
     *
     * @param string $cacheName Name of the cache in which to increment the key's value
     * @param string $key The key top increment
     * @param int $amount The amount to increment by. May be positive, negative, or zero. Defaults to 1.
     * @param int|null $ttlSeconds TTL for the item in cache. This TTL takes precedence over the TTL used when initializing a cache client.
     *   Defaults to client TTL. If specified must be strictly positive.
     * @return IncrementResponse Represents the result of the keys exist operation. This result is
     * resolved to a type-safe object of one of the following types:<br>
     * * IncrementSuccess<br>
     * * IncrementError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * } elseif ($success = $response->asSuccess()) {<br>
     * &nbsp;&nbsp;$keyIsInCache = $success->exists();<br>
     * }</code>
     */
    public function increment(
        string $cacheName, string $key, int $amount = 1, ?int $ttlSeconds = null
    ): IncrementResponse
    {
        return $this->incrementAsync($cacheName, $key, $amount, $ttlSeconds)->wait();
    }

    /**
     * Fetch the entire list from the cache.
     *
     * @param string $cacheName Name of the cache to perform the lookup in.
     * @param string $listName The list to fetch.
     * @return ListFetchResponse Represents the result of the list fetch operation and the associated list.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * ListFetchHit<br>
     * * ListFetchMiss<br>
     * * ListFetchError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($hit = $response->asHit()) {<br>
     * &nbsp;&nbsp;$theList = $hit->valuesArray();<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function listFetch(string $cacheName, string $listName): ListFetchResponse
    {
        return $this->getNextDataClient()->listFetch($cacheName, $listName);
    }

    /**
     * Push a value to the beginning of a list.
     *
     * @param string $cacheName Name of the cache that contains the list.
     * @param string $listName The list to push the value on.
     * @param string $value The value to push to the front of the list.
     * @param int|null $truncateBackToSize Ensure the list does not exceed this length. Remove excess from the end of the list. Must be a positive number.
     * @param CollectionTtl|null $ttl Specifies if collection TTL is refreshed when updated and the TTL value to which it is set.
     * @return ListPushFrontResponse Represents the result of the operation and the length of the list after the push.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * ListPushFrontSuccess<br>
     * * ListPushFrontError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($success = $response->asSuccess()) {<br>
     * &nbsp;&nbsp;$listLength = $success->listLength();<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function listPushFront(
        string $cacheName, string $listName, string $value, ?int $truncateBackToSize = null, ?CollectionTtl $ttl = null
    ): ListPushFrontResponse
    {
        return $this->getNextDataClient()->listPushFront($cacheName, $listName, $value, $truncateBackToSize, $ttl);
    }

    /**
     * Push a value to the end of a list.
     *
     * @param string $cacheName Name of the cache that contains the list.
     * @param string $listName The list to push the value on.
     * @param string $value The value to push to the front of the list.
     * @param int|null $truncateFrontToSize Ensure the list does not exceed this length. Remove excess from the front of the list. Must be a positive number.
     * @param CollectionTtl|null $ttl Specifies if collection TTL is refreshed when updated and the TTL value to which it is set.
     * @return ListPushBackResponse Represents the result of the operation and the length of the list after the push.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * ListPushBackSuccess<br>
     * * ListPushBackError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($success = $response->asSuccess()) {<br>
     * &nbsp;&nbsp;$listLength = $success->listLength();<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function listPushBack(
        string $cacheName, string $listName, string $value, ?int $truncateFrontToSize = null, ?CollectionTtl $ttl = null
    ): ListPushBackResponse
    {
        return $this->getNextDataClient()->listPushBack($cacheName, $listName, $value, $truncateFrontToSize, $ttl);
    }

    /**
     * Pop a value from the beginning of a list.
     *
     * @param string $cacheName Name of the cache that contains the list.
     * @param string $listName The list to pop the value from.
     * @return ListPopFrontResponse Represents the result of the operation and the popped value.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * ListPopFrontHit<br>
     * * ListPopFrontMiss<br>
     * * ListPushFrontError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($hit = $response->asHit()) {<br>
     * &nbsp;&nbsp;$poppedValue = $hit->valueString();<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function listPopFront(string $cacheName, string $listName): ListPopFrontResponse
    {
        return $this->getNextDataClient()->listPopFront($cacheName, $listName);
    }

    /**
     * Pop a value from the back of a list.
     *
     * @param string $cacheName Name of the cache that contains the list.
     * @param string $listName The list to pop the value from.
     * @return ListPopBackResponse Represents the result of the operation and the popped value.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * ListPopBackHit<br>
     * * ListPopBackMiss<br>
     * * ListPushBackError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($hit = $response->asHit()) {<br>
     * &nbsp;&nbsp;$poppedValue = $hit->valueString();<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function listPopBack(string $cacheName, string $listName): ListPopBackResponse
    {
        return $this->getNextDataClient()->listPopBack($cacheName, $listName);
    }

    /**
     * Remove all elements from a list that are equal to a particular value.
     *
     * @param string $cacheName Name of the cache that contains the list.
     * @param string $listName The list from which to remove the matching elements.
     * @param string $value The value to completely remove from the list.
     * @return ListRemoveValueResponse Represents the result of the list remove value operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * ListRemoveValueSuccess<br>
     * * ListRemoveValueError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function listRemoveValue(string $cacheName, string $listName, string $value): ListRemoveValueResponse
    {
        return $this->getNextDataClient()->listRemoveValue($cacheName, $listName, $value);
    }

    /**
     * Calculates the length of the list in the cache.
     *
     * @param string $cacheName Name of the cache that contains the list.
     * @param string $listName The list to calculate the length.
     * @return ListLengthResponse Represents the result of the list length operation and contains the list length.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * ListLengthSuccess<br>
     * * ListLengthError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($success = $response->asSuccess()) {<br>
     * &nbsp;&nbsp;$theLength = $success->length();<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function listLength(string $cacheName, string $listName): ListLengthResponse
    {
        return $this->getNextDataClient()->listLength($cacheName, $listName);
    }

    /**
     * Set a dictionary field to a value.
     *
     * @param string $cacheName Name of the cache that contains the dictionary.
     * @param string $dictionaryName The dictionary to set the field in. Will be created if it doesn't exist.
     * @param string $field The field in the dictionary to set.
     * @param string $value The value to be stored.
     * @param CollectionTtl|null $ttl TTL for the dictionary in cache. This TTL takes precedence over the TTL used when initializing a cache client. Defaults to client TTL.
     * @return DictionarySetFieldResponse Represents the result of the dictionary set field operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * DictionarySetFieldSuccess<br>
     * * DictionarySetFieldError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function dictionarySetField(string $cacheName, string $dictionaryName, string $field, string $value, ?CollectionTtl $ttl = null): DictionarySetFieldResponse
    {
        return $this->getNextDataClient()->dictionarySetField($cacheName, $dictionaryName, $field, $value, $ttl);
    }

    /**
     * Get the cache value stored for the given dictionary and field.
     *
     * @param string $cacheName Name of the cache that contains the dictionary.
     * @param string $dictionaryName The dictionary to get the field from.
     * @param string $field The field in the dictionary to get.
     * @return DictionaryGetFieldResponse Represents the result of the get field operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * DictionaryGetFieldHit<br>
     * * DictionaryGetFieldMiss<br>
     * * DictionaryGetFieldError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($hit = $response->asHit()) {<br>
     * &nbsp;&nbsp;$theValue = $hit->valueString();<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function dictionaryGetField(string $cacheName, string $dictionaryName, string $field): DictionaryGetFieldResponse
    {
        return $this->getNextDataClient()->dictionaryGetField($cacheName, $dictionaryName, $field);
    }

    /**
     * Fetch an entire dictionary from the cache.
     *
     * @param string $cacheName Name of the cache that contains the dictionary.
     * @param string $dictionaryName The dictionary to fetch.
     * @return DictionaryFetchResponse Represents the result of the fetch operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * DictionaryFetchHit<br>
     * * DictionaryFetchMiss<br>
     * * DictionaryFetchError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($hit = $response->asHit()) {<br>
     * &nbsp;&nbsp;$theValue = $hit->valuesDictionary();<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function dictionaryFetch(string $cacheName, string $dictionaryName): DictionaryFetchResponse
    {
        return $this->getNextDataClient()->dictionaryFetch($cacheName, $dictionaryName);
    }

    /**
     * Set several dictionary field-value pairs in the cache.
     *
     * @param string $cacheName Name of the cache that contains the dictionary.
     * @param string $dictionaryName The dictionary to set the field in. Will be created if it doesn't exist.
     * @param array $elements The field-value pairs to be stored.
     * @param CollectionTtl|null $ttl TTL for the dictionary in cache. This TTL takes precedence over the TTL used when initializing a cache client. Defaults to client TTL.
     * @return DictionarySetFieldsResponse Represents the result of the dictionary set field operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * DictionarySetFieldsSuccess<br>
     * * DictionarySetFieldsError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function dictionarySetFields(string $cacheName, string $dictionaryName, array $elements, ?CollectionTtl $ttl = null): DictionarySetFieldsResponse
    {
        return $this->getNextDataClient()->dictionarySetFields($cacheName, $dictionaryName, $elements, $ttl);
    }

    /**
     * Get several dictionary values from the cache.
     *
     * @param string $cacheName Name of the cache that contains the dictionary.
     * @param string $dictionaryName The dictionary to get the fields from.
     * @param array $fields The fields in the dictionary to lookup.
     * @return DictionaryGetFieldsResponse Represents the result of the get fields operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * DictionaryGetFieldsHit<br>
     * * DictionaryGetFieldsMiss<br>
     * * DictionaryGetFieldsError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($hit = $response->asHit()) {<br>
     * &nbsp;&nbsp;// get a list of responses corresponding to the list of requested values<br>
     * &nbsp;&nbsp;// each item in the list is an instance of one of the following:<br>
     * &nbsp;&nbsp;// - DictionaryGetFieldHit<br>
     * &nbsp;&nbsp;// - DictionaryGetFieldMiss<br>
     * &nbsp;&nbsp;// - DictionaryGetFieldError<br>
     * &nbsp;&nbsp;$responseTypes = $hit->responses();
     * &nbsp;&nbsp;// get a dictionary of responses mapping requested field name keys to their values<br>
     * &nbsp;&nbsp;// fields that were not found in the cache dictionary are omitted<br>
     * &nbsp;&nbsp;$valuesDict = $hit->valuesDictionary();
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function dictionaryGetFields(string $cacheName, string $dictionaryName, array $fields): DictionaryGetFieldsResponse
    {
        return $this->getNextDataClient()->dictionaryGetFields($cacheName, $dictionaryName, $fields);
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
     * @return DictionaryIncrementResponse Represents the result of the dictionary increment operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * DictionaryIncrementSuccess<br>
     * * DictionaryIncrementError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($success = $response->asSuccess()) {<br>
     * &nbsp;&nbsp;$newValue = $success->valueInt();
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function dictionaryIncrement(
        string $cacheName, string $dictionaryName, string $field, int $amount = 1, ?CollectionTtl $ttl = null
    ): DictionaryIncrementResponse
    {
        return $this->getNextDataClient()->dictionaryIncrement($cacheName, $dictionaryName, $field, $amount, $ttl);
    }

    /**
     * Remove a field from a dictionary.
     *
     * @param string $cacheName Name of the cache that contains the dictionary.
     * @param string $dictionaryName The dictionary from which to remove the field.
     * @param string $field The field to remove.
     * @return DictionaryRemoveFieldResponse Represents the result of the dictionary remove field operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * DictionaryRemoveFieldSuccess<br>
     * * DictionaryRemoveFieldError<br>
     * if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function dictionaryRemoveField(string $cacheName, string $dictionaryName, string $field): DictionaryRemoveFieldResponse
    {
        return $this->getNextDataClient()->dictionaryRemoveField($cacheName, $dictionaryName, $field);
    }

    /**
     * Remove multiple fields from a dictionary.
     *
     * @param string $cacheName Name of the cache that contains the dictionary.
     * @param string $dictionaryName The dictionary from which to remove the fields.
     * @param array $fields The fields to remove.
     * @return DictionaryRemoveFieldsResponse Represents the result of the dictionary remove fields operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * DictionaryRemoveFieldsSuccess<br>
     * * DictionaryRemoveFieldsError<br>
     * if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function dictionaryRemoveFields(string $cacheName, string $dictionaryName, array $fields): DictionaryRemoveFieldsResponse
    {
        return $this->getNextDataClient()->dictionaryRemoveFields($cacheName, $dictionaryName, $fields);
    }

    /**
     * Add an element to a set.
     *
     * @param string $cacheName Name of the cache that contains the set.
     * @param string $setName The set to add the element to.
     * @param string $element The element to add.
     * @param CollectionTtl|null $ttl TTL for the dictionary in cache. This TTL takes precedence over the TTL used when initializing a cache client. Defaults to client TTL.
     * @return ResponseFuture<SetAddElementResponse> A waitable future which
     * will provide the result of the set operation upon a blocking call to
     * wait.
     * <code>$response = $responseFuture->wait();
     * }</code>
     * The response represents the result of the set add element operation.
     * This result is resolved to a type-safe object of one of the following
     * types:<br>
     * * SetAddElementSuccess<br>
     * * SetAddElementError<br>
     * if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     * If inspection of the response is not required, one need not call wait as
     * we implicitly wait for completion of the request on destruction of the
     * response future.
     */
    public function setAddElementAsync(string $cacheName, string $setName, string $element, ?CollectionTtl $ttl = null): ResponseFuture
    {
        return $this->getNextDataClient()->setAddElement($cacheName, $setName, $element, $ttl);
    }

    /**
     * Add an element to a set.
     *
     * @param string $cacheName Name of the cache that contains the set.
     * @param string $setName The set to add the element to.
     * @param string $element The element to add.
     * @param CollectionTtl|null $ttl TTL for the dictionary in cache. This TTL takes precedence over the TTL used when initializing a cache client. Defaults to client TTL.
     * @return SetAddElementResponse Represents the result of the set add element operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * SetAddElementSuccess<br>
     * * SetAddElementError<br>
     * if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function setAddElement(string $cacheName, string $setName, string $element, ?CollectionTtl $ttl = null): SetAddElementResponse
    {
        return $this->setAddElementAsync($cacheName, $setName, $element, $ttl)->wait();
    }

    /**
     * Add many elements to a set.
     *
     * @param string $cacheName Name of the cache that contains the set.
     * @param string $setName The set to add the element to.
     * @param list<string> $elements The elements to add.
     * @param CollectionTtl|null $ttl TTL for the dictionary in cache. This TTL takes precedence over the TTL used when initializing a cache client. Defaults to client TTL.
     * @return ResponseFuture<SetAddElementsResponse> A waitable future which
     * will provide the result of the set operation upon a blocking call to
     * wait.
     * <code>$response = $responseFuture->wait();
     * }</code>
     * The response represents the result of the set add elements operation.
     * This result is resolved to a type-safe object of one of the following
     * types:<br>
     * * SetAddElementsSuccess<br>
     * * SetAddElementsError<br>
     * if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     * If inspection of the response is not required, one need not call wait as
     * we implicitly wait for completion of the request on destruction of the
     * response future.
     */
    public function setAddElementsAsync(string $cacheName, string $setName, array $elements, ?CollectionTtl $ttl = null): ResponseFuture
    {
        return $this->getNextDataClient()->setAddElements($cacheName, $setName, $elements, $ttl);
    }

    /**
     * Add many elements to a set.
     *
     * @param string $cacheName Name of the cache that contains the set.
     * @param string $setName The set to add the element to.
     * @param list<string> $elements The elements to add.
     * @param CollectionTtl|null $ttl TTL for the dictionary in cache. This TTL takes precedence over the TTL used when initializing a cache client. Defaults to client TTL.
     * @return SetAddElementsResponse Represents the result of the set add elements operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * SetAddElementsSuccess<br>
     * * SetAddElementsError<br>
     * if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function setAddElements(string $cacheName, string $setName, array $elements, ?CollectionTtl $ttl = null): SetAddElementsResponse
    {
        return $this->setAddElementsAsync($cacheName, $setName, $elements, $ttl)->wait();
    }

    /**
     * Fetch an entire set from the cache.
     *
     * @param string $cacheName Name of the cache that contains the set.
     * @param string $setName The set to fetch.
     * @return ResponseFuture<SetFetchResponse> A waitable future which will
     * provide the result of the set operation upon a blocking call to wait.
     * <code>$response = $responseFuture->wait();
     * }</code>
     * The response represents the result of the set fetch operation. This
     * result is resolved to a type-safe object of one of the following
     * types:<br>
     * * SetFetchHit<br>
     * * SetFetchMiss<br>
     * * SetFetchError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($hit = $response->asHit()) {<br>
     * &nbsp;&nbsp;$theSet = $response->valueArray();<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     * If inspection of the response is not required, one need not call wait as
     * we implicitly wait for completion of the request on destruction of the
     * response future.
     */
    public function setFetchAsync(string $cacheName, string $setName): ResponseFuture
    {
        return $this->getNextDataClient()->setFetch($cacheName, $setName);
    }

    /**
     * Fetch an entire set from the cache.
     *
     * @param string $cacheName Name of the cache that contains the set.
     * @param string $setName The set to fetch.
     * @return SetFetchResponse Represents the result of the set fetch operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * SetFetchHit<br>
     * * SetFetchMiss<br>
     * * SetFetchError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($hit = $response->asHit()) {<br>
     * &nbsp;&nbsp;$theSet = $response->valueArray();<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function setFetch(string $cacheName, string $setName): SetFetchResponse
    {
        return $this->setFetchAsync($cacheName, $setName)->wait();
    }

    /**
     * Get the length of a set from the cache.
     *
     * @param string $cacheName Name of the cache that contains the set.
     * @param string $setName The name of the set whose length should be returned.
     * @return ResponseFuture<SetLengthResponse> A waitable future which will
     * provide the result of the set operation upon a blocking call to wait.
     * <code>$response = $responseFuture->wait();
     * }</code>
     * The response represents the result of the set length operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * SetLengthSuccess<br>
     * * SetLengthError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($success = $response->asSuccess()) {<br>
     * &nbsp;&nbsp;$length = success->length();<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     * If inspection of the response is not required, one need not call wait as
     * we implicitly wait for completion of the request on destruction of the
     * response future.
     */
    public function setLengthAsync(string $cacheName, string $setName): ResponseFuture
    {
        return $this->getNextDataClient()->setLength($cacheName, $setName);
    }

    /**
     * Get the length of a set from the cache.
     *
     * @param string $cacheName Name of the cache that contains the set.
     * @param string $setName The name of the set whose length should be returned.
     * @return SetLengthResponse Represents the result of the set length operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * SetLengthSuccess<br>
     * * SetLengthError<br>
     * Pattern matching can be to operate on the appropriate subtype:<br>
     * <code>if ($success = $response->asSuccess()) {<br>
     * &nbsp;&nbsp;$length = success->length();<br>
     * } elseif ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function setLength(string $cacheName, string $setName): SetLengthResponse
    {
        return $this->setLengthAsync($cacheName, $setName)->wait();
    }

    /**
     * Remove an element from a set.
     *
     * @param string $cacheName Name of the cache that contains the set.
     * @param string $setName The set from which to remove the element.
     * @param string $element The element to remove.
     * @return ResponseFuture<SetRemoveElementResponse> A waitable future which
     * will provide the result of the set operation upon a blocking call to
     * wait.
     * <code>$response = $responseFuture->wait();
     * }</code>
     * The response represents the result of the set remove element operation.
     * This result is resolved to a type-safe object of one of the following
     * types:<br>
     * * SetRemoveElementSuccess<br>
     * * SetRemoveElementError<br>
     * if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     * If inspection of the response is not required, one need not call wait as
     * we implicitly wait for completion of the request on destruction of the
     * response future.
     */
    public function setRemoveElementAsync(string $cacheName, string $setName, string $element): ResponseFuture
    {
        return $this->getNextDataClient()->setRemoveElement($cacheName, $setName, $element);
    }

    /**
     * Remove an element from a set.
     *
     * @param string $cacheName Name of the cache that contains the set.
     * @param string $setName The set from which to remove the element.
     * @param string $element The element to remove.
     * @return SetRemoveElementResponse Represents the result of the set remove element operation.
     * This result is resolved to a type-safe object of one of the following types:<br>
     * * SetRemoveElementSuccess<br>
     * * SetRemoveElementError<br>
     * if ($error = $response->asError()) {<br>
     * &nbsp;&nbsp;// handle error condition<br>
     * }</code>
     */
    public function setRemoveElement(string $cacheName, string $setName, string $element): SetRemoveElementResponse
    {
        return $this->setRemoveElementAsync($cacheName, $setName, $element)->wait();
    }

    private function getNextDataClient(): ScsDataClient
    {
        $client = $this->dataClients[$this->nextDataClientIndex]->getClient();
        $this->nextDataClientIndex = ($this->nextDataClientIndex + 1) % count($this->dataClients);
        return $client;
    }
}
