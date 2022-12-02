<?php
declare(strict_types=1);

namespace Momento\Requests;

class CollectionTtl
{
    private ?int $ttlSeconds;
    private ?bool $refreshTtl;

    public function __construct(?int $ttlSeconds = null, ?bool $refreshTtl = true)
    {
        $this->ttlSeconds = $ttlSeconds;
        $this->refreshTtl = $refreshTtl;
    }

    public static function fromCacheTtl(): CollectionTtl
    {
        return new CollectionTtl(null, true);
    }

    public static function of(int $ttlSeconds): CollectionTtl
    {
        return new CollectionTtl($ttlSeconds);
    }

    public function getTtl(): int|null
    {
        return $this->ttlSeconds;
    }

    public function getRefreshTtl(): bool|null
    {
        return $this->refreshTtl;
    }

    public function withRefreshTtlOnUpdates(): CollectionTtl
    {
        return new CollectionTtl($this->ttlSeconds, $this->refreshTtl);
    }

    public function withNoRefreshTtlOnUpdates(): CollectionTtl
    {
        return new CollectionTtl($this->ttlSeconds, false);
    }
}
