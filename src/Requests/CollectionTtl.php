<?php
declare(strict_types=1);

namespace Momento\Requests;

class CollectionTtl
{
    private ?int $ttl;
    private ?bool $refreshTtl;

    public function __construct(?int $ttl = null, ?bool $refreshTtl = true)
    {
        $this->ttl = $ttl;
        $this->refreshTtl = $refreshTtl;
    }

    public static function fromCacheTtl(): CollectionTtl
    {
        return new CollectionTtl(null, true);
    }

    public static function of(int $ttl): CollectionTtl
    {
        return new CollectionTtl($ttl);
    }

    public function getTtl(): int|null
    {
        return $this->ttl;
    }

    public function getRefreshTtl(): bool|null
    {
        return $this->refreshTtl;
    }

    public function withRefreshTtlOnUpdates(): CollectionTtl
    {
        return new CollectionTtl($this->ttl, $this->refreshTtl);
    }

    public function withNoRefreshTtlOnUpdates(): CollectionTtl
    {
        return new CollectionTtl($this->ttl, false);
    }
}
