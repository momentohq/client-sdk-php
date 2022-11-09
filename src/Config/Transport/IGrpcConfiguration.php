<?php
declare(strict_types=1);

namespace Momento\Config\Transport;

interface IGrpcConfiguration
{
    public function getDeadline(): ?int;

    public function withDeadline(int $deadline): IGrpcConfiguration;
}