<?php
declare(strict_types=1);

namespace Momento\Config\Transport;

class StaticGrpcConfiguration implements IGrpcConfiguration
{

    private ?int $deadlineMilliseconds;
    private ?bool $forceNewChannel;

    public function __construct(?int $deadlineMilliseconds = null, ?bool $forceNewChannel = false)
    {
        $this->deadlineMilliseconds = $deadlineMilliseconds;
        $this->forceNewChannel = $forceNewChannel;
    }

    public function getDeadlineMilliseconds(): int|null
    {
        return $this->deadlineMilliseconds;
    }

    public function withDeadlineMilliseconds(int $deadlineMilliseconds): StaticGrpcConfiguration
    {
        return new StaticGrpcConfiguration($deadlineMilliseconds, $this->forceNewChannel);
    }

    public function getForceNewChannel(): bool|null
    {
        return $this->forceNewChannel;
    }

    public function withForceNewChannel(bool $forceNewChannel): StaticGrpcConfiguration
    {
        return new StaticGrpcConfiguration($this->deadlineMilliseconds, $forceNewChannel);
    }
}
