<?php
declare(strict_types=1);

namespace Momento\Tests\Cache;

use Momento\Auth\AuthUtils;
use Momento\Auth\EnvMomentoTokenProvider;
use Momento\Cache\Errors\MomentoErrorCode;
use Momento\Cache\SimpleCacheClient;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use TypeError;

/**
 * @covers SimpleCacheClient
 */
class CacheClientTest extends TestCase
{
    private EnvMomentoTokenProvider $authProvider;
    private string $TEST_CACHE_NAME;
    private string $BAD_AUTH_TOKEN = "eyJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJpbnRlZ3JhdGlvbiIsImNwIjoiY29udHJvbC5jZWxsLWFscGhhLWRldi5wcmVwcm9kLmEubW9tZW50b2hxLmNvbSIsImMiOiJjYWNoZS5jZWxsLWFscGhhLWRldi5wcmVwcm9kLmEubW9tZW50b2hxLmNvbSJ9.gdghdjjfjyehhdkkkskskmmls76573jnajhjjjhjdhnndy";
    private int $DEFAULT_TTL_SECONDS = 10;
    private SimpleCacheClient $client;

    public function setUp(): void
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
        $setUpCreateResponse = $this->client->createCache($this->TEST_CACHE_NAME);
        if ($setUpError = $setUpCreateResponse->asError()) {
            throw $setUpError->innerException();
        }
    }

    private function getBadAuthTokenClient(): SimpleCacheClient
    {
        $badEnvName = "_MOMENTO_BAD_AUTH_TOKEN";
        putenv("{$badEnvName}={$this->BAD_AUTH_TOKEN}");
        $authProvider = new EnvMomentoTokenProvider($badEnvName);
        putenv($badEnvName);
        return new SimpleCacheClient($authProvider, $this->DEFAULT_TTL_SECONDS);
    }

    // Happy path test

    public function testCreateSetGetDelete()
    {
        $cacheName = uniqid();
        $key = uniqid();
        $value = uniqid();
        $response = $this->client->createCache($cacheName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->set($cacheName, $key, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(get_class($response) . ": key $key = $value", "$response");

        $response = $this->client->get($cacheName, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $response = $response->asHit();
        $this->assertEquals($response->value(), $value);
        $this->assertEquals(get_class($response) . ": $value", "$response");

        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
        $response = $this->client->deleteCache($cacheName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
    }

    public function testNegativeDefaultTtl()
    {
        $this->expectExceptionMessage("TTL Seconds must be a non-negative integer");
        $client = new SimpleCacheClient($this->authProvider, -1);
    }

    // Client initialization tests

    public function testNonJwtTokens()
    {
        $AUTH_TOKEN = "notanauthtoken";
        $this->expectExceptionMessage("Invalid Momento auth token.");
        AuthUtils::parseAuthToken($AUTH_TOKEN);
        $AUTH_TOKEN = "not.anauth.token";
        $this->expectExceptionMessage("Invalid Momento auth token.");
        AuthUtils::parseAuthToken($AUTH_TOKEN);
    }

    public function testNegativeRequestTimeout()
    {
        $this->expectExceptionMessage("Request timeout must be greater than zero.");
        $client = new SimpleCacheClient($this->authProvider, $this->DEFAULT_TTL_SECONDS, -1);
    }

    public function testZeroRequestTimeout()
    {
        $this->expectExceptionMessage("Request timeout must be greater than zero.");
        $client = new SimpleCacheClient($this->authProvider, $this->DEFAULT_TTL_SECONDS, 0);
    }

    // Create cache tests

    public function testCreateCacheAlreadyExists()
    {
        $response = $this->client->createCache($this->TEST_CACHE_NAME);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asAlreadyExists());
    }

    public function testCreateCacheEmptyName()
    {
        $response = $this->client->createCache("");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $response = $response->asError();
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->errorCode());
        $this->assertEquals(get_class($response) . ": {$response->message()}", "$response");
    }

    public function testCreateCacheNullName()
    {
        $this->expectException(TypeError::class);
        $this->client->createCache(null);
    }

    public function testCreateCacheBadName()
    {
        $this->expectException(TypeError::class);
        $this->client->createCache(1);
    }

    public function testCreateCacheBadAuth()
    {
        $client = $this->getBadAuthTokenClient();
        $response = $client->createCache(uniqid());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::AUTHENTICATION_ERROR, $response->asError()->errorCode());
    }

    // Delete cache tests

    public function testDeleteCacheSucceeds()
    {
        $cacheName = uniqid();
        $response = $this->client->createCache($cacheName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->deleteCache($cacheName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->deleteCache($cacheName);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testDeleteUnknownCache()
    {
        $cacheName = uniqid();
        $response = $this->client->deleteCache($cacheName);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testDeleteNullCacheName()
    {
        $this->expectException(TypeError::class);
        $this->client->deleteCache(null);
    }

    public function testDeleteEmptyCacheName()
    {
        $response = $this->client->deleteCache("");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDeleteCacheBadAuth()
    {
        $client = $this->getBadAuthTokenClient();
        $response = $client->deleteCache(uniqid());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::AUTHENTICATION_ERROR, $response->asError()->errorCode());
    }

    // List caches tests
    public function testListCaches()
    {
        $cacheName = uniqid();
        $response = $this->client->listCaches();
        $this->assertNull($response->asError());
        $successResp = $response->asSuccess();
        $this->assertNotNull($successResp);
        $caches = $successResp->caches();
        $cacheNames = array_map(fn($i) => $i->name(), $caches);
        $this->assertNotContains($cacheName, $cacheNames);
        try {
            $response = $this->client->createCache($cacheName);
            $this->assertNull($response->asError());

            $listCachesResponse = $this->client->listCaches();
            $this->assertNull($listCachesResponse->asError());
            $listCachesResponse = $listCachesResponse->asSuccess();
            $caches = $listCachesResponse->caches();
            $cacheNames = array_map(fn($i) => $i->name(), $caches);
            $this->assertContains($cacheName, $cacheNames);
            $this->assertEquals(null, $listCachesResponse->nextToken());
            $this->assertEquals(get_class($listCachesResponse) . ": " . join(', ', $cacheNames), "$listCachesResponse");
        } finally {
            $response = $this->client->deleteCache($cacheName);
            $this->assertNull($response->asError());
        }
    }

    public function testListCachesBadAuth()
    {
        $client = $this->getBadAuthTokenClient();
        $response = $client->listCaches();
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::AUTHENTICATION_ERROR, $response->asError()->errorCode());
    }

    public function testListCachesNextToken()
    {
        $this->markTestSkipped("pagination not yet implemented");
    }

    // Setting and getting tests
    public function testCacheHit()
    {
        $key = uniqid();
        $value = uniqid();

        $response = $this->client->set($this->TEST_CACHE_NAME, $key, $value);
        $this->assertNull($response->asError());
        $response = $response->asSuccess();
        $this->assertNotNull($response);
        $this->assertEquals($value, $response->value());
        $this->assertEquals($key, $response->key());

        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $response = $response->asHit();
        $this->assertNotNull($response);
        $this->assertEquals($value, $response->value());
    }

    public function testGetMiss()
    {
        $key = uniqid();
        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testExpiresAfterTtl()
    {
        $key = uniqid();
        $value = uniqid();
        $client = new SimpleCacheClient($this->authProvider, 2);
        $response = $client->set($this->TEST_CACHE_NAME, $key, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        sleep(4);
        $response = $client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testSetWithDifferentTtls()
    {
        $key1 = uniqid();
        $key2 = uniqid();
        $response = $this->client->set($this->TEST_CACHE_NAME, $key1, "1", 2);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->set($this->TEST_CACHE_NAME, $key2, "2");
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->get($this->TEST_CACHE_NAME, $key1);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $response = $this->client->get($this->TEST_CACHE_NAME, $key2);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        sleep(4);

        $response = $this->client->get($this->TEST_CACHE_NAME, $key1);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");

        $response = $this->client->get($this->TEST_CACHE_NAME, $key2);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
    }

    // Set tests

    public function testSetWithNonexistentCache()
    {
        $cacheName = uniqid();
        $response = $this->client->set($cacheName, "key", "value");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testSetWithNullCacheName()
    {
        $this->expectException(TypeError::class);
        $this->client->set(null, "key", "value");
    }

    public function testSetWithEmptyCacheName()
    {
        $response = $this->client->set("", "key", "value");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetWithNullKey()
    {
        $this->expectException(TypeError::class);
        $this->client->set($this->TEST_CACHE_NAME, null, "value");
    }

    public function testSetWithNullValue()
    {
        $this->expectException(TypeError::class);
        $this->client->set($this->TEST_CACHE_NAME, "key", null);
    }

    public function testSetNegativeTtl()
    {
        $response = $this->client->set($this->TEST_CACHE_NAME, "key", "value", -1);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetBadKey()
    {
        $this->expectException(TypeError::class);
        $this->client->set($this->TEST_CACHE_NAME, null, "bar");
    }

    public function testSetBadValue()
    {
        $this->expectException(TypeError::class);
        $this->client->set($this->TEST_CACHE_NAME, "foo", null);
    }

    public function testSetBadAuth()
    {
        $client = $this->getBadAuthTokenClient();
        $response = $client->set($this->TEST_CACHE_NAME, "foo", "bar");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::AUTHENTICATION_ERROR, $response->asError()->errorCode());
    }

    // Get tests
    public function testGetNonexistentCache()
    {
        $cacheName = uniqid();
        $response = $this->client->get($cacheName, "foo");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testGetNullCacheName()
    {
        $this->expectException(TypeError::class);
        $this->client->get(null, "foo");
    }

    public function testGetEmptyCacheName()
    {
        $response = $this->client->get("", "foo");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testGetNullKey()
    {
        $this->expectException(TypeError::class);
        $this->client->get($this->TEST_CACHE_NAME, null);
    }

    public function testGetBadAuth()
    {
        $client = $this->getBadAuthTokenClient();
        $response = $client->get($this->TEST_CACHE_NAME, "key");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::AUTHENTICATION_ERROR, $response->asError()->errorCode());
    }

    public function testGetTimeout()
    {
        $client = new SimpleCacheClient($this->authProvider, $this->DEFAULT_TTL_SECONDS, 1);
        $response = $client->get($this->TEST_CACHE_NAME, "key");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::TIMEOUT_ERROR, $response->asError()->errorCode());
    }

    // Delete tests

    public function testDeleteNonexistentKey()
    {
        $key = "a key that isn't there";
        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");

        $response = $this->client->delete($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testDelete()
    {
        $key = "key1";
        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");

        $response = $this->client->set($this->TEST_CACHE_NAME, $key, "value");
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $response = $this->client->delete($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    // List API tests

    public function testListPushFrontFetchHappyPath()
    {
        $listName = uniqid();
        $value = uniqid();
        $value2 = uniqid();
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, true, 6000);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(1, $response->asSuccess()->listLength());

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $values = $response->asHit()->values();
        $this->assertNotEmpty($values);
        $this->assertCount(1, $values);
        $this->assertContains($value, $values);

        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value2, true, 6000);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(2, $response->asSuccess()->listLength());

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $values = $response->asHit()->values();
        $this->assertNotEmpty($values);
        $this->assertEquals([$value2, $value], $values);
    }

    public function testListPushFront_NoRefreshTtl()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, false, 5);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, false, 10);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        sleep(5);
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull(($response->asMiss()));
    }

    public function testListPushFront_RefreshTtl()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, false, 2);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, true, 10);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        sleep(2);
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertCount(2, $response->asHit()->values());
    }

    public function testListPushFront_TruncateList()
    {
        $listName = uniqid();
        $value1 = uniqid();
        $value2 = uniqid();
        $value3 = uniqid();
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value1, false);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value2, false);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value3, false, truncateBackToSize: 2);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals([$value3, $value2], $response->asHit()->values());
    }

    public function testListPushFront_TruncateList_NegativeValue()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, false, truncateBackToSize: -1);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testListPushBackFetchHappyPath()
    {
        $listName = uniqid();
        $value = uniqid();
        $value2 = uniqid();
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, true, 6000);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(1, $response->asSuccess()->listLength());

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $values = $response->asHit()->values();
        $this->assertNotEmpty($values);
        $this->assertCount(1, $values);
        $this->assertContains($value, $values);

        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value2, true, 6000);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(2, $response->asSuccess()->listLength());

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $values = $response->asHit()->values();
        $this->assertNotEmpty($values);
        $this->assertEquals([$value, $value2], $values);
    }

    public function testListPushBack_NoRefreshTtl()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, false, 5);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, false, 10);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        sleep(5);
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull(($response->asMiss()));
    }

    public function testListPushBack_RefreshTtl()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, false, 2);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, true, 10);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        sleep(2);
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertCount(2, $response->asHit()->values());
    }

    public function testListPushBack_TruncateList()
    {
        $listName = uniqid();
        $value1 = uniqid();
        $value2 = uniqid();
        $value3 = uniqid();
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value1, false);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value2, false);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value3, false, truncateFrontToSize: 2);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals([$value2, $value3], $response->asHit()->values());
    }

    public function testListPushBack_TruncateList_NegativeValue()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, false, truncateFrontToSize: -1);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testListPopFront_MissHappyPath()
    {
        $listName = uniqid();
        $response = $this->client->listPopFront($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testListPopFront_HappyPath()
    {
        $listName = uniqid();
        $values = [];
        foreach (range(0, 3) as $i) {
            $val = uniqid();
            $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $val, false);
            $this->assertNull($response->asError());
            $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
            array_unshift($values, $val);
        }
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($values, $response->asHit()->values());
        while ($val = array_shift($values)) {
            $response = $this->client->listPopFront($this->TEST_CACHE_NAME, $listName);
            $this->assertNull($response->asError());
            $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
            $this->assertEquals($val, $response->asHit()->value());
            $this->assertEquals(get_class($response) . ": {$response->asHit()->value()}", "$response");
        }
    }

    public function testListPopBack_MissHappyPath()
    {
        $listName = uniqid();
        $response = $this->client->listPopBack($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testListPopFront_EmptyList()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, false);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listPopFront($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($value, $response->asHit()->value());

        $response = $this->client->listLength($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(0, $response->asSuccess()->length());

        $response = $this->client->listPopFront($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testListPopBack_HappyPath()
    {
        $listName = uniqid();
        $values = [];
        foreach (range(0, 3) as $i) {
            $val = uniqid();
            $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $val, false);
            $this->assertNull($response->asError());
            $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
            $values[] = $val;
        }
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($values, $response->asHit()->values());

        while ($val = array_pop($values)) {
            $response = $this->client->listPopBack($this->TEST_CACHE_NAME, $listName);
            $this->assertNull($response->asError());
            $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
            $this->assertEquals($val, $response->asHit()->value());
            $this->assertEquals(get_class($response) . ": {$response->asHit()->value()}", "$response");
        }
    }

    public function testListPopBack_EmptyList()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, false);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listPopBack($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($value, $response->asHit()->value());

        $response = $this->client->listLength($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(0, $response->asSuccess()->length());

        $response = $this->client->listPopBack($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testListRemoveValue_HappyPath()
    {
        $listName = uniqid();
        $values = [];
        $valueToRemove = uniqid();
        foreach (range(0, 3) as $i) {
            $val = uniqid();
            $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $val, false);
            $this->assertNull($response->asError());
            $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
            $values[] = $val;
        }
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $valueToRemove, false);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $valueToRemove, false);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $expectedValues = $values;
        array_push($expectedValues, $valueToRemove, $valueToRemove);
        $this->assertEquals($expectedValues, $response->asHit()->values());

        $response = $this->client->listRemoveValue($this->TEST_CACHE_NAME, $listName, $valueToRemove);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($values, $response->asHit()->values());
    }

    public function testListRemoveValues_ValueNotPresent()
    {
        $listName = uniqid();
        $values = [];
        foreach (range(0, 3) as $i) {
            $val = uniqid();
            $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $val, false);
            $this->assertNull($response->asError());
            $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
            $values[] = $val;
        }

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($values, $response->asHit()->values());

        $response = $this->client->listRemoveValue($this->TEST_CACHE_NAME, $listName, "i-am-not-in-the-list");
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($values, $response->asHit()->values());
    }

    public function testListLength_HappyPath()
    {
        $listName = uniqid();
        foreach (range(0, 3) as $i) {
            $val = uniqid();
            $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $val, false);
            $this->assertNull($response->asError());
            $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

            $response = $this->client->listLength($this->TEST_CACHE_NAME, $listName);
            $this->assertNull($response->asError());
            $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
            $this->assertEquals($i + 1, $response->asSuccess()->length());
            $this->assertEquals(get_class($response) . ": {$response->asSuccess()->length()}", "$response");
        }
    }

    public function testListLength_MissingList()
    {
        $response = $this->client->listLength($this->TEST_CACHE_NAME, uniqid());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(0, $response->asSuccess()->length());
    }

    // List erase
    public function testListEraseAll_HappyPath()
    {
        $listName = uniqid();
        foreach (range(0, 3) as $i) {
            $val = uniqid();
            $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $val, false);
            $this->assertNull($response->asError());
            $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        }

        $response = $this->client->listLength($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(4, $response->asSuccess()->length());

        $response = $this->client->listErase($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listLength($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(0, $response->asSuccess()->length());
    }

    public function testListEraseRange_HappyPath()
    {
        $listName = uniqid();
        $values = [];
        foreach (range(0, 3) as $i) {
            $val = uniqid();
            $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $val, false);
            $this->assertNull($response->asError());
            $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
            $values[] = $val;
        }

        $response = $this->client->listLength($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(4, $response->asSuccess()->length());

        $response = $this->client->listErase($this->TEST_CACHE_NAME, $listName, 0, 2);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals(array_slice($values, 2), $response->asHit()->values());
    }

    public function testListEraseRange_LargeCountValue()
    {
        $listName = uniqid();
        $values = [];
        foreach (range(0, 3) as $i) {
            $val = uniqid();
            $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $val, false);
            $this->assertNull($response->asError());
            $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
            $values[] = $val;
        }

        $response = $this->client->listLength($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(4, $response->asSuccess()->length());

        $response = $this->client->listErase($this->TEST_CACHE_NAME, $listName, 1, 20);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals([$values[0]], $response->asHit()->values());
    }

    public function testListErase_MissingList()
    {
        $response = $this->client->listErase($this->TEST_CACHE_NAME, uniqid());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
    }

    // Dictionary tests
    public function testDictionary_IsMissing()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testDictionary_HappyPath()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = uniqid();
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, false);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
    }

    public function testDictionaryFieldMissing()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = uniqid();
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, false);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $otherField = uniqid();
        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $otherField);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testDictionaryNoRefreshTtl()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = uniqid();
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, false, 5);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(1);

        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, false, 10);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(4);

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testDictionaryEmptyDictionaryName_IsError()
    {
        $dictionaryName = "";
        $field = uniqid();
        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionaryEmptyFieldName_IsError()
    {
        $dictionaryName = uniqid();
        $field = "";
        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionaryEmptyValue_IsError()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = "";
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, false);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionaryRefreshTtl()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = uniqid();
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, false, 2);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, true, 10);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        sleep(2);
        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($value, $response->asHit()->value());
    }

    public function testDictionaryDeleteEmptyCacheName_IsError()
    {
        $cacheName = "";
        $dictionaryName = uniqid();
        $response = $this->client->dictionaryDelete($cacheName, $dictionaryName);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionaryDeleteEmptyDictionaryName_IsError()
    {
        $dictionaryName = "";
        $response = $this->client->dictionaryDelete($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionaryDelete_HappyPath()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field, uniqid(), false);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $response = $this->client->dictionaryDelete($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testDictionaryIncrement_NullFieldError()
    {
        $dictionaryName = uniqid();
        $field = "";
        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, false);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionaryIncrement_HappyPath()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, false);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(1, $response->asSuccess()->value());

        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, false, 41);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(42, $response->asSuccess()->value());

        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, false, -1042);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(-1000, $response->asSuccess()->value());

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals("-1000", $response->asHit()->value());
    }

    public function testDictionaryIncrement_RefreshTtl()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, false, ttlSeconds: 2);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, true, ttlSeconds: 10);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(2);

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals(2, $response->asHit()->value());
    }

    public function testDictionaryIncrement_NoRefreshTtl()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, false, ttlSeconds: 5);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, false, ttlSeconds: 10);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(6);

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testDictionaryIncrement_SetAndReset()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field, "10", false);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, false, 0);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(10, $response->asSuccess()->value());

        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, false, 90);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(100, $response->asSuccess()->value());

        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field, "0", false);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, false, 0);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(0, $response->asSuccess()->value());
    }

    public function testDictionaryIncrement_FailedPrecondition()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field, "amcaface", false);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, true, 1);
        $this->assertNotNull($response->asError(), "Expected an error but got: $response");
        $this->assertEquals(MomentoErrorCode::FAILED_PRECONDITION_ERROR, $response->asError()->errorCode());
    }

    public function testDictionaryRemoveField_EmptyValues()
    {
        $response = $this->client->dictionaryRemoveField("", "dict", "field");
        $this->assertNotNull($response->asError(), "Expected an error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());

        $response = $this->client->dictionaryRemoveField("cache", "", "field");
        $this->assertNotNull($response->asError(), "Expected an error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());

        $response = $this->client->dictionaryRemoveField("cache", "dict", "");
        $this->assertNotNull($response->asError(), "Expected an error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionaryRemoveField_HappyPath()
    {
        $dictionaryName = uniqid();
        $field1 = uniqid();
        $value1 = uniqid();
        $field2 = uniqid();

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field1);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");

        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field1, $value1, false);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryRemoveField($this->TEST_CACHE_NAME, $dictionaryName, $field1);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field1);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field2);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");

        $response = $this->client->dictionaryRemoveField($this->TEST_CACHE_NAME, $dictionaryName, $field2);
        $this->assertNull($response->asError());

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field2);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testDictionaryRemoveFields_EmptyValues()
    {
        $response = $this->client->dictionaryRemoveFields("", "dict", ["field"]);
        $this->assertNotNull($response->asError(), "Expected an error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());

        $response = $this->client->dictionaryRemoveFields("cache", "", ["field"]);
        $this->assertNotNull($response->asError(), "Expected an error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());

        $response = $this->client->dictionaryRemoveFields("cache", "dict", [""]);
        $this->assertNotNull($response->asError(), "Expected an error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }


    public function testDictionaryRemoveFields_HappyPath()
    {
        $dictionaryName = uniqid();
        $fields = [uniqid(), uniqid()];
        $otherField = uniqid();

        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $fields[0], uniqid(), false);
        $this->assertNull($response->asError());
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $fields[1], uniqid(), false);
        $this->assertNull($response->asError());
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $otherField, uniqid(), false);
        $this->assertNull($response->asError());

        $response = $this->client->dictionaryRemoveFields($this->TEST_CACHE_NAME, $dictionaryName, $fields);
        $this->assertNull($response->asError());

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $otherField);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $fields[0]);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $fields[1]);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testDictionaryFetchWithNullCacheName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $cacheName = null;
        $dictionaryName = uniqid();
        $this->client->dictionaryFetch($cacheName, $dictionaryName);
    }

    public function testDictionaryFetchWithNullDictionaryName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $dictionaryName = null;
        $this->client->dictionaryFetch($this->TEST_CACHE_NAME, $dictionaryName);
    }

    public function testDictionaryFetchMissing_HappyPath()
    {
        $dictionaryName = uniqid();
        $response = $this->client->dictionaryFetch($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testDictionaryFetch_HappyPath()
    {
        $dictionaryName = uniqid();
        $field1 = uniqid();
        $field2 = uniqid();
        $value1 = uniqid();
        $value2 = uniqid();
        $contentDictionary = [$field1 => $value1, $field2 => $value2];
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field1, $value1, true, 10);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field2, $value2, true, 10);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryFetch($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($response->asHit()->dictionary(), $contentDictionary);
    }

    public function testDictionaryFetchDictionaryDoesNotExist_Noop()
    {
        $dictionaryName = uniqid();
        $response = $this->client->dictionaryFetch($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");

        $response = $this->client->dictionaryDelete($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryFetch($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testDictionaryDeleteDictionaryExists_HappyPath()
    {
        $dictionaryName = uniqid();
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, uniqid(), uniqid(), false);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, uniqid(), uniqid(), false);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, uniqid(), uniqid(), false);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryFetch($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $response = $this->client->dictionaryDelete($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryFetch($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testDictionarySetBatchWithNullDictionaryName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $dictionaryName = null;
        $items = [uniqid()];
        $this->client->dictionarySetBatch($this->TEST_CACHE_NAME, $dictionaryName, $items, false);
    }

    public function testDictionarySetBatchWithEmptyDictionaryName_IsError()
    {
        $dictionaryName = "";
        $items = [uniqid()];
        $response = $this->client->dictionarySetBatch($this->TEST_CACHE_NAME, $dictionaryName, $items, false);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionarySetBatchWithNullItems_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $dictionaryName = uniqid();
        $items = null;
        $this->client->dictionarySetBatch($this->TEST_CACHE_NAME, $dictionaryName, $items, false);
    }

    public function testDictionarySetBatchWithEmptyItems_IsError()
    {
        $dictionaryName = uniqid();
        $items = [""];
        $response = $this->client->dictionarySetBatch($this->TEST_CACHE_NAME, $dictionaryName, $items, false);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());

        $items = [];
        $response = $this->client->dictionarySetBatch($this->TEST_CACHE_NAME, $dictionaryName, $items, false);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionarySetBatch_HappyPath()
    {
        $dictionaryName = uniqid();
        $field1 = uniqid();
        $field2 = uniqid();
        $value1 = uniqid();
        $value2 = uniqid();
        $items = [$field1 => $value1, $field2 => $value2];
        $response = $this->client->dictionarySetBatch($this->TEST_CACHE_NAME, $dictionaryName, $items, false, 10);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field1);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($value1, $response->asHit()->value());

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field2);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($value2, $response->asHit()->value());
    }

    public function testDictionarySetBatchRefreshTtl_HappyPath()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = uniqid();
        $content = [$field => $value];
        $response = $this->client->dictionarySetBatch($this->TEST_CACHE_NAME, $dictionaryName, $content, false, 2);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionarySetBatch($this->TEST_CACHE_NAME, $dictionaryName, $content, true, 10);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(2);

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($value, $response->asHit()->value());
    }

    public function testDictionarySetBatchNoRefreshTtl_HappyPath()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = uniqid();
        $content = [$field => $value];
        $response = $this->client->dictionarySetBatch($this->TEST_CACHE_NAME, $dictionaryName, $content, false, 5);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(1);

        $response = $this->client->dictionarySetBatch($this->TEST_CACHE_NAME, $dictionaryName, $content, false, 10);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(4);

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testDictionaryGetBatchWithNullDictionaryName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $dictionaryName = null;
        $this->client->dictionaryGetBatch($this->TEST_CACHE_NAME, $dictionaryName, uniqid());
    }

    public function testDictionaryGetBatchWithEmptyDictionaryName_IsError()
    {
        $dictionaryName = "";
        $response = $this->client->dictionaryGetBatch($this->TEST_CACHE_NAME, $dictionaryName, [uniqid()]);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionaryGetBatchWithNullFields_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $dictionaryName = uniqid();
        $fields = null;
        $this->client->dictionaryGetBatch($this->TEST_CACHE_NAME, $dictionaryName, $fields);
    }

    public function testDictionaryGetBatchWithEmptyFields_IsError()
    {
        $dictionaryName = uniqid();
        $fields = [""];
        $response = $this->client->dictionaryGetBatch($this->TEST_CACHE_NAME, $dictionaryName, $fields);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());

        $fields = [];
        $response = $this->client->dictionaryGetBatch($this->TEST_CACHE_NAME, $dictionaryName, $fields);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionaryGetBatch_HappyPath()
    {
        $dictionaryName = uniqid();
        $field1 = uniqid();
        $field2 = uniqid();
        $field3 = uniqid();
        $value1 = uniqid();
        $value2 = uniqid();
        $items = [$field1 => $value1, $field2 => $value2];
        $response = $this->client->dictionarySetBatch($this->TEST_CACHE_NAME, $dictionaryName, $items, false, 10);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryGetBatch($this->TEST_CACHE_NAME, $dictionaryName, [$field1, $field2, $field3]);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $values = [$value1, $value2, null];
        $this->assertEquals($values, $response->asSuccess()->values());
    }

    public function testDictionaryGetBatchDictionaryMissing_HappyPath()
    {
        $dictionaryName = uniqid();
        $field1 = uniqid();
        $field2 = uniqid();
        $field3 = uniqid();
        $response = $this->client->dictionaryGetBatch($this->TEST_CACHE_NAME, $dictionaryName, [$field1, $field2, $field3]);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $values = [null, null, null];
        $this->assertEquals($values, $response->asSuccess()->values());
    }

    // __toString() tests

    public function testCacheSetToString_HappyPath()
    {
        $key = uniqid();
        $value = "a short value";
        $response = $this->client->set($this->TEST_CACHE_NAME, $key, $value);
        $this->assertNull($response->asError());
        $this->assertEquals(get_class($response) . ": key $key = $value", "$response");
    }

    public function testCacheSetToString_LongValues()
    {
        $key = str_repeat("k", 256);
        $value = str_repeat("v", 256);
        $response = $this->client->set($this->TEST_CACHE_NAME, $key, $value);
        $this->assertNull($response->asError());
        $this->assertStringStartsWith(get_class($response) . ": key ", "$response");
        $this->assertMatchesRegularExpression("/: key k+\.\.\. = v+\.\.\.$/", "$response");
    }

    public function testCacheGetToString_HappyPath()
    {
        $key = uniqid();
        $value = "a short value";
        $response = $this->client->set($this->TEST_CACHE_NAME, $key, $value);
        $this->assertNull($response->asError());

        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertEquals(get_class($response) . ": $value", "$response");
    }

    public function testCacheGetToString_LongValue()
    {
        $key = uniqid();
        $value = str_repeat("a", 256);
        $response = $this->client->set($this->TEST_CACHE_NAME, $key, $value);
        $this->assertNull($response->asError());

        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertEquals($value, $response->asHit()->value());
        $this->assertStringEndsWith("...", "$response");
    }

    public function testCacheListPopFrontToString_HappyPath()
    {
        $listName = uniqid();
        $value = "a short value";
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, false);
        $this->assertNull($response->asError());

        $response = $this->client->listPopFront($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertEquals(get_class($response) . ": $value", "$response");
    }

    public function testCacheListPopFrontToString_LongValue()
    {
        $listName = uniqid();
        $value = str_repeat("a", 256);
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, false);
        $this->assertNull($response->asError());

        $response = $this->client->listPopFront($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertEquals($value, $response->asHit()->value());
        $this->assertStringEndsWith("...", "$response");
    }

    public function testCacheListPopBackToString_HappyPath()
    {
        $listName = uniqid();
        $value = "a short value";
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, false);
        $this->assertNull($response->asError());

        $response = $this->client->listPopBack($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertEquals(get_class($response) . ": $value", "$response");
    }

    public function testCacheListPopBackToString_LongValue()
    {
        $listName = uniqid();
        $value = str_repeat("a", 256);
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, false);
        $this->assertNull($response->asError());

        $response = $this->client->listPopBack($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertEquals($value, $response->asHit()->value());
        $this->assertStringEndsWith("...", "$response");
    }

    public function testCacheListFetchToString_HappyPath()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, false);
        $this->assertNull($response->asError());
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, false);
        $this->assertNull($response->asError());
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, false);
        $this->assertNull($response->asError());

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertCount(3, $response->asHit()->values());
        $this->assertEquals(get_class($response) . ": 3 items", "$response");
    }

    public function testCacheListPushFrontToString_HappyPath()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, false);
        $this->assertNull($response->asError());
        $this->assertEquals(get_class($response) . ": 1 items", "$response");
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, false);
        $this->assertNull($response->asError());
        $this->assertEquals(get_class($response) . ": 2 items", "$response");
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, false);
        $this->assertNull($response->asError());
        $this->assertEquals(get_class($response) . ": 3 items", "$response");
    }

    public function testCacheListPushBackToString_HappyPath()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, false);
        $this->assertNull($response->asError());
        $this->assertEquals(get_class($response) . ": 1 items", "$response");
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, false);
        $this->assertNull($response->asError());
        $this->assertEquals(get_class($response) . ": 2 items", "$response");
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, false);
        $this->assertNull($response->asError());
        $this->assertEquals(get_class($response) . ": 3 items", "$response");
    }

    public function testCacheListLengthToString_HappyPath()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, false);
        $this->assertNull($response->asError());
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, false);
        $this->assertNull($response->asError());
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, false);
        $this->assertNull($response->asError());

        $response = $this->client->listLength($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertEquals(get_class($response) . ": 3", "$response");
    }

    public function testDictionaryGetToString_HappyPath()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = uniqid();
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, false);
        $this->assertNull($response->asError());

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals(get_class($response) . ": $value", "$response");
    }

    public function testDictionaryGetToString_LongValue()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = str_repeat("a", 256);
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, false);
        $this->assertNull($response->asError());

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($value, $response->asHit()->value());
        $this->assertStringEndsWith("...", "$response");
    }

    public function testDictionaryFetchToString_HappyPath()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = uniqid();
        for ($i = 0; $i < 5; $i++) {
            $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, "$field-$i", $value, false);
            $this->assertNull($response->asError());
        }
        $response = $this->client->dictionaryFetch($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals(get_class($response) . ": 5 items", "$response");
    }

    public function testDictionaryIncrementToString_HappyPath()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field, "1", false);
        $this->assertNull($response->asError());

        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, false);
        $this->assertNull($response->asError());
        $this->assertEquals(get_class($response) . ": 2", "$response");

        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, false, 10);
        $this->assertNull($response->asError());
        $this->assertEquals(get_class($response) . ": 12", "$response");
    }

    public function testSetAddWithNullCacheName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $setName = uniqid();
        $element = uniqid();
        $this->client->setAdd(null, $setName, $element, false);
    }

    public function testSetAddWithEmptyCacheName_ThrowsException()
    {
        $setName = uniqid();
        $element = uniqid();
        $response = $this->client->setAdd("", $setName, $element, false);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetAddWithNullSetName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $element = uniqid();
        $this->client->setAdd($this->TEST_CACHE_NAME, null, $element, false);
    }

    public function testSetAddWithEmptySetName_ThrowsException()
    {
        $element = uniqid();
        $response = $this->client->setAdd($this->TEST_CACHE_NAME, "", $element, false);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetAddWithNullElement_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $setName = uniqid();
        $this->client->setAdd($this->TEST_CACHE_NAME, $setName, null, false);
    }

    public function testSetAddWithEmptyElement_ThrowsException()
    {
        $setName = uniqid();
        $response = $this->client->setAdd($this->TEST_CACHE_NAME, $setName, "", false);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetFetchWithNullCacheName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $setName = uniqid();
        $this->client->setFetch(null, $setName);
    }

    public function testSetFetchWithEmptyCacheName_ThrowsException()
    {
        $setName = uniqid();
        $response = $this->client->setFetch("", $setName);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetFetchWithNullSetName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->client->setFetch($this->TEST_CACHE_NAME, null);
    }

    public function testSetFetchWithEmptySetName_ThrowsException()
    {
        $response = $this->client->setFetch($this->TEST_CACHE_NAME, "");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetAddSetFetch_HappyPath()
    {
        $setName = uniqid();
        $element = uniqid();
        $response = $this->client->setAdd($this->TEST_CACHE_NAME, $setName, $element, false);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->setFetch($this->TEST_CACHE_NAME, $setName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($element, $response->asHit()->stringSet()[0]);
    }

    public function testSetAddSetFetch_NoRefreshTtl()
    {
        $setName = uniqid();
        $element = uniqid();
        $response = $this->client->setAdd($this->TEST_CACHE_NAME, $setName, $element, false, 5);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(1);

        $response = $this->client->setAdd($this->TEST_CACHE_NAME, $setName, $element, false, 10);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(4);

        $response = $this->client->setFetch($this->TEST_CACHE_NAME, $setName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testSetAddSetFetch_RefreshTtl()
    {
        $setName = uniqid();
        $element = uniqid();
        $response = $this->client->setAdd($this->TEST_CACHE_NAME, $setName, $element, false, 2);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->setAdd($this->TEST_CACHE_NAME, $setName, $element, true, 10);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(2);

        $response = $this->client->setFetch($this->TEST_CACHE_NAME, $setName);
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($element, $response->asHit()->stringSet()[0]);
    }
}
