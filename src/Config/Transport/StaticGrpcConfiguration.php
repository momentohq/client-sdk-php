<?php
declare(strict_types=1);

namespace Momento\Config\Transport;

class StaticGrpcConfiguration implements IGrpcConfiguration
{

    private ?int $deadlineMilliseconds;
    private bool $forceNewChannel;
    private int $numGrpcChannels;
    private ?int $keepAlivePermitWithoutCalls;
    private ?int $keepAliveTimeoutMS;
    private ?int $keepAliveTimeMS;

    public function __construct(
        ?int $deadlineMilliseconds = null,
        bool $forceNewChannel = false,
        int $numGrpcChannels = 1,
        ?int $keepAlivePermitWithoutCalls = 0,
        ?int $keepAliveTimeoutMS = null,
        ?int $keepAliveTimeMS = null
    ) {
        $this->deadlineMilliseconds = $deadlineMilliseconds;
        $this->forceNewChannel = $forceNewChannel;
        $this->numGrpcChannels = $numGrpcChannels;
        $this->keepAlivePermitWithoutCalls = $keepAlivePermitWithoutCalls;
        $this->keepAliveTimeoutMS = $keepAliveTimeoutMS;
        $this->keepAliveTimeMS = $keepAliveTimeMS;
    }

    public function getKeepAlivePermitWithoutCalls(): ?int
    {
        return $this->keepAlivePermitWithoutCalls;
    }

    public function getKeepAliveTimeoutMS(): ?int
    {
        return $this->keepAliveTimeoutMS;
    }

    public function getKeepAliveTimeMS(): ?int
    {
        return $this->keepAliveTimeMS;
    }

    public function getDeadlineMilliseconds(): ?int
    {
        return $this->deadlineMilliseconds;
    }

    public function withDeadlineMilliseconds(int $deadlineMilliseconds): StaticGrpcConfiguration
    {
        return new StaticGrpcConfiguration($deadlineMilliseconds, $this->forceNewChannel, $this->numGrpcChannels);
    }

    public function getForceNewChannel(): ?bool
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
