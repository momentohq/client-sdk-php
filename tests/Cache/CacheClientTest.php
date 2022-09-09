<?php
namespace Momento\Tests\Cache;
use Momento\Cache\CacheClient;
use PHPUnit\Framework\TestCase;

/**
 * @covers CacheClient
 */
class CacheClientTest extends TestCase
{
    public function testCanUseBucketEndpoint()
    {
        $c = new CacheClient(getenv("MOMENTO_AUTH_TOKEN"), 100);
        $this->assertSame(
            'Hello',
            $c->foo()
        );
    }
}
