<?php
declare(strict_types=1);

namespace Momento\Requests;

/**
 * Represents the desired behavior for managing the TTL on collection
 * objects (dictionaries, lists, sets) in your cache.
 *
 * For cache operations that modify a collection, there are a few things
 * to consider.  The first time the collection is created, we need to
 * set a TTL on it.  For subsequent operations that modify the collection
 * you may choose to update the TTL in order to prolong the life of the
 * cached collection object, or you may choose to leave the TTL unmodified
 * in order to ensure that the collection expires at the original TTL.
 *
 * The default behavior is to refresh the TTL (to prolong the life of the
 * collection) each time it is written.  This behavior can be modified
 * by calling withNoRefreshTtlOnUpdates.
 */
class CollectionTtl
{
    private $ttlSeconds;
    private ?bool $refreshTtl;

    /**
     * @param int|float|null $ttlSeconds The number of seconds after which to expire the collection from the cache.
     * @param bool|null $refreshTtl If true, the collection's TTL will be refreshed (to prolong the life of the collection) on every update.  If false, the collection's TTL will only be set when the collection is initially created.
     */
    public function __construct($ttlSeconds = null, ?bool $refreshTtl = true)
    {
        $this->ttlSeconds = $ttlSeconds;
        $this->refreshTtl = $refreshTtl;
    }

    /**
     * The default way to handle TTLs for collections.  The default TTL
     * that was specified when constructing the CacheClient
     * will be used, and the TTL for the collection will be refreshed any
     * time the collection is modified.
     *
     * @return CollectionTtl
     */
    public static function fromCacheTtl(): CollectionTtl
    {
        return new CollectionTtl(null, true);
    }

    /**
     * Constructs a CollectionTtl with the specified TTL in seconds.  The TTL for the collection
     * will be refreshed any time the collection is modified.
     *
     * @param int|float $ttlSeconds
     * @return CollectionTtl
     */
    public static function of($ttlSeconds): CollectionTtl
    {
        return new CollectionTtl($ttlSeconds);
    }

    /**
     * @return int|null The current value for TTL in seconds.
     */
    public function getTtl()
    {
        return $this->ttlSeconds;
    }

    /**
     * @return bool|null The current value for whether or not to refresh the TTL for a collection when it is modified.
     */
    public function getRefreshTtl(): ?bool
    {
        return $this->refreshTtl;
    }

    /**
     * Specifies that the TTL for the collection should be refreshed when the collection is modified.  (This is the default behavior.)
     *
     * @return CollectionTtl
     */
    public function withRefreshTtlOnUpdates(): CollectionTtl
    {
        return new CollectionTtl($this->ttlSeconds, $this->refreshTtl);
    }

    /**
     * Specifies that the TTL for the collection should not be refreshed when the collection is modified.
     * Use this if you want to ensure that your collection expires at the originally specified time, even
     * if you make modifications to the value of the collection.
     *
     * @return CollectionTtl
     */
    public function withNoRefreshTtlOnUpdates(): CollectionTtl
    {
        return new CollectionTtl($this->ttlSeconds, false);
    }
}
