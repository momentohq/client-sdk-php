<?php
declare(strict_types=1);

namespace Momento\Config\Transport;

class StaticGrpcConfiguration implements IGrpcConfiguration
{

    private ?int $deadlineMilliseconds;
    private bool $forceNew = false;

    public function __construct(?int $deadlineMilliseconds = null, ?bool $forceNew = null)
    {
        $this->deadlineMilliseconds = $deadlineMilliseconds;
        if (!is_null($forceNew)) {
            $this->forceNew = $forceNew;
        }
    }

    public function getDeadlineMilliseconds(): int|null
    {
        return $this->deadlineMilliseconds;
    }

    public function withDeadlineMilliseconds(int $deadlineMilliseconds): StaticGrpcConfiguration
    {
        return new StaticGrpcConfiguration($deadlineMilliseconds);
    }

    public function getForceNew(): bool
    {
        return $this->forceNew;
    }

    public function withForceNew(bool $forceNew): IGrpcConfiguration
    {
        return new StaticGrpcConfiguration($this->deadlineMilliseconds, $forceNew);
    }

}
