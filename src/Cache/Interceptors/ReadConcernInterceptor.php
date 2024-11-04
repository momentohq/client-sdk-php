<?php
declare(strict_types=1);

namespace Momento\Cache\Interceptors;

use Grpc\Interceptor;
use Momento\Config\ReadConcern;

class ReadConcernInterceptor extends Interceptor
{
    private string $readConcern;

    public function __construct(string $readConcern)
    {
        $this->readConcern = $readConcern;
    }

    public function interceptUnaryUnary($method, $argument, $deserialize, $continuation, array $metadata = [], array $options = [])
    {
        if (!is_null($this->readConcern) && $this->readConcern !== ReadConcern::BALANCED) {
            $metadata["read-concern"] = [$this->readConcern];
        }
        return parent::interceptUnaryUnary($method, $argument, $deserialize, $continuation, $metadata, $options);
    }
}
