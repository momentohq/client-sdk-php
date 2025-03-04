<?php
declare(strict_types=1);

namespace Momento\Config\Transport;

interface IGrpcConfiguration
{
    public function getDeadlineMilliseconds(): ?int;

    public function withDeadlineMilliseconds(int $deadlineMilliseconds): IGrpcConfiguration;

    public function getForceNewChannel(): ?bool;

    public function withForceNewChannel(bool $forceNewChannel) : IGrpcConfiguration;

    public function getNumGrpcChannels(): int;

    public function withNumGrpcChannels(int $numGrpcChannels): IGrpcConfiguration;

    public function getKeepAlivePermitWithoutCalls(): ?int;

    public function getKeepAliveTimeoutMS(): ?int;

    public function getKeepAliveTimeMS(): ?int;

}
