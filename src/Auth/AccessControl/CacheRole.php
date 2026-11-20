<?php
declare(strict_types=1);

namespace Momento\Auth\AccessControl;

abstract class CacheRole
{
    public const READ_WRITE = "readwrite";
    public const READ_ONLY = "readonly";
    public const WRITE_ONLY = "writeonly";
}
