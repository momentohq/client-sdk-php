<?php
declare(strict_types=1);

namespace Momento\Auth\AccessControl;

/**
 * The set of permissions granted to a disposable token.
 */
class DisposableTokenScope
{
    /**
     * @var array<CachePermission|TopicPermission>
     */
    private array $permissions;

    /**
     * @param array<CachePermission|TopicPermission> $permissions
     */
    public function __construct(array $permissions)
    {
        $this->permissions = $permissions;
    }

    /**
     * @return array<CachePermission|TopicPermission>
     */
    public function permissions(): array
    {
        return $this->permissions;
    }
}
