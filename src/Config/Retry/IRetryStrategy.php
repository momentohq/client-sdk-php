<?php
declare(strict_types=1);

namespace Momento\Config\Retry;

interface IRetryStrategy {
    public function determineWhenToRetry(int $grpcStatusCode, string $grpcMethod, int $attemptNumber): int|null;
}
