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

    private function getBadAuthTokenClient(): SimpleCacheClient
    {
        $badEnvName = "_MOMENTO_BAD_AUTH_TOKEN";
        putenv("{$badEnvName}={$this->BAD_AUTH_TOKEN}");
        $authProvider = new EnvMomentoTokenProvider($badEnvName);
        putenv($badEnvName);
        return new SimpleCacheClient($authProvider, $this->DEFAULT_TTL_SECONDS);
    }

    public function tearDown(): void
    {
        $this->client->deleteCache($this->TEST_CACHE_NAME);
    }

    // Happy path test

    public function Create_Set_Get_Delete_HappyPath()
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

    public function NegativeTTL_IsError()
    {
        $this->expectExceptionMessage("TTL Seconds must be a non-negative integer");
        $client = new SimpleCacheClient($this->authProvider, -1);
    }

    // Client initialization tests

    public function InvalidAuthToken_IsError()
    {
        $AUTH_TOKEN = "notanauthtoken";
        $this->expectExceptionMessage("Invalid Momento auth token.");
        AuthUtils::parseAuthToken($AUTH_TOKEN);
        $AUTH_TOKEN = "not.anauth.token";
        $this->expectExceptionMessage("Invalid Momento auth token.");
        AuthUtils::parseAuthToken($AUTH_TOKEN);
    }

    public function NegativeRequestTime_IsError()
    {
        $this->expectExceptionMessage("Request timeout must be greater than zero.");
        $client = new SimpleCacheClient($this->authProvider, $this->DEFAULT_TTL_SECONDS, -1);
    }

    public function ZeroRequestTimeout_IsError()
    {
        $this->expectExceptionMessage("Request timeout must be greater than zero.");
        $client = new SimpleCacheClient($this->authProvider, $this->DEFAULT_TTL_SECONDS, 0);
    }

    // Create cache tests

    public function CreateCache_IsAlreadyExists()
    {
        $response = $this->client->createCache($this->TEST_CACHE_NAME);
        $this->assertNotNull($response->asAlreadyExists());
    }

    public function CreateCache_EmptyName_IsError()
    {
        $response = $this->client->createCache("");
        $this->assertNotNull($response->asError());
        $response = $response->asError();
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->errorCode());
        $this->assertEquals(get_class($response) . ": {$response->message()}", "$response");
    }

    public function CreateCache_NullName_IsError()
    {
        $this->expectException(TypeError::class);
        $this->client->createCache(null);
    }

    public function CreateCache_InvalidName_IsError()
    {
        $response = $this->client->createCache(1);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function CreateCache_InvalidToken_IsError()
    {
        $client = $this->getBadAuthTokenClient();
        $response = $client->createCache(uniqid());
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::AUTHENTICATION_ERROR, $response->asError()->errorCode());
    }

    // Delete cache tests

    public function DeleteCache_HappyPath()
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

    public function DeleteCache_UnknownCacheName_IsError()
    {
        $cacheName = uniqid();
        $response = $this->client->deleteCache($cacheName);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function DeleteCache_NullCacheName_IsError()
    {
        $this->expectException(TypeError::class);
        $this->client->deleteCache(null);
    }

    public function DeleteCache_EmptyCacheName_IsError()
    {
        $response = $this->client->deleteCache("");
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function DeleteCache_InvalidToken_IsError()
    {
        $client = $this->getBadAuthTokenClient();
        $response = $client->deleteCache(uniqid());
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::AUTHENTICATION_ERROR, $response->asError()->errorCode());
    }

    // List caches tests
    public function ListCache_HappyPath()
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

    public function ListCache_InvalidToken_IsError()
    {
        $client = $this->getBadAuthTokenClient();
        $response = $client->listCaches();
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::AUTHENTICATION_ERROR, $response->asError()->errorCode());
    }

    public function ListCache_NextToken()
    {
        $this->markTestSkipped("pagination not yet implemented");
    }

    // Setting and getting tests
    public function CacheSetGet_HappyPath()
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

    public function CacheGet_IsMiss()
    {
        $key = uniqid();
        $getResp = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNotNull($getResp->asMiss());
    }

    public function CacheSetGet_ExpiresAfterTtl()
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

    public function CacheSetGet_VariousTtls()
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

    public function CacheSet_WithNonExistentCacheName_IsError()
    {
        $cacheName = uniqid();
        $response = $this->client->set($cacheName, "key", "value");
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function CacheSet_WithNullCacheName_IsError()
    {
        $this->expectException(TypeError::class);
        $this->client->set(null, "key", "value");
    }

    public function CacheSet_WithEmptyCacheName_IsError()
    {
        $response = $this->client->set("", "key", "value");
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function CacheSet_WithNullKey_IsError()
    {
        $this->expectException(TypeError::class);
        $this->client->set($this->TEST_CACHE_NAME, null, "value");
    }

    public function CacheSet_WithNullValue_IsError()
    {
        $this->expectException(TypeError::class);
        $this->client->set($this->TEST_CACHE_NAME, "key", null);
    }

    public function CacheSet_WithNegativeTtl_IsError()
    {
        $response = $this->client->set($this->TEST_CACHE_NAME, "key", "value", -1);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function CacheSet_WithInvalidKey_IsError()
    {
        $this->expectException(TypeError::class);
        $this->client->set($this->TEST_CACHE_NAME, null, "bar");
    }

    public function CacheSet_WithInvalidValue_IsError()
    {
        $this->expectException(TypeError::class);
        $this->client->set($this->TEST_CACHE_NAME, "foo", null);
    }

    public function CacheSet_WithInvalidToken_IsError()
    {
        $client = $this->getBadAuthTokenClient();
        $response = $client->set($this->TEST_CACHE_NAME, "foo", "bar");
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::AUTHENTICATION_ERROR, $response->asError()->errorCode());
    }

    // Get tests
    public function CacheGet_WithNonExistentCacheName_IsError()
    {
        $cacheName = uniqid();
        $response = $this->client->get($cacheName, "foo");
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function CacheGet_WithNullCacheName_IsError()
    {
        $this->expectException(TypeError::class);
        $this->client->get(null, "foo");
    }

    public function CacheGet_WithEmptyCacheName_IsError()
    {
        $response = $this->client->get("", "foo");
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function CacheGet_WithNullKey_IsError()
    {
        $this->expectException(TypeError::class);
        $this->client->get($this->TEST_CACHE_NAME, null);
    }

    public function CacheGet_WithInvalidToken_IsError()
    {
        $client = $this->getBadAuthTokenClient();
        $response = $client->get($this->TEST_CACHE_NAME, "key");
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::AUTHENTICATION_ERROR, $response->asError()->errorCode());
    }

    public function CacheGet_WithShortTimeout_IsError()
    {
        $client = new SimpleCacheClient($this->authProvider, $this->DEFAULT_TTL_SECONDS, 1);
        $response = $client->get($this->TEST_CACHE_NAME, "key");
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::TIMEOUT_ERROR, $response->asError()->errorCode());
    }

    // Delete tests

    public function CacheDelete_WithNonExistentKey_IsError()
    {
        $key = "a key that isn't there";
        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNotNull($response->asMiss());
        $response = $this->client->delete($this->TEST_CACHE_NAME, $key);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNotNull($response->asMiss());
    }

    public function CacheDelete_HappyPath()
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

    public function ListPushFrontFetch_HappyPath()
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

    public function ListPushFront_WithoutRefreshTtl_HappyPath()
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

    public function ListPushFront_WithRefreshTtl_HappyPath()
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

    public function cHappyPath()
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

    public function ListPushFront_WithNegativeTruncateSize_IsError()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, false, truncateBackToSize: -1);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function ListPushBackFetch_HappyPath()
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

    public function ListPushBack_WithoutRefreshTtl_HappyPath()
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

    public function ListPushBack_WithRefreshTtl_HappyPath()
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

    public function ListPushBack_WithTruncateSize_HappyPath()
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

    public function ListPushBack_WithNegativeTruncateSize_IsError()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, false, truncateFrontToSize: -1);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function ListPopFront_Miss_HappyPath()
    {
        $listName = uniqid();
        $response = $this->client->listPopFront($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asMiss());
    }

    public function ListPopFront_HappyPath()
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

    public function ListPopBack_Miss_HappyPath()
    {
        $listName = uniqid();
        $response = $this->client->listPopBack($this->TEST_CACHE_NAME, $listName);
        $this->assertNotNull($response->asMiss());
    }

    public function ListPopFront_EmptyList_HappyPath()
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

    public function ListPopBack_HappyPath()
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

    public function ListPopBack_EmptyList_HappyPath()
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

    public function ListRemoveValue_HappyPath()
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

    public function ListRemoveValues_ValueNotPresent_IsError()
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

    public function ListLength_HappyPath()
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

    public function ListLength_MissingList_HappyPath()
    {
        $response = $this->client->listLength($this->TEST_CACHE_NAME, uniqid());
        $this->assertNotNull($response->asSuccess());
        $this->assertEquals(0, $response->asSuccess()->length());
    }

    // List erase
    public function ListEraseAll_HappyPath()
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

    public function ListEraseRange_HappyPath()
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

    public function ListEraseRange_LargeCountValue_HappyPath()
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

    public function ListErase_MissingList_HappyPath()
    {
        $response = $this->client->listErase($this->TEST_CACHE_NAME, uniqid());
        $this->assertNotNull($response->asSuccess());
    }

    // Dictionary tests
    public function DictionaryGet_IsMissing()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNotNull($response->asMiss());
    }

    public function DictionarySetGet_HappyPath()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = uniqid();
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, false);
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNotNull($response->asHit());
    }

    public function DictionarySetGet_FieldMissing()
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

    public function DictionarySet_WithoutRefreshTtl_HappyPath()
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

    public function DictionaryGet_WithEmptyDictionaryName_IsError()
    {
        $dictionaryName = "";
        $field = uniqid();
        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function DictionaryGet_WithEmptyFieldName_IsError()
    {
        $dictionaryName = uniqid();
        $field = "";
        $response = $this->client->dictionaryGet($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function DictionaryGet_WithEmptyValue_IsError()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = "";
        $response = $this->client->dictionarySet($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, false);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function DictionarySet_WithRefreshTtl_HappyPath()
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

    public function DictionaryDelete_WithEmptyCacheName_IsError()
    {
        $cacheName = "";
        $dictionaryName = uniqid();
        $response = $this->client->dictionaryDelete($cacheName, $dictionaryName);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function DictionaryDelete_WithEmptyDictionaryName_IsError()
    {
        $dictionaryName = "";
        $response = $this->client->dictionaryDelete($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function DictionaryDelete_HappyPath()
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
