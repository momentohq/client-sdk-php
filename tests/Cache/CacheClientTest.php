<?php

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
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->set($cacheName, $key, $value);
        $this->assertNotNull($response->asSuccess());
        $this->assertEquals(get_class($response) . ": key $key = $value", "$response");
        $response = $this->client->get($cacheName, $key);
        $this->assertNotNull($response->asHit());
        $response = $response->asHit();
        $this->assertEquals($response->value(), $value);
        $this->assertEquals(get_class($response) . ": $value", "$response");
        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNotNull($response->asMiss());
        $response = $this->client->deleteCache($cacheName);
        $this->assertNotNull($response->asSuccess());
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
        $this->assertNotNull($response->asAlreadyExists());
    }

    public function testCreateCacheEmptyName()
    {
        $response = $this->client->createCache("");
        $this->assertNotNull($response->asError());
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
        $response = $this->client->createCache(1);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testCreateCacheBadAuth()
    {
        $client = $this->getBadAuthTokenClient();
        $response = $client->createCache(uniqid());
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::AUTHENTICATION_ERROR, $response->asError()->errorCode());
    }

    // Delete cache tests

    public function testDeleteCacheSucceeds()
    {
        $cacheName = uniqid();
        $response = $this->client->createCache($cacheName);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->deleteCache($cacheName);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->deleteCache($cacheName);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testDeleteUnknownCache()
    {
        $cacheName = uniqid();
        $response = $this->client->deleteCache($cacheName);
        $this->assertNotNull($response->asError());
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
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDeleteCacheBadAuth()
    {
        $client = $this->getBadAuthTokenClient();
        $response = $client->deleteCache(uniqid());
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::AUTHENTICATION_ERROR, $response->asError()->errorCode());
    }

    // List caches tests
    public function testListCaches()
    {
        $cacheName = uniqid();
        $resp = $this->client->listCaches();
        $successResp = $resp->asSuccess();
        $this->assertNotNull($successResp);
        $caches = $successResp->caches();
        $cacheNames = array_map(fn($i) => $i->name(), $caches);
        $this->assertNotContains($cacheName, $cacheNames);
        try {
            $this->client->createCache($cacheName);
            $listCachesResp = $this->client->listCaches();
            $caches = $listCachesResp->caches();
            $cacheNames = array_map(fn($i) => $i->name(), $caches);
            $this->assertContains($cacheName, $cacheNames);
            $this->assertEquals(null, $listCachesResp->nextToken());
            $this->assertEquals(get_class($listCachesResp) . ": " . join(', ', $cacheNames), "$listCachesResp");
        } finally {
            $this->client->deleteCache($cacheName);
        }
    }

    public function testListCachesBadAuth()
    {
        $client = $this->getBadAuthTokenClient();
        $response = $client->listCaches();
        $this->assertNotNull($response->asError());
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

        $setResp = $this->client->set($this->TEST_CACHE_NAME, $key, $value);
        $this->assertNotNull($setResp->asSuccess());
        $this->assertEquals($value, $setResp->value());
        $this->assertEquals($key, $setResp->key());

        $getResp = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNotNull($getResp->asHit());
        $this->assertEquals($value, $getResp->asHit()->value());
    }

    public function testGetMiss()
    {
        $key = uniqid();
        $getResp = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNotNull($getResp->asMiss());
    }

    public function testExpiresAfterTtl()
    {
        $key = uniqid();
        $value = uniqid();
        $client = new SimpleCacheClient($this->authProvider, 2);
        $response = $client->set($this->TEST_CACHE_NAME, $key, $value);
        $this->assertNotNull($response->asSuccess());
        $response = $client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNotNull($response->asHit());
        sleep(4);
        $response = $client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNotNull($response->asMiss());
    }

    public function testSetWithDifferentTtls()
    {
        $key1 = uniqid();
        $key2 = uniqid();
        $response = $this->client->set($this->TEST_CACHE_NAME, $key1, "1", 2);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->set($this->TEST_CACHE_NAME, $key2, "2");
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->get($this->TEST_CACHE_NAME, $key1);
        $this->assertNotNull($response->asHit());
        $response = $this->client->get($this->TEST_CACHE_NAME, $key2);
        $this->assertNotNull($response->asHit());

        sleep(4);

        $response = $this->client->get($this->TEST_CACHE_NAME, $key1);
        $this->assertNotNull($response->asMiss());
        $response = $this->client->get($this->TEST_CACHE_NAME, $key2);
        $this->assertNotNull($response->asHit());
    }

    // Set tests

    public function testSetWithNonexistentCache()
    {
        $cacheName = uniqid();
        $response = $this->client->set($cacheName, "key", "value");
        $this->assertNotNull($response->asError());
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
        $this->assertNotNull($response->asError());
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
        $this->assertNotNull($response->asError());
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
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::AUTHENTICATION_ERROR, $response->asError()->errorCode());
    }

    // Get tests
    public function testGetNonexistentCache()
    {
        $cacheName = uniqid();
        $response = $this->client->get($cacheName, "foo");
        $this->assertNotNull($response->asError());
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
        $this->assertNotNull($response->asError());
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
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::AUTHENTICATION_ERROR, $response->asError()->errorCode());
    }

    public function testGetTimeout()
    {
        $client = new SimpleCacheClient($this->authProvider, $this->DEFAULT_TTL_SECONDS, 1);
        $response = $client->get($this->TEST_CACHE_NAME, "key");
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::TIMEOUT_ERROR, $response->asError()->errorCode());
    }

    // Delete tests

    public function testDeleteNonexistentKey()
    {
        $key = "a key that isn't there";
        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNotNull($response->asMiss());
        $response = $this->client->delete($this->TEST_CACHE_NAME, $key);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNotNull($response->asMiss());
    }

    public function testDelete()
    {
        $key = "key1";
        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNotNull($response->asMiss());
        $response = $this->client->set($this->TEST_CACHE_NAME, $key, "value");
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNotNull($response->asHit());
        $response = $this->client->delete($this->TEST_CACHE_NAME, $key);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNotNull($response->asMiss());
    }

    // List API tests

    public function testListPushFrontFetchHappyPath()
    {
        $listName = uniqid();
        $value = uniqid();
        $value2 = uniqid();
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, true, 6000);
        $this->assertNotNull($response->asSuccess());
        $this->assertEquals(1, $response->asSuccess()->listLength());
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asHit());
        $values = $response->asHit()->values();
        $this->assertNotEmpty($values);
        $this->assertCount(1, $values);
        $this->assertContains($value, $values);

        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value2, true, 6000);
        $this->assertNotNull($response->asSuccess());
        $this->assertEquals(2, $response->asSuccess()->listLength());
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asHit());
        $values = $response->asHit()->values();
        $this->assertNotEmpty($values);
        $this->assertEquals([$value2, $value], $values);
    }

    public function testListPushFront_NoRefreshTtl()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, false, 5);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, false, 10);
        $this->assertNotNull($response->asSuccess());
        sleep(5);
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull(($response->asMiss()));
    }

    public function testListPushFront_RefreshTtl()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, false, 2);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, true, 10);
        $this->assertNotNull($response->asSuccess());
        sleep(2);
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asHit());
        $this->assertCount(2, $response->asHit()->values());
    }

    public function testListPushFront_TruncateList()
    {
        $listName = uniqid();
        $value1 = uniqid();
        $value2 = uniqid();
        $value3 = uniqid();
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value1, false);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value2, false);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value3, false, truncateBackToSize: 2);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asHit());
        $this->assertEquals([$value3, $value2], $response->asHit()->values());
    }

    public function testListPushFront_TruncateList_NegativeValue()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, false, truncateBackToSize: -1);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testListPushBackFetchHappyPath()
    {
        $listName = uniqid();
        $value = uniqid();
        $value2 = uniqid();
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, true, 6000);
        $this->assertNotNull($response->asSuccess());
        $this->assertEquals(1, $response->asSuccess()->listLength());
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asHit());
        $values = $response->asHit()->values();
        $this->assertNotEmpty($values);
        $this->assertCount(1, $values);
        $this->assertContains($value, $values);

        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value2, true, 6000);
        $this->assertNotNull($response->asSuccess());
        $this->assertEquals(2, $response->asSuccess()->listLength());
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asHit());
        $values = $response->asHit()->values();
        $this->assertNotEmpty($values);
        $this->assertEquals([$value, $value2], $values);
    }

    public function testListPushBack_NoRefreshTtl()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, false, 5);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, false, 10);
        $this->assertNotNull($response->asSuccess());
        sleep(5);
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull(($response->asMiss()));
    }

    public function testListPushBack_RefreshTtl()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, false, 2);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, true, 10);
        $this->assertNotNull($response->asSuccess());
        sleep(2);
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asHit());
        $this->assertCount(2, $response->asHit()->values());
    }

    public function testListPushBack_TruncateList()
    {
        $listName = uniqid();
        $value1 = uniqid();
        $value2 = uniqid();
        $value3 = uniqid();
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value1, false);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value2, false);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value3, false, truncateFrontToSize: 2);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asHit());
        $this->assertEquals([$value2, $value3], $response->asHit()->values());
    }

    public function testListPushBack_TruncateList_NegativeValue()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, false, truncateFrontToSize: -1);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testListPopFront_MissHappyPath()
    {
        $listName = uniqid();
        $response = $this->client->listPopFront($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asMiss());
    }

    public function testListPopFront_HappyPath()
    {
        $listName = uniqid();
        $values = [];
        foreach (range(0, 3) as $i) {
            $val = uniqid();
            $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $val, false);
            $this->assertNotNull($response->asSuccess());
            array_unshift($values, $val);
        }
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asHit());
        $this->assertEquals($values, $response->asHit()->values());
        while ($val = array_shift($values)) {
            $response = $this->client->listPopFront($this->TEST_CACHE_NAME, $listName);
            $this->assertNotNull($response->asHit());
            $this->assertEquals($val, $response->asHit()->value());
            $this->assertEquals(get_class($response) . ": {$response->asHit()->value()}", "$response");
        }
    }

    public function testListPopBack_MissHappyPath()
    {
        $listName = uniqid();
        $response = $this->client->listPopBack($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asMiss());
    }

    public function testListPopFront_EmptyList()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, false);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->listPopFront($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asHit());
        $this->assertEquals($value, $response->asHit()->value());
        $response = $this->client->listLength($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asSuccess());
        $this->assertEquals(0, $response->asSuccess()->length());
        $response = $this->client->listPopFront($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asMiss());
    }

    public function testListPopBack_HappyPath()
    {
        $listName = uniqid();
        $values = [];
        foreach (range(0, 3) as $i) {
            $val = uniqid();
            $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $val, false);
            $this->assertNotNull($response->asSuccess());
            $values[] = $val;
        }
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asHit());
        $this->assertEquals($values, $response->asHit()->values());
        while ($val = array_pop($values)) {
            $response = $this->client->listPopBack($this->TEST_CACHE_NAME, $listName);
            $this->assertNotNull($response->asHit());
            $this->assertEquals($val, $response->asHit()->value());
            $this->assertEquals(get_class($response) . ": {$response->asHit()->value()}", "$response");
        }
    }

    public function testListPopBack_EmptyList()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, false);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->listPopBack($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asHit());
        $this->assertEquals($value, $response->asHit()->value());
        $response = $this->client->listLength($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asSuccess());
        $this->assertEquals(0, $response->asSuccess()->length());
        $response = $this->client->listPopBack($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asMiss());
    }

    public function testListRemoveValue_HappyPath()
    {
        $listName = uniqid();
        $values = [];
        $valueToRemove = uniqid();
        foreach (range(0, 3) as $i) {
            $val = uniqid();
            $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $val, false);
            $this->assertNotNull($response->asSuccess());
            $values[] = $val;
        }
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $valueToRemove, false);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $valueToRemove, false);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asHit());
        $expectedValues = $values;
        array_push($expectedValues, $valueToRemove, $valueToRemove);
        $this->assertEquals($expectedValues, $response->asHit()->values());

        $response = $this->client->listRemoveValue($this->TEST_CACHE_NAME, $listName, $valueToRemove);
        $this->assertNotNull($response->asSuccess());

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asHit());
        $this->assertEquals($values, $response->asHit()->values());
    }

    public function testListRemoveValues_ValueNotPresent()
    {
        $listName = uniqid();
        $values = [];
        foreach (range(0, 3) as $i) {
            $val = uniqid();
            $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $val, false);
            $this->assertNotNull($response->asSuccess());
            $values[] = $val;
        }

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asHit());
        $this->assertEquals($values, $response->asHit()->values());

        $response = $this->client->listRemoveValue($this->TEST_CACHE_NAME, $listName, "i-am-not-in-the-list");
        $this->assertNotNull($response->asSuccess());

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asHit());
        $this->assertEquals($values, $response->asHit()->values());
    }

    public function testListLength_HappyPath()
    {
        $listName = uniqid();
        foreach (range(0, 3) as $i) {
            $val = uniqid();
            $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $val, false);
            $this->assertNotNull($response->asSuccess());
            $response = $this->client->listLength($this->TEST_CACHE_NAME, $listName);
            $this->assertNotNull($response->asSuccess());
            $this->assertEquals($i + 1, $response->asSuccess()->length());
            $this->assertEquals(get_class($response) . ": {$response->asSuccess()->length()}", "$response");
        }
    }

    public function testListLength_MissingList()
    {
        $response = $this->client->listLength($this->TEST_CACHE_NAME, uniqid());
        $this->assertNotNull($response->asSuccess());
        $this->assertEquals(0, $response->asSuccess()->length());
    }

    // List erase
    public function testListEraseAll_HappyPath()
    {
        $listName = uniqid();
        foreach (range(0, 3) as $i) {
            $val = uniqid();
            $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $val, false);
            $this->assertNotNull($response->asSuccess());
        }

        $response = $this->client->listLength($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asSuccess());
        $this->assertEquals(4, $response->asSuccess()->length());

        $response = $this->client->listErase($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asSuccess());

        $response = $this->client->listLength($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asSuccess());
        $this->assertEquals(0, $response->asSuccess()->length());
    }

    public function testListEraseRange_HappyPath()
    {
        $listName = uniqid();
        $values = [];
        foreach (range(0, 3) as $i) {
            $val = uniqid();
            $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $val, false);
            $this->assertNotNull($response->asSuccess());
            $values[] = $val;
        }

        $response = $this->client->listLength($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asSuccess());
        $this->assertEquals(4, $response->asSuccess()->length());

        $response = $this->client->listErase($this->TEST_CACHE_NAME, $listName, 0, 2);
        $this->assertNotNull($response->asSuccess());

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asHit());
        $this->assertEquals(array_slice($values, 2), $response->asHit()->values());
    }

    public function testListEraseRange_LargeCountValue()
    {
        $listName = uniqid();
        $values = [];
        foreach (range(0, 3) as $i) {
            $val = uniqid();
            $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $val, false);
            $this->assertNotNull($response->asSuccess());
            $values[] = $val;
        }

        $response = $this->client->listLength($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asSuccess());
        $this->assertEquals(4, $response->asSuccess()->length());

        $response = $this->client->listErase($this->TEST_CACHE_NAME, $listName, 1, 20);
        $this->assertNotNull($response->asSuccess());

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asHit());
        $this->assertEquals([$values[0]], $response->asHit()->values());
    }

    public function testListErase_MissingList()
    {
        $response = $this->client->listErase($this->TEST_CACHE_NAME, uniqid());
        $this->assertNotNull($response->asSuccess());
    }

    // Dictionary tests
    public function testDictionaryIsMissing()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNotNull($response->asMiss());
    }

    public function testDictionaryHappyPath()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = uniqid();
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, false);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNotNull($response->asHit());
    }

    public function testDictionaryFieldMissing()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = uniqid();
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, false);
        $this->assertNotNull($response->asSuccess());

        $otherField = uniqid();
        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $otherField);
        $this->assertNotNull($response->asMiss());
    }

    public function testDictionaryNoRefreshTtl()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = uniqid();
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, false, 5);
        $this->assertNotNull($response->asSuccess());
        sleep(1);

        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, false, 10);
        $this->assertNotNull($response->asSuccess());
        sleep(4);

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNotNull($response->asMiss());
    }

    public function testDictionaryThrowExceptionForEmptyDictionaryName()
    {
        $dictionaryName = "";
        $field = uniqid();
        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionaryThrowExceptionForEmptyFieldName()
    {
        $dictionaryName = uniqid();
        $field = "";
        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionaryThrowExceptionForEmptyValueName()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = "";
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, false);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionaryRefreshTtl()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = uniqid();
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, false, 2);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, true, 10);
        $this->assertNotNull($response->asSuccess());
        sleep(2);

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNotNull($response->asHit());
        $this->assertEquals($value, $response->asHit()->value());
    }

    public function testDictionaryDeleteThrowExceptionForEmptyCacheName()
    {
        $cacheName = "";
        $dictionaryName = uniqid();
        $response = $this->client->dictionaryDelete($cacheName, $dictionaryName);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionaryDeleteThrowExceptionForEmptyDictionaryName()
    {
        $dictionaryName = "";
        $response = $this->client->dictionaryDelete($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionaryDeleteHappyPath()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field, uniqid(), false);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNotNull($response->asHit());
        $response = $this->client->dictionaryDelete($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNotNull($response->asMiss());
    }

    public function testDictionaryFetchWithNullCacheNameIsError()
    {
        $this->expectException(TypeError::class);
        $cacheName = null;
        $dictionaryName = uniqid();
        $this->client->dictionaryFetch($cacheName, $dictionaryName);
    }

    public function testDictionaryFetchWithNullDictionaryNameIsError()
    {
        $this->expectException(TypeError::class);
        $dictionaryName = null;
        $this->client->dictionaryFetch($this->TEST_CACHE_NAME, $dictionaryName);
    }

    public function testDictionaryFetchMissingHappyPath()
    {
        $dictionaryName = uniqid();
        $response = $this->client->dictionaryFetch($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNotNull($response->asMiss());
    }

    public function testDictionaryFetchHappyPath()
    {
        $dictionaryName = uniqid();
        $field1 = uniqid();
        $field2 = uniqid();
        $value1 = uniqid();
        $value2 = uniqid();
        $contentDictionary = [$field1 => $value1, $field2 => $value2];
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field1, $value1, true, 10);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field2, $value2, true, 10);
        $this->assertNotNull($response->asSuccess());

        $response = $this->client->dictionaryFetch($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNotNull($response->asHit());
        $this->assertEquals($response->asHit()->dictionary(), $contentDictionary);
    }

    public function testDictionaryFetchDictionaryDoesNotExistNoop()
    {
        $dictionaryName = uniqid();
        $response = $this->client->dictionaryFetch($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNotNull($response->asMiss());
        $response = $this->client->dictionaryDelete($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->dictionaryFetch($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNotNull($response->asMiss());
    }

    public function testDictionaryDeleteDictionaryExistsHappyPath()
    {
        $dictionaryName = uniqid();
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, uniqid(), uniqid(), false);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, uniqid(), uniqid(), false);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, uniqid(), uniqid(), false);
        $this->assertNotNull($response->asSuccess());

        $response = $this->client->dictionaryFetch($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNotNull($response->asHit());
        $response = $this->client->dictionaryDelete($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->dictionaryFetch($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNotNull($response->asMiss());
    }

    public function testDictionarySetBatchWithNullDictionaryNameIsError()
    {
        $this->expectException(TypeError::class);
        $dictionaryName = null;
        $items = [uniqid()];
        $this->client->dictionarySetBatch($this->TEST_CACHE_NAME, $dictionaryName, $items, false);
    }

    public function testDictionarySetBatchWithEmptyDictionaryNameIsError()
    {
        $dictionaryName = "";
        $items = [uniqid()];
        $response = $this->client->dictionarySetBatch($this->TEST_CACHE_NAME, $dictionaryName, $items, false);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionarySetBatchWithNullItemsIsError()
    {
        $this->expectException(TypeError::class);
        $dictionaryName = uniqid();
        $items = null;
        $this->client->dictionarySetBatch($this->TEST_CACHE_NAME, $dictionaryName, $items, false);
    }

    public function testDictionarySetBatchWithEmptyItemsIsError()
    {
        $dictionaryName = uniqid();
        $items = [""];
        $response = $this->client->dictionarySetBatch($this->TEST_CACHE_NAME, $dictionaryName, $items, false);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());

        $items = [];
        $response = $this->client->dictionarySetBatch($this->TEST_CACHE_NAME, $dictionaryName, $items, false);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionarySetBatchHappyPath()
    {
        $dictionaryName = uniqid();
        $field1 = uniqid();
        $field2 = uniqid();
        $value1 = uniqid();
        $value2 = uniqid();
        $items = [$field1 => $value1, $field2 => $value2];
        $response = $this->client->dictionarySetBatch($this->TEST_CACHE_NAME, $dictionaryName, $items, false, 10);
        $this->assertNotNull($response->asSuccess());

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field1);
        $this->assertNotNull($response->asHit());
        $this->assertEquals($value1, $response->asHit()->value());

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field2);
        $this->assertNotNull($response->asHit());
        $this->assertEquals($value2, $response->asHit()->value());
    }

    public function testDictionarySetBatchRefreshTtlHappyPath()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = uniqid();
        $content = [$field => $value];
        $response = $this->client->dictionarySetBatch($this->TEST_CACHE_NAME, $dictionaryName, $content, false, 2);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->dictionarySetBatch($this->TEST_CACHE_NAME, $dictionaryName, $content, true, 10);
        $this->assertNotNull($response->asSuccess());
        sleep(2);

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNotNull($response->asHit());
        $this->assertEquals($value, $response->asHit()->value());
    }

    public function testDictionarySetBatchNoRefreshTtlHappyPath()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = uniqid();
        $content = [$field => $value];
        $response = $this->client->dictionarySetBatch($this->TEST_CACHE_NAME, $dictionaryName, $content, false, 5);
        $this->assertNotNull($response->asSuccess());
        sleep(1);
        $response = $this->client->dictionarySetBatch($this->TEST_CACHE_NAME, $dictionaryName, $content, false, 10);
        $this->assertNotNull($response->asSuccess());
        sleep(4);

        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNotNull($response->asMiss());
    }

    public function testDictionaryGetBatchWithNullDictionaryNameIsError()
    {
        $this->expectException(TypeError::class);
        $dictionaryName = null;
        $this->client->dictionaryGetBatch($this->TEST_CACHE_NAME, $dictionaryName, uniqid());
    }

    public function testDictionaryGetBatchWithEmptyDictionaryNameIsError()
    {
        $dictionaryName = "";
        $response = $this->client->dictionaryGetBatch($this->TEST_CACHE_NAME, $dictionaryName, [uniqid()]);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionaryGetBatchWithNullFieldsIsError()
    {
        $this->expectException(TypeError::class);
        $dictionaryName = uniqid();
        $fields = null;
        $this->client->dictionaryGetBatch($this->TEST_CACHE_NAME, $dictionaryName, $fields);
    }

    public function testDictionaryGetBatchWithEmptyFieldsIsError()
    {
        $dictionaryName = uniqid();
        $fields = [""];
        $response = $this->client->dictionaryGetBatch($this->TEST_CACHE_NAME, $dictionaryName, $fields);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());

        $fields = [];
        $response = $this->client->dictionaryGetBatch($this->TEST_CACHE_NAME, $dictionaryName, $fields);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionaryGetBatchHappyPath()
    {
        $dictionaryName = uniqid();
        $field1 = uniqid();
        $field2 = uniqid();
        $field3 = uniqid();
        $value1 = uniqid();
        $value2 = uniqid();
        $items = [$field1 => $value1, $field2 => $value2];
        $response = $this->client->dictionarySetBatch($this->TEST_CACHE_NAME, $dictionaryName, $items, false, 10);
        $this->assertNotNull($response->asSuccess());

        $response = $this->client->dictionaryGetBatch($this->TEST_CACHE_NAME, $dictionaryName, [$field1, $field2, $field3]);
        $this->assertNotNull($response->asSuccess());
        $values = [$value1, $value2, null];
        $this->assertEquals($values, $response->asSuccess()->values());
    }

    public function testDictionaryGetBatchDictionaryMissingHappyPath()
    {
        $dictionaryName = uniqid();
        $field1 = uniqid();
        $field2 = uniqid();
        $field3 = uniqid();
        $response = $this->client->dictionaryGetBatch($this->TEST_CACHE_NAME, $dictionaryName, [$field1, $field2, $field3]);
        $this->assertNotNull($response->asSuccess());
        $values = [null, null, null];
        $this->assertEquals($values, $response->asSuccess()->values());
    }
}
