<?php
declare(strict_types=1);

namespace Momento\Config\Transport;

class StaticGrpcConfiguration implements IGrpcConfiguration
{

    private ?int $deadlineMilliseconds;
    private bool $forceNewChannel;
    private int $numGrpcChannels;

    public function __construct(?int $deadlineMilliseconds = null, bool $forceNewChannel = false, int $numGrpcChannels = 1)
    {
        $this->deadlineMilliseconds = $deadlineMilliseconds;
        $this->forceNewChannel = $forceNewChannel;
        $this->numGrpcChannels = $numGrpcChannels;
    }

    public function getDeadlineMilliseconds(): int|null
    {
        return $this->deadlineMilliseconds;
    }

    public function withDeadlineMilliseconds(int $deadlineMilliseconds): StaticGrpcConfiguration
    {
        return new StaticGrpcConfiguration($deadlineMilliseconds, $this->forceNewChannel, $this->numGrpcChannels);
    }

    public function getForceNewChannel(): bool|null
    {
        return $this->forceNewChannel;
    }

    public function withForceNewChannel(bool $forceNewChannel): StaticGrpcConfiguration
    {
        return new StaticGrpcConfiguration($this->deadlineMilliseconds, $forceNewChannel, $this->numGrpcChannels);
    }

    public function getNumGrpcChannels(): int
    {
        return $this->numGrpcChannels;
    }

    public function withNumGrpcChannels(int $numGrpcChannels): IGrpcConfiguration
    {
        return new StaticGrpcConfiguration($this->deadlineMilliseconds, $this->forceNewChannel, $numGrpcChannels);
    }
}
