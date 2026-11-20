<?php
declare(strict_types=1);

namespace Momento\Auth\AccessControl;

class CachePermission
{
    private string $role;
    private CacheSelector $cache;
    private ?CacheItemSelector $item;

    /**
     * @param string $role One of the CacheRole constants.
     * @param CacheSelector $cache The cache or caches the permission applies to.
     * @param CacheItemSelector|null $item Restricts the permission to the selected cache items.
     */
    public function __construct(string $role, CacheSelector $cache, ?CacheItemSelector $item = null)
    {
        $this->role = $role;
        $this->cache = $cache;
        $this->item = $item;
    }

    public function role(): string
    {
        return $this->role;
    }

    public function cache(): CacheSelector
    {
        return $this->cache;
    }

    public function item(): ?CacheItemSelector
    {
        return $this->item;
    }
}
