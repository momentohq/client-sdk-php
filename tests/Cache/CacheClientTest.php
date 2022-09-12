<?php
namespace Momento\Tests\Cache;

use Momento\Cache\CacheOperationTypes\CacheGetResponse;
use Momento\Cache\CacheOperationTypes\CacheGetStatus;
use Momento\Cache\Errors\AlreadyExistsError;
use RuntimeException;
use Momento\Cache\SimpleCacheClient;
use PHPUnit\Framework\TestCase;

/**
 * @covers SimpleCacheClient
 */
class CacheClientTest extends TestCase
{
    private string $TEST_CACHE_NAME;
    private string $BAD_AUTH_TOKEN = "eyJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJpbnRlZ3JhdGlvbiIsImNwIjoiY29udHJvbC5jZWxsLWFscGhhLWRldi5wcmVwcm9kLmEubW9tZW50b2hxLmNvbSIsImMiOiJjYWNoZS5jZWxsLWFscGhhLWRldi5wcmVwcm9kLmEubW9tZW50b2hxLmNvbSJ9.gdghdjjfjyehhdkkkskskmmls76573jnajhjjjhjdhnndy";
    private int $DEFAULT_TTL_SECONDS = 60;
    private SimpleCacheClient $client;

    public function setUp() : void
    {
        $AUTH_TOKEN = getenv("TEST_AUTH_TOKEN");
        if (!$AUTH_TOKEN) {
            throw new RuntimeException(
                "Integration tests require TEST_AUTH_TOKEN env var; see README for more details."
            );
        }
        $this->TEST_CACHE_NAME = getenv("TEST_CACHE_NAME");
        if (!$this->TEST_CACHE_NAME) {
            throw new RuntimeException(
                "Integration tests require TEST_CACHE_NAME env var; see README for more details."
            );
        }
        $this->client = new SimpleCacheClient($AUTH_TOKEN, $this->DEFAULT_TTL_SECONDS);

        // Ensure test cache exists
        try {
            $this->client->createCache($this->TEST_CACHE_NAME);
        } catch (AlreadyExistsError $e) {}
    }

    public function testCreateSetGetDelete() {
        $cacheName = uniqid();
        $key = uniqid();
        $value = uniqid();
        $this->client->createCache($cacheName);
        $this->client->set($cacheName, $key, $value);
        $response = $this->client->get($cacheName, $key);
        $this->assertEquals($response->value(), $value);
        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        // TODO: Ok, this is most certainly not right ;-)
        $this->assertEquals('MISS', $response->status());
        $this->client->deleteCache($cacheName);
    }


}
