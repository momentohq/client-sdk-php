<?php
declare(strict_types=1);

namespace Momento\Auth\AccessControl;

/**
 * Convenience constructors for common single-permission disposable token scopes.
 * Caches and topics may be given by name or as a selector.
 */
abstract class DisposableTokenScopes
{
    /**
     * @param CacheSelector|string $cache
     */
    public static function cacheReadWrite($cache): DisposableTokenScope
    {
        return self::cache(CacheRole::READ_WRITE, $cache, null);
    }

    /**
     * @param CacheSelector|string $cache
     */
    public static function cacheReadOnly($cache): DisposableTokenScope
    {
        return self::cache(CacheRole::READ_ONLY, $cache, null);
    }

    /**
     * @param CacheSelector|string $cache
     */
    public static function cacheWriteOnly($cache): DisposableTokenScope
    {
        return self::cache(CacheRole::WRITE_ONLY, $cache, null);
    }

    /**
     * @param CacheSelector|string $cache
     */
    public static function cacheKeyReadWrite($cache, string $key): DisposableTokenScope
    {
        return self::cache(CacheRole::READ_WRITE, $cache, CacheItemSelector::byKey($key));
    }

    /**
     * @param CacheSelector|string $cache
     */
    public static function cacheKeyReadOnly($cache, string $key): DisposableTokenScope
    {
        return self::cache(CacheRole::READ_ONLY, $cache, CacheItemSelector::byKey($key));
    }

    /**
     * @param CacheSelector|string $cache
     */
    public static function cacheKeyWriteOnly($cache, string $key): DisposableTokenScope
    {
        return self::cache(CacheRole::WRITE_ONLY, $cache, CacheItemSelector::byKey($key));
    }

    /**
     * @param CacheSelector|string $cache
     */
    public static function cacheKeyPrefixReadWrite($cache, string $keyPrefix): DisposableTokenScope
    {
        return self::cache(CacheRole::READ_WRITE, $cache, CacheItemSelector::byKeyPrefix($keyPrefix));
    }

    /**
     * @param CacheSelector|string $cache
     */
    public static function cacheKeyPrefixReadOnly($cache, string $keyPrefix): DisposableTokenScope
    {
        return self::cache(CacheRole::READ_ONLY, $cache, CacheItemSelector::byKeyPrefix($keyPrefix));
    }

    /**
     * @param CacheSelector|string $cache
     */
    public static function cacheKeyPrefixWriteOnly($cache, string $keyPrefix): DisposableTokenScope
    {
        return self::cache(CacheRole::WRITE_ONLY, $cache, CacheItemSelector::byKeyPrefix($keyPrefix));
    }

    /**
     * @param CacheSelector|string $cache
     * @param TopicSelector|string $topic
     */
    public static function topicPublishSubscribe($cache, $topic): DisposableTokenScope
    {
        return self::topic(TopicRole::PUBLISH_SUBSCRIBE, $cache, $topic);
    }

    /**
     * @param CacheSelector|string $cache
     * @param TopicSelector|string $topic
     */
    public static function topicSubscribeOnly($cache, $topic): DisposableTokenScope
    {
        return self::topic(TopicRole::SUBSCRIBE_ONLY, $cache, $topic);
    }

    /**
     * @param CacheSelector|string $cache
     * @param TopicSelector|string $topic
     */
    public static function topicPublishOnly($cache, $topic): DisposableTokenScope
    {
        return self::topic(TopicRole::PUBLISH_ONLY, $cache, $topic);
    }

    /**
     * @param CacheSelector|string $cache
     */
    private static function cache(string $role, $cache, ?CacheItemSelector $item): DisposableTokenScope
    {
        return new DisposableTokenScope([new CachePermission($role, self::cacheSelector($cache), $item)]);
    }

    /**
     * @param CacheSelector|string $cache
     * @param TopicSelector|string $topic
     */
    private static function topic(string $role, $cache, $topic): DisposableTokenScope
    {
        $topic = $topic instanceof TopicSelector ? $topic : TopicSelector::byName($topic);

        return new DisposableTokenScope([new TopicPermission($role, self::cacheSelector($cache), $topic)]);
    }

    /**
     * @param CacheSelector|string $cache
     */
    private static function cacheSelector($cache): CacheSelector
    {
        return $cache instanceof CacheSelector ? $cache : CacheSelector::byName($cache);
    }
}
