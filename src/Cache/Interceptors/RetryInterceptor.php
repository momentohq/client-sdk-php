<?php
declare(strict_types=1);

namespace Momento\Cache\Interceptors;

use Grpc\Interceptor;
use Momento\Config\Retry\IRetryStrategy;

class RetryInterceptor extends Interceptor
{
    private IRetryStrategy $retryStrategy;

    public function __construct(IRetryStrategy $retryStrategy) {
        $this->retryStrategy = $retryStrategy;
    }

    public function interceptUnaryUnary($method, $argument, $deserialize, $continuation, array $metadata = [], array $options = [])
    {
        $retries = 3;
        $sleepMicroseconds = 250000;
        while ($retries) {
            $call = $continuation($method, $argument, $deserialize, $metadata, $options);
            [$response, $status] = $call->wait();
            if ($status->code == 0) {
                // We're good!
                break;
            }
            $retries--;
            usleep($sleepMicroseconds);
        }
        return parent::interceptUnaryUnary($method, $argument, $deserialize, $continuation, $metadata, $options);
    }
}
