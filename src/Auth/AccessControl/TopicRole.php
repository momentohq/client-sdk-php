<?php
declare(strict_types=1);

namespace Momento\Auth\AccessControl;

abstract class TopicRole
{
    public const PUBLISH_SUBSCRIBE = "publishsubscribe";
    public const SUBSCRIBE_ONLY = "subscribeonly";
    public const PUBLISH_ONLY = "publishonly";
}
