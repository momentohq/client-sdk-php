<?php

namespace Momento\Tests\Cache;

use Momento\Auth\AuthUtils;
use Momento\Auth\EnvMomentoTokenProvider;
use Momento\Cache\CacheOperationTypes\CacheGetStatus;
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

    public function tearDown(): void
    {
        $this->client->deleteCache($this->TEST_CACHE_NAME);
    }

    public function testCreateSetGetDelete()
    {
        $cacheName = uniqid();
        $key = uniqid();
        $value = uniqid();
        $response = $this->client->createCache($cacheName);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->set($cacheName, $key, $value);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->get($cacheName, $key);
        $this->assertNotNull($response->asHit());
        $response = $response->asHit();
        $this->assertEquals($response->value(), $value);
        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNotNull($response->asMiss());
        $response = $this->client->deleteCache($cacheName);
        $this->assertNotNull($response->asSuccess());
    }

    // Happy path test

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

    public function testCreateCacheAlreadyExists()
    {
        $response = $this->client->createCache($this->TEST_CACHE_NAME);
        $this->assertNotNull($response->asAlreadyExists());
    }

    // Create cache tests

    public function testCreateCacheEmptyName()
    {
        $response = $this->client->createCache("");
        $this->assertNotNull($response->asError());
        $response = $response->asError();
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->errorCode());
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

    private function getBadAuthTokenClient(): SimpleCacheClient
    {
        $badEnvName = "_MOMENTO_BAD_AUTH_TOKEN";
        putenv("{$badEnvName}={$this->BAD_AUTH_TOKEN}");
        $authProvider = new EnvMomentoTokenProvider($badEnvName);
        putenv($badEnvName);
        return new SimpleCacheClient($authProvider, $this->DEFAULT_TTL_SECONDS);
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
        } finally {
            $this->client->deleteCache($cacheName);
        }
    }

    public function testListCachesBadAuth()
    {
        $client = $this->getBadAuthTokenClient();
        $respnse = $client->listCaches();
        $this->assertNotNull($respnse->asError());
        $this->assertEquals(MomentoErrorCode::AUTHENTICATION_ERROR, $respnse->asError()->errorCode());
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
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asHit());
        $values = $response->asHit()->values();
        $this->assertNotEmpty($values);
        $this->assertCount(1, $values);
        $this->assertContains($value, $values);

        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value2, true, 6000);
        $this->assertNotNull($response->asSuccess());
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

    public function testListPushBackFetchHappyPath()
    {
        $listName = uniqid();
        $value = uniqid();
        $value2 = uniqid();
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, true, 6000);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asHit());
        $values = $response->asHit()->values();
        $this->assertNotEmpty($values);
        $this->assertCount(1, $values);
        $this->assertContains($value, $values);

        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value2, true, 6000);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asHit());
        $values = $response->asHit()->values();
        $this->assertNotEmpty($values);
        $this->assertEquals([$value, $value2], $values);
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
}
