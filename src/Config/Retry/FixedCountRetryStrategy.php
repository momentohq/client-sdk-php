<?php
declare(strict_types=1);

namespace Momento\Config\Retry;

use Psr\Log\LoggerInterface;

class FixedCountRetryStrategy implements IRetryStrategy {

    private IEligibilityStrategy $eligibilityStrategy;
    private int $maxAttempts;
    private LoggerInterface $log;

    public function __construct(IEligibilityStrategy $eligibilityStrategy, int $maxAttempts, LoggerInterface $log) {
        $this->eligibilityStrategy = $eligibilityStrategy;
        $this->maxAttempts = $maxAttempts;
        $this->log = $log;
    }

    public function withMaxAttempts(int $maxAttempts): IRetryStrategy {
        return new FixedCountRetryStrategy($this->eligibilityStrategy, $maxAttempts, $this->log);
    }

    public function withEligibilityStrategy(IEligibilityStrategy $eligibilityStrategy): IRetryStrategy {
        return new FixedCountRetryStrategy($eligibilityStrategy, $this->maxAttempts, $this->log);
    }

    public function determineWhenToRetry(int $grpcStatusCode, string $grpcMethod, int $attemptNumber): int|null
    {
        // TODO: Add logging
        if (!$this->eligibilityStrategy->isEligibleForRetry($grpcStatusCode, $grpcMethod, $attemptNumber)) {
            return null;
        }
        if ($attemptNumber > $this->maxAttempts) {
            return null;
        }

        // Return value is time until next retry, which is currently 0.
        return 0;
    }
}
