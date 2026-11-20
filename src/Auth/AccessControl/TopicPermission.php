<?php
declare(strict_types=1);

namespace Momento\Auth\AccessControl;

class TopicPermission
{
    private string $role;
    private CacheSelector $cache;
    private TopicSelector $topic;

    /**
     * @param string $role One of the TopicRole constants.
     * @param CacheSelector $cache The cache or caches the permission applies to.
     * @param TopicSelector $topic The topic or topics the permission applies to.
     */
    public function __construct(string $role, CacheSelector $cache, TopicSelector $topic)
    {
        $this->role = $role;
        $this->cache = $cache;
        $this->topic = $topic;
    }

    public function role(): string
    {
        return $this->role;
    }

    public function cache(): CacheSelector
    {
        return $this->cache;
    }

    public function topic(): TopicSelector
    {
        return $this->topic;
    }
}
