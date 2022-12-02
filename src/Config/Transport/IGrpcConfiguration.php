<?php
declare(strict_types=1);

namespace Momento\Config\Transport;

interface IGrpcConfiguration
{
    public function getDeadlineMilliseconds(): ?int;

    public function getForceNew(): ?bool;

    public function withDeadlineMilliseconds(int $deadlineMilliseconds): IGrpcConfiguration;

    public function withForceNew(bool $forceNew): IGrpcConfiguration;
}
