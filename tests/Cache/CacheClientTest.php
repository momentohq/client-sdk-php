<?php
namespace Momento\Tests\Cache;
use Momento\Cache\CacheClient;
use PHPUnit\Framework\TestCase;

/**
 */
class CacheClientTest extends TestCase
{
    public function testCanUseBucketEndpoint()
    {
        $c = new CacheClient("", 100);
        $this->assertSame(
            'Hello',
            $c->foo()
        );
    }
}
