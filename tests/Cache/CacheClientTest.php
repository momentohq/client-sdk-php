<?php
namespace Momento\Tests\Cache;

use Momento\Auth\AuthUtils;
use Momento\Auth\EnvMomentoTokenProvider;
use Momento\Cache\CacheOperationTypes\CacheGetStatus;
use Momento\Cache\Errors\AlreadyExistsError;
use Momento\Cache\Errors\AuthenticationError;
use Momento\Cache\Errors\BadRequestError;
use Momento\Cache\Errors\InvalidArgumentError;
use Momento\Cache\Errors\NotFoundError;
use Momento\Cache\Errors\TimeoutError;
use RuntimeException;
use Momento\Cache\SimpleCacheClient;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * @covers SimpleCacheClient
 */
class CacheClientTest extends TestCase
{
    private EnvMomentoTokenProvider $authProvider;
    private string $TEST_CACHE_NAME;
    private string $BAD_AUTH_TOKEN = "eyJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJpbnRlZ3JhdGlvbiIsImNwIjoiY29udHJvbC5jZWxsLWFscGhhLWRldi5wcmVwcm9kLmEubW9tZW50b2hxLmNvbSIsImMiOiJjYWNoZS5jZWxsLWFscGhhLWRldi5wcmVwcm9kLmEubW9tZW50b2hxLmNvbSJ9.gdghdjjfjyehhdkkkskskmmls76573jnajhjjjhjdhnndy";
    private int $DEFAULT_TTL_SECONDS = 60;
    private SimpleCacheClient $client;

    public function setUp() : void
    {
        $this->authProvider = new EnvMomentoTokenProvider("TEST_AUTH_TOKEN");

        $this->TEST_CACHE_NAME = getenv("TEST_CACHE_NAME");
        if (!$this->TEST_CACHE_NAME) {
            throw new RuntimeException(
                "Integration tests require TEST_CACHE_NAME env var; see README for more details."
            );
        }
        $this->client = new SimpleCacheClient($this->authProvider, $this->DEFAULT_TTL_SECONDS);

        // Ensure test cache exists
        try {
            $this->client->createCache($this->TEST_CACHE_NAME);
        } catch (AlreadyExistsError $e) {}
    }

    private function getBadAuthTokenClient() : SimpleCacheClient
    {
        $badEnvName = "_MOMENTO_BAD_AUTH_TOKEN";
        putenv("{$badEnvName}={$this->BAD_AUTH_TOKEN}");
        $authProvider = new EnvMomentoTokenProvider($badEnvName);
        putenv($badEnvName);
        return new SimpleCacheClient($authProvider, $this->DEFAULT_TTL_SECONDS);
    }

    // Happy path test
    public function testCreateSetGetDelete() {
        $cacheName = uniqid();
        $key = uniqid();
        $value = uniqid();
        $this->client->createCache($cacheName);
        $this->client->set($cacheName, $key, $value);
        $response = $this->client->get($cacheName, $key);
        $this->assertEquals($response->value(), $value);
        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertEquals('MISS', $response->status());
        $this->client->deleteCache($cacheName);
    }

    // Client initialization tests
    public function testNegativeDefaultTtl() {
        $this->expectExceptionMessage("TTL Seconds must be a non-negative integer");
        $client = new SimpleCacheClient($this->authProvider, -1);
    }

    public function testNonJwtTokens() {
        $AUTH_TOKEN = "notanauthtoken";
        $this->expectExceptionMessage("Invalid Momento auth token.");
        AuthUtils::parseAuthToken($AUTH_TOKEN);
        $AUTH_TOKEN = "not.anauth.token";
        $this->expectExceptionMessage("Invalid Momento auth token.");
        AuthUtils::parseAuthToken($AUTH_TOKEN);
    }

    public function testNegativeRequestTimeout() {
        $this->expectExceptionMessage("Request timeout must be greater than zero.");
        $client = new SimpleCacheClient($this->authProvider, $this->DEFAULT_TTL_SECONDS, -1);
    }

    public function testZeroRequestTimeout() {
        $this->expectExceptionMessage("Request timeout must be greater than zero.");
        $client = new SimpleCacheClient($this->authProvider, $this->DEFAULT_TTL_SECONDS, 0);
    }

    // Create cache tests
    public function testCreateCacheAlreadyExists() {
        $this->expectException(AlreadyExistsError::class);
        $this->client->createCache($this->TEST_CACHE_NAME);
    }

    public function testCreateCacheEmptyName()
    {
        $this->expectException(InvalidArgumentError::class);
        $this->client->createCache("");
    }

    public function testCreateCacheNullName() {
        $this->expectException(\TypeError::class);
        $this->client->createCache(null);
    }

    public function testCreateCacheBadName() {
        $this->expectException(BadRequestError::class);
        $this->client->createCache(1);
    }

    public function testCreateCacheBadAuth() {
        $client = $this->getBadAuthTokenClient();
        $this->expectException(AuthenticationError::class);
        $client->createCache(uniqid());
    }

    // Delete cache tests
    public function testDeleteCacheSucceeds() {
        $cacheName = uniqid();
        $this->client->createCache($cacheName);
        $this->client->deleteCache($cacheName);
        $this->expectException(NotFoundError::class);
        $this->client->deleteCache($cacheName);
    }

    public function testDeleteUnknownCache() {
        $cacheName = uniqid();
        $this->expectException(NotFoundError::class);
        $this->client->deleteCache($cacheName);
    }

    public function testDeleteNullCacheName() {
        $this->expectException(\TypeError::class);
        $this->client->deleteCache(null);
    }

    public function testDeleteEmptyCacheName() {
        $this->expectException(InvalidArgumentError::class);
        $this->client->deleteCache("");
    }

    public function testDeleteCacheBadAuth() {
        $client = $this->getBadAuthTokenClient();
        $this->expectException(AuthenticationError::class);
        $client->deleteCache(uniqid());
    }

    // List caches tests
    public function testListCaches() {
        $cacheName = uniqid();
        $caches = $this->client->listCaches()->caches();
        $cacheNames = array_map(fn($i) => $i->name(), $caches);
        $this->assertNotContains($cacheName, $cacheNames);
        try {
            $this->client->createCache($cacheName);
            $listCachesResp = $this->client->listCaches();
            $caches = $listCachesResp->caches();
            $cacheNames = array_map(fn($i) => $i->name(), $caches);
            $this->assertContains($cacheName, $cacheNames);
            $this->assertEquals(null, $listCachesResp->nextToken());
        } finally {
            $this->client->deleteCache($cacheName);
        }
    }

    public function testListCachesBadAuth() {
        $client = $this->getBadAuthTokenClient();
        $this->expectException(AuthenticationError::class);
        $client->listCaches();
    }

    public function testListCachesNextToken() {
        $this->markTestSkipped("pagination not yet implemented");
    }

    // Setting and getting tests
    public function testCacheHit() {
        $key = uniqid();
        $value = uniqid();

        $setResp = $this->client->set($this->TEST_CACHE_NAME, $key, $value);
        $this->assertEquals($value, $setResp->value());
        $this->assertEquals($key, $setResp->key());

        $getResp = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertEquals(CacheGetStatus::HIT->name, $getResp->status());
        $this->assertEquals($value, $getResp->value());
    }

    public function testGetMiss() {
        $key = uniqid();
        $getResp = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertEquals(CacheGetStatus::MISS->name, $getResp->status());
        $this->assertEquals(null, $getResp->value());
    }

    public function testExpiresAfterTtl() {
        $key = uniqid();
        $value = uniqid();
        $client = new SimpleCacheClient($this->authProvider, 2);
        $client->set($this->TEST_CACHE_NAME, $key, $value);
        $this->assertEquals(CacheGetStatus::HIT->name, $client->get($this->TEST_CACHE_NAME, $key)->status());
        sleep(4);
        $this->assertEquals(CacheGetStatus::MISS->name, $client->get($this->TEST_CACHE_NAME, $key)->status());
    }

    public function testSetWithDifferentTtls() {
        $key1 = uniqid();
        $key2 = uniqid();
        $this->client->set($this->TEST_CACHE_NAME, $key1, "1", 2);
        $this->client->set($this->TEST_CACHE_NAME, $key2, "2");
        $this->assertEquals(
            CacheGetStatus::HIT->name,
            $this->client->get($this->TEST_CACHE_NAME, $key1)->status()
        );
        $this->assertEquals(
            CacheGetStatus::HIT->name,
            $this->client->get($this->TEST_CACHE_NAME, $key2)->status()
        );

        sleep(4);

        $this->assertEquals(
            CacheGetStatus::MISS->name,
            $this->client->get($this->TEST_CACHE_NAME, $key1)->status()
        );
        $this->assertEquals(
            CacheGetStatus::HIT->name,
            $this->client->get($this->TEST_CACHE_NAME, $key2)->status()
        );
    }

    // Set tests

    public function testSetWithNonexistentCache() {
        $cacheName = uniqid();
        $this->expectException(NotFoundError::class);
        $this->client->set($cacheName, "key", "value");
    }

    public function testSetWithNullCacheName() {
        $this->expectException(TypeError::class);
        $this->client->set(null, "key", "value");
    }

    public function testSetWithEmptyCacheName() {
        $this->expectException(InvalidArgumentError::class);
        $this->expectExceptionMessage("Cache name must be a non-empty string");
        $this->client->set("", "key", "value");
    }

    public function testSetWithNullKey() {
        $this->expectException(TypeError::class);
        $this->client->set($this->TEST_CACHE_NAME, null, "value");
    }

    public function testSetWithNullValue() {
        $this->expectException(TypeError::class);
        $this->client->set($this->TEST_CACHE_NAME, "key", null);
    }

    public function testSetNegativeTtl() {
        $this->expectException(InvalidArgumentError::class);
        $this->expectExceptionMessage("TTL Seconds must be a non-negative integer");
        $this->client->set($this->TEST_CACHE_NAME, "key", "value", -1);
    }

    public function testSetBadKey() {
        $this->expectException(TypeError::class);
        $this->client->set($this->TEST_CACHE_NAME, null, "bar");
    }

    public function testSetBadValue() {
        $this->expectException(TypeError::class);
        $this->client->set($this->TEST_CACHE_NAME, "foo", null);
    }

    public function testSetBadAuth() {
        $client = $this->getBadAuthTokenClient();
        $this->expectException(AuthenticationError::class);
        $client->set($this->TEST_CACHE_NAME, "foo", "bar");
        putenv($badEnvName);
    }

    // Get tests
    public function testGetNonexistentCache() {
        $cacheName = uniqid();
        $this->expectException(NotFoundError::class);
        $this->client->get($cacheName, "foo");
    }

    public function testGetNullCacheName() {
        $this->expectException(TypeError::class);
        $this->client->get(null, "foo");
    }

    public function testGetEmptyCacheName() {
        $this->expectException(InvalidArgumentError::class);
        $this->client->get("", "foo");
    }

    public function testGetNullKey() {
        $this->expectException(TypeError::class);
        $this->client->get($this->TEST_CACHE_NAME, null);
    }

    public function testGetBadAuth() {
        $client = $this->getBadAuthTokenClient();
        $this->expectException(AuthenticationError::class);
        $client->get($this->TEST_CACHE_NAME, "key");
    }

    public function testGetTimeout() {
        $client = new SimpleCacheClient($this->authProvider, $this->DEFAULT_TTL_SECONDS, 1);
        $this->expectException(TimeoutError::class);
        $client->get($this->TEST_CACHE_NAME, "key");
    }

    // Delete tests

    public function testDeleteNonexistentKey() {
        $key = "a key that isn't there";
        $this->assertEquals(CacheGetStatus::MISS->name, $this->client->get($this->TEST_CACHE_NAME, $key)->status());
        $this->client->delete($this->TEST_CACHE_NAME, $key);
        $this->assertEquals(CacheGetStatus::MISS->name, $this->client->get($this->TEST_CACHE_NAME, $key)->status());
    }

    public function testDelete() {
        $key = "key1";
        $this->assertEquals(CacheGetStatus::MISS->name, $this->client->get($this->TEST_CACHE_NAME, $key)->status());
        $this->client->set($this->TEST_CACHE_NAME, $key, "value");
        $this->assertEquals(CacheGetStatus::HIT->name, $this->client->get($this->TEST_CACHE_NAME, $key)->status());
        $this->client->delete($this->TEST_CACHE_NAME, $key);
        $this->assertEquals(CacheGetStatus::MISS->name, $this->client->get($this->TEST_CACHE_NAME, $key)->status());
    }

}
