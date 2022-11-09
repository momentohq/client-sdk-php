<?php
declare(strict_types=1);

namespace Momento\Config\Transport;

class StaticGrpcConfiguration implements IGrpcConfiguration
{

    private ?int $deadline;

    public function __construct(?int $deadline = null)
    {
        $this->deadline = $deadline;
    }

    public function getDeadline(): int|null
    {
        return $this->deadline;
    }

    public function withDeadline(int $deadline): StaticGrpcConfiguration
    {
        return new StaticGrpcConfiguration($deadline);
    }
}