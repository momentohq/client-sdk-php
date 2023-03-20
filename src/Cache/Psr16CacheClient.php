<?php
declare(strict_types=1);

namespace Momento\Cache;

use DateInterval;
use Momento\Auth\CredentialProvider;
use Momento\Cache\CacheOperationTypes\ResponseBase;
use Momento\Cache\Errors\CacheException;
use Momento\Cache\Errors\NotImplementedException;
use Momento\Config\IConfiguration;
use Psr\SimpleCache\CacheInterface;
use function Momento\Utilities\validatePsr16Key;

class Psr16CacheClient implements CacheInterface
{

    private SimpleCacheClient $momento;
    // PSR-16 spec requires a default of as close to "forever" as the engine allows.
    // The below is set to a week and will be truncated as necessary for the cache
    // backend in use.
    private const DEFAULT_TTL_SECONDS = 604800;
    private const CACHE_NAME = "momento-psr16";
    private ?CacheException $lastError = null;
    private bool $throwExceptions = true;

    /**
     * @param IConfiguration $configuration
     * @param CredentialProvider $authProvider
     * @param int|null $defaultTtlSeconds
     * @param bool|null $throwExceptions
     */
    public function __construct(
        IConfiguration     $configuration,
        CredentialProvider $authProvider,
        ?int               $defaultTtlSeconds,
        ?bool              $throwExceptions = null
    )
    {
        $ttlSeconds = $defaultTtlSeconds ?? self::DEFAULT_TTL_SECONDS;
        $this->momento = new SimpleCacheClient($configuration, $authProvider, $ttlSeconds);
        if (!is_null($throwExceptions)) {
            $this->throwExceptions = $throwExceptions;
        }
        $response = $this->momento->createCache(self::CACHE_NAME);
        if ($error = $response->asError()) {
            $this->throwExceptions = true;
            $this->handleCacheError($error);
        }
    }

    /**
     * Coverts a DateInterval object into seconds.
     *
     * @param DateInterval $di
     * @return int
     */
    public static function dateIntervalToSeconds(DateInterval $di): int
    {
        $secs = $di->days * 86400 + $di->h * 3600 + $di->i * 60 + $di->s;
        if ($di->invert) {
            $secs *= -1;
        }
        return $secs;
    }

    /**
     * Either throws or returns an exception derived from a cache client error response,
     * depending on the value of the $throwExceptions property.
     *
     * @param ResponseBase $err
     * @return void
     */
    private function handleCacheError(ResponseBase $err): void
    {
        $exception = new CacheException($err->message(), 0, $err->innerException());
        if ($this->throwExceptions) {
            throw $exception;
        }
        $this->lastError = $exception;
    }

    /**
     * If the $throwExceptions property is set to false, exceptions handled by the
     * handleCacheError method are stored in a $lastError property. This method allows
     * access to the exceptions, optionally clearing the property when the error is
     * returned.
     *
     * If $throwExceptions is true, this method will always reurn null as the property will
     * never be set.
     *
     * @param bool $clear_error
     * @return CacheException|null
     */
    public function getLastError(bool $clear_error = true): CacheException|null
    {
        if (!$this->throwExceptions) {
            return null;
        }
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
        if ($error = $response->asError()) {
            $this->handleCacheError($error);
            return $default;
        } elseif ($response->asMiss()) {
            return $default;
        }
        $hit = $response->asHit();
        return unserialize($hit->valueString());
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
            $this->handleCacheError($error);
            return false;
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function delete(string $key): bool
    {
        validatePsr16Key($key);
        $response = $this->momento->delete(self::CACHE_NAME, $key);
        if ($error = $response->asError()) {
            $this->handleCacheError($error);
            return false;
        }
        return true;
    }

    /**
     * The clear method is currently unimplemented.
     *
     * @throws NotImplementedException
     */
    public function clear(): bool
    {
        throw new NotImplementedException("The clear() method is not currently implemented.");
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
