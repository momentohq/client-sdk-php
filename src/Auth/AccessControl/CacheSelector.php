<?php
declare(strict_types=1);

namespace Momento\Auth\AccessControl;

/**
 * Selects the cache or caches that a permission applies to.
 */
class CacheSelector
{
    private ?string $name;

    private function __construct(?string $name)
    {
        $this->name = $name;
    }

    public static function allCaches(): CacheSelector
    {
        return new CacheSelector(null);
    }

    public static function byName(string $name): CacheSelector
    {
        return new CacheSelector($name);
    }

    public function isAllCaches(): bool
    {
        return $this->name === null;
    }

    /**
     * @return string|null The cache name, or null when all caches are selected.
     */
    public function name(): ?string
    {
        return $this->name;
    }
}
