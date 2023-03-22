<?php
declare(strict_types=1);

namespace Momento\Cache\Interceptors;

use Grpc\Interceptor;
use Momento\Config\Retry\IRetryStrategy;
use Momento\Utilities\_ErrorConverter;

class RetryInterceptor extends Interceptor
{
    private IRetryStrategy $retryStrategy;

    public function __construct(IRetryStrategy $retryStrategy) {
        $this->retryStrategy = $retryStrategy;
    }

    public function interceptUnaryUnary(
        $method, $argument, $deserialize, $continuation, array $metadata = [], array $options = []
    ) {
        $attempts = 0;
        while (true) {
            $attempts++;
            $call = $continuation($method, $argument, $deserialize, $metadata, $options);
            // This is the only way AFAIK to get the success or error status of the call.
            // However, you can only call wait() on a call once, so we have basically
            // consumed the response and status data in this context. Returning the call is
            // useless, so I don't see any alternative to returning the response when we
            // are successful and throwing an exception when we're not.
            //
            // Unfortunately thus returns the wrong type from calls to ScsClient. Fortunately
            // PHP doesn't actually care, and the responses are processed successfully downstream.
            // This also means that if we remove the RetryInterceptor, we'll either need to add
            // a replacement that extracts the response in the same way or go back to calling
            // `call->wait()` in the ScsDataClient and handling statuses and responses there.
            [$response, $status] = $call->wait();

            if ($status->code == 0) {
                // We're good!
                return $response;
            }
            $sleepMillis = $this->retryStrategy->determineWhenToRetry($status->code, $method, $attempts);
            if ($sleepMillis !== null) {
                usleep($sleepMillis * 1000);
            } else {
                throw _ErrorConverter::convert($status->code, $status->details, $metadata);
            }
        }
    }
}
