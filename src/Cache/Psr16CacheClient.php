<?php
declare(strict_types=1);

namespace Momento\Cache;

use DateInterval;
use Momento\Auth\ICredentialProvider;
use Momento\Cache\CacheOperationTypes\ResponseBase;
use Momento\Cache\Errors\CacheException;
use Psr\SimpleCache\CacheInterface;
use function Momento\Utilities\validatePsr16Key;

class Psr16CacheClient implements CacheInterface
{

    private SimpleCacheClient $momento;
    // TODO: this needs to be, by spec, the longest duration possible supported
    //   by our backend. Setting it to a day for now.
    private const DEFAULT_TTL_SECONDS = 86400;
    private const CACHE_NAME = "momento-psr16";
    private const KEY_LIST_NAME = "momento-psr16-keys";
    // Key metadata list TTL = 1 day
    private const KEY_LIST_TTL = 86400;
    // TODO: I hate this. More discussion below.
    private ?CacheException $lastError = null;

    public function __construct(ICredentialProvider $authProvider, ?int $defaultTtlSeconds)
    {
        $ttlSeconds = $defaultTtlSeconds ?? self::DEFAULT_TTL_SECONDS;
        $this->momento = new SimpleCacheClient($authProvider, $ttlSeconds);
        $response = $this->momento->createCache(self::CACHE_NAME);
        if ($error = $response->asError()) {
            throw $this->cacheExceptionFromError($error);
        }
    }

    public static function dateIntervalToSeconds(DateInterval $di): int
    {
        $secs = $di->days * 86400 + $di->h * 3600 + $di->i * 60 + $di->s;
        if ($di->invert) {
            $secs *= -1;
        }
        return $secs;
    }

    private function cacheExceptionFromError(ResponseBase $err): CacheException
    {
        // TODO: think about adding "throw mode" a la https://packagist.org/packages/firehed/redis-psr16
        return new CacheException($err->message(), 0, $err->innerException());
    }

    // TODO: keep this or delete/restore cache instead (which has issues with concurrency)
    private function addKey(string $key): bool
    {
        $response = $this->momento->listPushFront(
            self::CACHE_NAME, self::KEY_LIST_NAME, $key, true, self::KEY_LIST_TTL
        );
        if ($error = $response->asError()) {
            $this->lastError = $this->cacheExceptionFromError($error);
            return false;
        }
        return true;
    }

    private function removeKey(string $key): bool
    {
        $response = $this->momento->listRemoveValue(self::CACHE_NAME, self::KEY_LIST_NAME, $key);
        if ($error = $response->asError()) {
            $this->lastError = $this->cacheExceptionFromError($error);
            return false;
        }
        return true;
    }

    public function getLastError($clear_error = true): CacheException|null
    {
        $error = $this->lastError;
        if ($clear_error) {
            $this->lastError = null;
        }
        return $error;
    }

    /**
     * @inheritDoc
     */
    public function get(string $key, mixed $default = null): mixed
    {
        validatePsr16Key($key);
        $response = $this->momento->get(self::CACHE_NAME, $key);
        // TODO: How do we handle errors here!?
        //   From the PSR:
        //   If it is not possible to return the exact saved value for any reason,
        //   implementing libraries MUST respond with a cache miss rather than corrupted data
        if ($error = $response->asError()) {
            $this->lastError = $this->cacheExceptionFromError($error);
            return $default;
        } elseif ($response->asMiss()) {
            return $default;
        }
        $hit = $response->asHit();
        return unserialize($hit->value());
    }

    /**
     * @inheritDoc
     */
    public function set(string $key, mixed $value, DateInterval|int|null $ttl = null): bool
    {
        validatePsr16Key($key);
        if (is_null($ttl)) {
            $ttl = self::DEFAULT_TTL_SECONDS;
        } elseif ($ttl instanceof DateInterval) {
            $ttl = self::dateIntervalToSeconds($ttl);
        }

        if ($ttl <= 0) {
            // according to the spec, setting an item with a 0 or negative ttl
            // must immediately remove that item from the cache
            return $this->delete($key);
        }

        $response = $this->momento->set(self::CACHE_NAME, $key, serialize($value), $ttl);
        if ($error = $response->asError()) {
            $this->lastError = $this->cacheExceptionFromError($error);
            return false;
        }
        return $this->addKey($key);
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        validatePsr16Key($key);
        $response = $this->momento->delete(self::CACHE_NAME, $key);
        if ($error = $response->asError()) {
            $this->lastError = $this->cacheExceptionFromError($error);
            return false;
        }
        return $this->removeKey($key);
    }

    /**
     * @inheritDoc
     */
    public function clear(): bool
    {
        $response = $this->momento->listFetch(self::CACHE_NAME, self::KEY_LIST_NAME);
        if ($error = $response->asError()) {
            $this->lastError = $this->cacheExceptionFromError($error);
            return false;
        } elseif ($response->asMiss()) {
            $this->lastError = new CacheException("Got a cache miss for the list of keys to clear.");
            return false;
        }
        // it is possible (likely) that we're tracking keys that have already expired from the cache,
        // so error handling is a little loose here to avoid false negatives.
        foreach ($response->asHit()->values() as $key) {
            if ($this->delete($key) === false) {
                return false;
            };
        }
        $response = $this->momento->listErase(self::CACHE_NAME, self::KEY_LIST_NAME);
        if ($error = $response->asError()) {
            $this->lastError = $this->cacheExceptionFromError($error);
            return false;
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        foreach ($keys as $key) {
            validatePsr16Key($key);
        }
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }
        return $result;
    }

    /**
     * @inheritDoc
     */
    public function setMultiple(iterable $values, DateInterval|int|null $ttl = null): bool
    {
        foreach ($values as $key => $value) {
            validatePsr16Key($key);
        }
        foreach ($values as $key => $value) {
            $result = $this->set($key, $value);
            if ($result === false) {
                return false;
            }
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function deleteMultiple(iterable $keys): bool
    {
        foreach ($keys as $key) {
            validatePsr16Key($key);
        }
        foreach ($keys as $key) {
            $this->delete($key);
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function has(string $key): bool
    {
        validatePsr16Key($key);
        return (bool)$this->get($key);
    }
}
