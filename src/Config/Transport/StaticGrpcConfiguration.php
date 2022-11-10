<?php
declare(strict_types=1);

namespace Momento\Config\Transport;

class StaticGrpcConfiguration implements IGrpcConfiguration
{

    private ?int $deadlineMilliseconds;

    public function __construct(?int $deadlineMilliseconds = null)
    {
        $this->deadlineMilliseconds = $deadlineMilliseconds;
    }

    public function getDeadlineMilliseconds(): int|null
    {
        return $this->deadlineMilliseconds;
    }

    public function withDeadlineMilliseconds(int $deadlineMilliseconds): StaticGrpcConfiguration
    {
        return new StaticGrpcConfiguration($deadlineMilliseconds);
    }
}
