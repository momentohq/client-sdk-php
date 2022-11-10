<?php
declare(strict_types=1);

namespace Momento\Config\Transport;

interface IGrpcConfiguration
{
    public function getDeadlineMilliseconds(): ?int;

    public function withDeadlineMilliseconds(int $deadline): IGrpcConfiguration;
}