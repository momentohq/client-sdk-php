<?php
declare(strict_types=1);

namespace Momento\Auth\AccessControl;

/**
 * Selects the cache item or items that a permission applies to.
 */
class CacheItemSelector
{
    private ?string $key;
    private ?string $keyPrefix;

    private function __construct(?string $key, ?string $keyPrefix)
    {
        $this->key = $key;
        $this->keyPrefix = $keyPrefix;
    }

    public static function allCacheItems(): CacheItemSelector
    {
        return new CacheItemSelector(null, null);
    }

    public static function byKey(string $key): CacheItemSelector
    {
        return new CacheItemSelector($key, null);
    }

    public static function byKeyPrefix(string $keyPrefix): CacheItemSelector
    {
        return new CacheItemSelector(null, $keyPrefix);
    }

    public function isAllCacheItems(): bool
    {
        return $this->key === null && $this->keyPrefix === null;
    }

    /**
     * @return string|null The item key, or null when the selector is not for a single key.
     */
    public function key(): ?string
    {
        return $this->key;
    }

    /**
     * @return string|null The item key prefix, or null when the selector is not for a key prefix.
     */
    public function keyPrefix(): ?string
    {
        return $this->keyPrefix;
    }
}
