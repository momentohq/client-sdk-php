<?php
declare(strict_types=1);

namespace Momento\Config\Retry;

interface IEligibilityStrategy {
    public function isEligibleForRetry(int $grpcCode, string $method, int $attemptNumber) : bool;
}
