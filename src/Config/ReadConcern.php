<?php
declare(strict_types=1);

namespace Momento\Config;

abstract class ReadConcern
{
    /**
     * BALANCED is the default read concern for the cache client.
     */
    public const BALANCED = "BALANCED";
    /**
     * CONSISTENT read concern guarantees read after write consistency.
     */
    public const CONSISTENT = "CONSISTENT";
}
