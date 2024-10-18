<?php
declare(strict_types=1);

namespace Momento\Tests\Cache;

use Momento\Auth\AuthUtils;
use Momento\Auth\EnvMomentoTokenProvider;
use Momento\Cache\CacheOperationTypes\DictionaryGetFieldHit;
use Momento\Cache\CacheOperationTypes\DictionaryGetFieldMiss;
use Momento\Cache\CacheOperationTypes\GetHit;
use Momento\Cache\CacheOperationTypes\GetMiss;
use Momento\Cache\Errors\MomentoErrorCode;
use Momento\Cache\CacheClient;
use Momento\Config\Configuration;
use Momento\Config\Configurations;
use Momento\Config\IConfiguration;
use Momento\Config\Transport\StaticGrpcConfiguration;
use Momento\Config\Transport\StaticTransportStrategy;
use Momento\Logging\NullLoggerFactory;
use Momento\Requests\CollectionTtl;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * @covers CacheClient
 */
class CacheClientTest extends TestCase
{
    private IConfiguration $configuration;
    private EnvMomentoTokenProvider $authProvider;
    private int $DEFAULT_TTL_SECONDS = 10;
    private CacheClient $client;
    private string $TEST_CACHE_NAME;
    private string $BAD_AUTH_TOKEN = "eyJhbGciOiJIUzUxMiJ9.eyJzdWIiOiJpbnRlZ3JhdGlvbiIsImNwIjoiY29udHJvbC5jZWxsLWFscGhhLWRldi5wcmVwcm9kLmEubW9tZW50b2hxLmNvbSIsImMiOiJjYWNoZS5jZWxsLWFscGhhLWRldi5wcmVwcm9kLmEubW9tZW50b2hxLmNvbSJ9.gdghdjjfjyehhdkkkskskmmls76573jnajhjjjhjdhnndy";

    public function setUp(): void
    {
        $this->configuration = Configurations\Laptop::latest();
        $this->authProvider = new EnvMomentoTokenProvider("MOMENTO_API_KEY");
        $this->client = new CacheClient($this->configuration, $this->authProvider, $this->DEFAULT_TTL_SECONDS);
        $this->TEST_CACHE_NAME = uniqid('php-integration-tests-');
        // Ensure test cache exists
        $createResponse = $this->client->createCache($this->TEST_CACHE_NAME);
        if ($createError = $createResponse->asError()) {
            throw $createError->innerException();
        }
    }

    public function tearDown() : void {
        $deleteResponse = $this->client->deleteCache($this->TEST_CACHE_NAME);
        if ($deleteError = $deleteResponse->asError()) {
            throw $deleteError->innerException();
        }
        $this->client->close();
    }

    private function getBadAuthTokenClient(): CacheClient
    {
        $badEnvName = "_MOMENTO_BAD_AUTH_TOKEN";
        $_SERVER[$badEnvName] = $this->BAD_AUTH_TOKEN;
        $authProvider = new EnvMomentoTokenProvider($badEnvName);
        unset($_SERVER[$badEnvName]);
        return new CacheClient($this->configuration, $authProvider, $this->DEFAULT_TTL_SECONDS);
    }

    private function getConfigurationWithDeadline(int $deadline)
    {
        $loggerFactory = new NullLoggerFactory();
        $grpcConfig = new StaticGrpcConfiguration($deadline);
        $transportStrategy = new StaticTransportStrategy($grpcConfig, $loggerFactory);
        return new Configuration($loggerFactory, $transportStrategy);
    }

    public function testCreateAndCloseClient() {
        $client = new CacheClient($this->configuration, $this->authProvider, $this->DEFAULT_TTL_SECONDS);
        $response = $client->listCaches();
        $this->assertNull($response->asError());
        $client->close();
        $client = new CacheClient($this->configuration, $this->authProvider, $this->DEFAULT_TTL_SECONDS);
        $response = $client->listCaches();
        $this->assertNull($response->asError());
        $client->close();
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
        $this->assertEquals("$response", get_class($response));

        $response = $this->client->get($cacheName, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $response = $response->asHit();
        $this->assertEquals($response->valueString(), $value);
        $this->assertEquals("$response", get_class($response) . ": $value");

        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
        $response = $this->client->deleteCache($cacheName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
    }

    public function testNegativeDefaultTtl()
    {
        $this->expectExceptionMessage("TTL Seconds must be a non-negative number");
        $client = new CacheClient($this->configuration, $this->authProvider, -1);
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
        $configuration = $this->getConfigurationWithDeadline(-1);
        $client = new CacheClient($configuration, $this->authProvider, $this->DEFAULT_TTL_SECONDS);
    }

    public function testZeroRequestTimeout()
    {
        $this->expectExceptionMessage("Request timeout must be greater than zero.");
        $configuration = $this->getConfigurationWithDeadline(0);
        $client = new CacheClient($configuration, $this->authProvider, $this->DEFAULT_TTL_SECONDS);
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
        $this->assertEquals("$response", get_class($response) . ": {$response->message()}");
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
            $this->assertEquals("$listCachesResponse", get_class($listCachesResponse) . ": " . join(', ', $cacheNames));
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

        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $response = $response->asHit();
        $this->assertNotNull($response);
        $this->assertEquals($value, $response->valueString());
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
        $client = new CacheClient($this->configuration, $this->authProvider, 2);
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
        $configuration = $this->getConfigurationWithDeadline(1);
        $client = new CacheClient($configuration, $this->authProvider, $this->DEFAULT_TTL_SECONDS);
        $response = $client->get($this->TEST_CACHE_NAME, "key");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::TIMEOUT_ERROR, $response->asError()->errorCode());
    }

    // SetIfPresent tests

    public function testSetIfPresent()
    {
        $key = uniqid();
        $value = uniqid();
        $value2 = uniqid();

        $response = $this->client->set($this->TEST_CACHE_NAME, $key, $value);
        $this->assertNull($response->asError());

        $response = $this->client->setIfPresent($this->TEST_CACHE_NAME, $key, $value2);
        $this->assertNull($response->asError());
        $this->assertNull($response->asNotStored());
        $response = $response->asStored();
        $this->assertNotNull($response);
        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit());
        $this->assertEquals($value2, $response->asHit()->valueString());

        $response = $this->client->delete($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->setIfPresent($this->TEST_CACHE_NAME, $key, $value2);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asNotStored());
        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss());
    }

    public function testSetIfPresentWithNonexistentCache()
    {
        $cacheName = uniqid();
        $response = $this->client->setIfPresent($cacheName, "key", "value");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::CACHE_NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testSetIfPresentWithNullCacheName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfPresent(null, "key", "value");
    }

    public function testSetIfPresentWithEmptyCacheName()
    {
        $response = $this->client->setIfPresent("", "key", "value");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetIfPresentWithNullKey_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfPresent($this->TEST_CACHE_NAME, null, "value");
    }

    public function testSetIfPresentWithNullValue_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfPresent($this->TEST_CACHE_NAME, "key", null);
    }

    public function testSetIfPresentNegativeTtl()
    {
        $response = $this->client->setIfPresent($this->TEST_CACHE_NAME, "key", "value", -1);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetIfPresentBadKey()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfPresent($this->TEST_CACHE_NAME, null, "bar");
    }

    public function testSetIfPresentBadValue()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfPresent($this->TEST_CACHE_NAME, "foo", null);
    }

    public function testSetIfPresentBadAuth()
    {
        $client = $this->getBadAuthTokenClient();
        $response = $client->setIfPresent($this->TEST_CACHE_NAME, "foo", "bar");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::AUTHENTICATION_ERROR, $response->asError()->errorCode());
    }

    // SetIfPresentAndNotEqual tests

    public function testSetIfPresentAndNotEqual()
    {
        $key = uniqid();
        $value = uniqid();
        $value2 = uniqid();

        $response = $this->client->set($this->TEST_CACHE_NAME, $key, $value);
        $this->assertNull($response->asError());

        $response = $this->client->setIfPresentAndNotEqual($this->TEST_CACHE_NAME, $key, $value2, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asNotStored());

        $response = $this->client->setIfPresentAndNotEqual($this->TEST_CACHE_NAME, $key, $value2, uniqid());
        $response = $response->asStored();
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asStored());

        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit());
        $this->assertEquals($value2, $response->asHit()->valueString());

        $response = $this->client->delete($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess());

        $response = $this->client->setIfPresentAndNotEqual($this->TEST_CACHE_NAME, $key, $value2, uniqid());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asNotStored());
        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss());
    }

    public function testSetIfPresentAndNotEqualWithNonexistentCache()
    {
        $cacheName = uniqid();
        $response = $this->client->setIfPresentAndNotEqual($cacheName, "key", "value", uniqid());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testSetIfPresentAndNotEqualWithNullCacheName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfPresentAndNotEqual(null, "key", "value", uniqid());
    }

    public function testSetIfPresentAndNotEqualWithEmptyCacheName()
    {
        $response = $this->client->setIfPresentAndNotEqual("", "key", "value", uniqid());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetIfPresentAndNotEqualWithNullKey_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfPresentAndNotEqual($this->TEST_CACHE_NAME, null, "value", uniqid());
    }

    public function testSetIfPresentAndNotEqualWithNullValue_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfPresentAndNotEqual($this->TEST_CACHE_NAME, "key", null, uniqid());
    }

    public function testSetIfPresentAndNotEqualNegativeTtl()
    {
        $response = $this->client->setIfPresentAndNotEqual($this->TEST_CACHE_NAME, "key", "value", uniqid(), -1);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetIfPresentAndNotEqualBadKey()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfPresentAndNotEqual($this->TEST_CACHE_NAME, null, "bar", uniqid());
    }

    public function testSetIfPresentAndNotEqualBadValue()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfPresentAndNotEqual($this->TEST_CACHE_NAME, "foo", null, uniqid());
    }

    public function testSetIfPresentAndNotEqualBadAuth()
    {
        $client = $this->getBadAuthTokenClient();
        $response = $client->setIfPresentAndNotEqual($this->TEST_CACHE_NAME, "foo", "bar", uniqid());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::AUTHENTICATION_ERROR, $response->asError()->errorCode());
    }

    // SetIfAbsent tests

    public function testSetIfAbsent()
    {
        $key = uniqid();
        $value = uniqid();
        $value2 = uniqid();

        $response = $this->client->setIfAbsent($this->TEST_CACHE_NAME, $key, $value2);
        $this->assertNull($response->asError());
        $this->assertNull($response->asNotStored());
        $response = $response->asStored();
        $this->assertNotNull($response);
        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit());
        $this->assertEquals($value2, $response->asHit()->valueString());

        $response = $this->client->delete($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess());
        $response = $this->client->set($this->TEST_CACHE_NAME, $key, $value);
        $this->assertNull($response->asError());
        $response = $this->client->setIfAbsent($this->TEST_CACHE_NAME, $key, $value2);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asNotStored());
        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit());
        $this->assertEquals($value, $response->asHit()->valueString());
    }

    public function testSetIfAbsentWithNonexistentCache()
    {
        $cacheName = uniqid();
        $response = $this->client->setIfAbsent($cacheName, "key", "value");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testSetIfAbsentWithNullCacheName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfAbsent(null, "key", "value");
    }

    public function testSetIfAbsentWithEmptyCacheName()
    {
        $response = $this->client->setIfAbsent("", "key", "value");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetIfAbsentWithNullKey_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfAbsent($this->TEST_CACHE_NAME, null, "value");
    }

    public function testSetIfAbsentWithNullValue_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfAbsent($this->TEST_CACHE_NAME, "key", null);
    }

    public function testSetIfAbsentNegativeTtl()
    {
        $response = $this->client->setIfAbsent($this->TEST_CACHE_NAME, "key", "value", -1);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetIfAbsentBadKey()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfAbsent($this->TEST_CACHE_NAME, null, "bar");
    }

    public function testSetIfAbsentBadValue()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfAbsent($this->TEST_CACHE_NAME, "foo", null);
    }

    public function testSetIfAbsentBadAuth()
    {
        $client = $this->getBadAuthTokenClient();
        $response = $client->setIfAbsent($this->TEST_CACHE_NAME, "foo", "bar");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::AUTHENTICATION_ERROR, $response->asError()->errorCode());
    }

    // SetIfAbsentOrEqual tests

    public function testSetIfAbsentOrEqual()
    {
        $key = uniqid();
        $value = uniqid();
        $value2 = uniqid();

        // absent - set
        $response = $this->client->setIfAbsentOrEqual($this->TEST_CACHE_NAME, $key, $value, $value2);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asStored());

        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit());
        $this->assertEquals($value, $response->asHit()->valueString());

        // present and equal - set
        $response = $this->client->setIfAbsentOrEqual($this->TEST_CACHE_NAME, $key, $value2, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asStored());

        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit());
        $this->assertEquals($value2, $response->asHit()->valueString());

        // present and not equal - don't set
        $response = $this->client->setIfAbsentOrEqual($this->TEST_CACHE_NAME, $key, $value, uniqid());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asNotStored());

        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit());
        $this->assertEquals($value2, $response->asHit()->valueString());
    }

    public function testSetIfAbsentOrEqualWithNonexistentCache()
    {
        $cacheName = uniqid();
        $response = $this->client->setIfAbsentOrEqual($cacheName, "key", "value", uniqid());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testSetIfAbsentOrEqualWithNullCacheName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfAbsentOrEqual(null, "key", "value", uniqid());
    }

    public function testSetIfAbsentOrEqualWithEmptyCacheName()
    {
        $response = $this->client->setIfAbsentOrEqual("", "key", "value", uniqid());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetIfAbsentOrEqualWithNullKey_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfAbsentOrEqual($this->TEST_CACHE_NAME, null, "value", uniqid());
    }

    public function testSetIfAbsentOrEqualWithNullValue_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfAbsentOrEqual($this->TEST_CACHE_NAME, "key", null, uniqid());
    }

    public function testSetIfAbsentOrEqualNegativeTtl()
    {
        $response = $this->client->setIfAbsentOrEqual($this->TEST_CACHE_NAME, "key", "value", uniqid(), -1);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetIfAbsentOrEqualBadKey()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfAbsentOrEqual($this->TEST_CACHE_NAME, null, "bar", uniqid());
    }

    public function testSetIfAbsentOrEqualBadValue()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfAbsentOrEqual($this->TEST_CACHE_NAME, "foo", null, uniqid());
    }

    public function testSetIfAbsentOrEqualBadAuth()
    {
        $client = $this->getBadAuthTokenClient();
        $response = $client->setIfAbsentOrEqual($this->TEST_CACHE_NAME, "foo", "bar", uniqid());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::AUTHENTICATION_ERROR, $response->asError()->errorCode());
    }

    // SetIfEqual tests

    public function testSetIfEqual()
    {
        $key = uniqid();
        $value = uniqid();
        $value2 = uniqid();

        // absent - not set
        $response = $this->client->setIfEqual($this->TEST_CACHE_NAME, $key, $value, $value2);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asNotStored());

        // present and equal - set
        $response = $this->client->set($this->TEST_CACHE_NAME, $key, $value);
        $this->assertNull($response->asError());

        $response = $this->client->setIfEqual($this->TEST_CACHE_NAME, $key, $value2, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asStored());

        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit());
        $this->assertEquals($value2, $response->asHit()->valueString());

        // present and not equal - don't set
        $response = $this->client->setIfEqual($this->TEST_CACHE_NAME, $key, $value, uniqid());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asNotStored());

        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit());
        $this->assertEquals($value2, $response->asHit()->valueString());
    }

    public function testSetIfEqualWithNonexistentCache()
    {
        $cacheName = uniqid();
        $response = $this->client->setIfEqual($cacheName, "key", "value", uniqid());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testSetIfEqualWithNullCacheName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfEqual(null, "key", "value", uniqid());
    }

    public function testSetIfEqualWithEmptyCacheName()
    {
        $response = $this->client->setIfEqual("", "key", "value", uniqid());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetIfEqualWithNullKey_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfEqual($this->TEST_CACHE_NAME, null, "value", uniqid());
    }

    public function testSetIfEqualWithNullValue_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfEqual($this->TEST_CACHE_NAME, "key", null, uniqid());
    }

    public function testSetIfEqualNegativeTtl()
    {
        $response = $this->client->setIfEqual($this->TEST_CACHE_NAME, "key", "value", uniqid(), -1);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetIfEqualBadKey()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfEqual($this->TEST_CACHE_NAME, null, "bar", uniqid());
    }

    public function testSetIfEqualBadValue()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfEqual($this->TEST_CACHE_NAME, "foo", null, uniqid());
    }

    public function testSetIfEqualBadAuth()
    {
        $client = $this->getBadAuthTokenClient();
        $response = $client->setIfEqual($this->TEST_CACHE_NAME, "foo", "bar", uniqid());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::AUTHENTICATION_ERROR, $response->asError()->errorCode());
    }

    // SetIfNotEqual tests

    public function testSetIfNotEqual()
    {
        $key = uniqid();
        $value = uniqid();
        $value2 = uniqid();

        // absent - set
        $response = $this->client->setIfNotEqual($this->TEST_CACHE_NAME, $key, $value, $value2);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asStored());

        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit());
        $this->assertEquals($value, $response->asHit()->valueString());

        // present and equal - not set
        $response = $this->client->setIfNotEqual($this->TEST_CACHE_NAME, $key, $value2, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asNotStored());

        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit());
        $this->assertEquals($value, $response->asHit()->valueString());

        // present and not equal - set
        $response = $this->client->setIfNotEqual($this->TEST_CACHE_NAME, $key, $value2, uniqid());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asStored());

        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit());
        $this->assertEquals($value2, $response->asHit()->valueString());
    }

    public function testSetIfNotEqualWithNonexistentCache()
    {
        $cacheName = uniqid();
        $response = $this->client->setIfNotEqual($cacheName, "key", "value", uniqid());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testSetIfNotEqualWithNullCacheName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfNotEqual(null, "key", "value", uniqid());
    }

    public function testSetIfNotEqualWithEmptyCacheName()
    {
        $response = $this->client->setIfNotEqual("", "key", "value", uniqid());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetIfNotEqualWithNullKey_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfNotEqual($this->TEST_CACHE_NAME, null, "value", uniqid());
    }

    public function testSetIfNotEqualWithNullValue_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfNotEqual($this->TEST_CACHE_NAME, "key", null, uniqid());
    }

    public function testSetIfNotEqualNegativeTtl()
    {
        $response = $this->client->setIfNotEqual($this->TEST_CACHE_NAME, "key", "value", uniqid(), -1);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetIfNotEqualBadKey()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfNotEqual($this->TEST_CACHE_NAME, null, "bar", uniqid());
    }

    public function testSetIfNotEqualBadValue()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfNotEqual($this->TEST_CACHE_NAME, "foo", null, uniqid());
    }

    public function testSetIfNotEqualBadAuth()
    {
        $client = $this->getBadAuthTokenClient();
        $response = $client->setIfNotEqual($this->TEST_CACHE_NAME, "foo", "bar", uniqid());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::AUTHENTICATION_ERROR, $response->asError()->errorCode());
    }

    // SetIfNotExists tests

    public function testSetIfNotExists()
    {
        $key = uniqid();
        $value = uniqid();

        $response = $this->client->setIfNotExists($this->TEST_CACHE_NAME, $key, $value);
        $this->assertNull($response->asError());
        $this->assertNull($response->asNotStored());
        $response = $response->asStored();
        $this->assertNotNull($response);

        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $response = $response->asHit();
        $this->assertNotNull($response);
        $this->assertEquals($value, $response->valueString());

        $response = $this->client->setIfNotExists($this->TEST_CACHE_NAME, $key, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asNotStored());
        $this->assertNull($response->asStored());
    }

    public function testSetIfNotExistsWithNonexistentCache()
    {
        $cacheName = uniqid();
        $response = $this->client->setIfNotExists($cacheName, "key", "value");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testSetIfNotExistsWithNullCacheName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfNotExists(null, "key", "value");
    }

    public function testSetIfNotExistsWithEmptyCacheName()
    {
        $response = $this->client->setIfNotExists("", "key", "value");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetIfNotExistsWithNullKey_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfNotExists($this->TEST_CACHE_NAME, null, "value");
    }

    public function testSetIfNotExistsWithNullValue_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfNotExists($this->TEST_CACHE_NAME, "key", null);
    }

    public function testSetIfNotExistsNegativeTtl()
    {
        $response = $this->client->setIfNotExists($this->TEST_CACHE_NAME, "key", "value", -1);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetIfNotExistsBadKey()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfNotExists($this->TEST_CACHE_NAME, null, "bar");
    }

    public function testSetIfNotExistsBadValue()
    {
        $this->expectException(TypeError::class);
        $this->client->setIfNotExists($this->TEST_CACHE_NAME, "foo", null);
    }

    public function testSetIfNotExistsBadAuth()
    {
        $client = $this->getBadAuthTokenClient();
        $response = $client->setIfNotExists($this->TEST_CACHE_NAME, "foo", "bar");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::AUTHENTICATION_ERROR, $response->asError()->errorCode());
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

    // Keys Exist tests
    public function testKeysExist() {
        $keysToSet = ["key1", "key2", "key3"];
        foreach ($keysToSet as $key) {
            $response = $this->client->set($this->TEST_CACHE_NAME, $key, "hi");
            $this->assertNull($response->asError());
        }

        $keysToTestAllHits = $keysToSet;
        $keysToTestAllMisses = ["nope1", "nope2", "nope3"];
        $keysToTestMixed = array_merge($keysToTestAllHits, $keysToTestAllMisses);
        $expectAllHits = array_map(function() { return true; }, $keysToTestAllHits);
        $expectAllHitsDict = array_combine($keysToTestAllHits, $expectAllHits);
        $expectAllMisses = array_map(function() { return false; }, $keysToTestAllMisses);
        $expectAllMissesDict = array_combine($keysToTestAllMisses, $expectAllMisses);
        $expectMixed = array_map(function($v) { return str_starts_with($v, "key"); }, $keysToTestMixed);
        $expectMixedDict = array_combine($keysToTestMixed, $expectMixed);

        $response = $this->client->keysExist($this->TEST_CACHE_NAME, $keysToTestAllHits);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals($response->asSuccess()->exists(), $expectAllHits);
        $this->assertEquals($response->asSuccess()->existsDictionary(), $expectAllHitsDict);

        $response = $this->client->keysExist($this->TEST_CACHE_NAME, $keysToTestAllMisses);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals($response->asSuccess()->exists(), $expectAllMisses);
        $this->assertEquals($response->asSuccess()->existsDictionary(), $expectAllMissesDict);

        $response = $this->client->keysExist($this->TEST_CACHE_NAME, $keysToTestMixed);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals($response->asSuccess()->exists(), $expectMixed);
        $this->assertEquals($response->asSuccess()->existsDictionary(), $expectMixedDict);
    }

    public function testKeyExists() {
        $keysToSet = ["key1", "key2", "key3"];
        foreach ($keysToSet as $key) {
            $response = $this->client->set($this->TEST_CACHE_NAME, $key, "hi");
            $this->assertNull($response->asError());
        }

        $response = $this->client->keyExists($this->TEST_CACHE_NAME, "key2");
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(true, $response->asSuccess()->exists());

        $response = $this->client->keyExists($this->TEST_CACHE_NAME, "nope99");
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(false, $response->asSuccess()->exists());
    }

    // Increment tests
    public function testIncrementHappyPath()
    {
        $key = uniqid();
        $response = $this->client->set($this->TEST_CACHE_NAME, $key, "5");
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $response = $this->client->increment($this->TEST_CACHE_NAME, $key);
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(6, $response->asSuccess()->value());
        $response = $this->client->increment($this->TEST_CACHE_NAME, $key, 4);
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(10, $response->asSuccess()->value());
    }

    public function testIncrementCreatesKey() {
        $key = uniqid();
        $response = $this->client->increment($this->TEST_CACHE_NAME, $key);
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(1, $response->asSuccess()->value());
    }

    public function testIncrementByZeroIsNoop()
    {
        $key = uniqid();
        $response = $this->client->set($this->TEST_CACHE_NAME, $key, "5");
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $response = $this->client->increment($this->TEST_CACHE_NAME, $key, 0);
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(5, $response->asSuccess()->value());
    }

    public function testNegativeIncrementHappyPath()
    {
        $key = uniqid();
        $response = $this->client->set($this->TEST_CACHE_NAME, $key, "5");
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $response = $this->client->increment($this->TEST_CACHE_NAME, $key, -4);
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(1, $response->asSuccess()->value());
    }

    public function testIncrementChangeTtl() {
        $key = uniqid();
        $response = $this->client->set($this->TEST_CACHE_NAME, $key, "5");
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $response = $this->client->increment($this->TEST_CACHE_NAME, $key, 4, 1);
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(9, $response->asSuccess()->value());
        sleep(1);
        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNotNull($response->asMiss());
    }

    // List API tests

    public function testListPushFrontFetchHappyPath()
    {
        $listName = uniqid();
        $value = uniqid();
        $value2 = uniqid();
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::of(6000));
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(1, $response->asSuccess()->listLength());

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $values = $response->asHit()->valuesArray();
        $this->assertNotEmpty($values);
        $this->assertCount(1, $values);
        $this->assertContains($value, $values);

        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value2, null, CollectionTtl::of(6000));
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(2, $response->asSuccess()->listLength());

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $values = $response->asHit()->valuesArray();
        $this->assertNotEmpty($values);
        $this->assertEquals([$value2, $value], $values);
    }

    public function testListPushFront_NoRefreshTtl()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::of(5)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::of(10)->withNoRefreshTtlOnUpdates());
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
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::of(2)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::of(10)->withRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        sleep(2);
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertCount(2, $response->asHit()->valuesArray());
    }

    public function testListPushFront_TruncateList()
    {
        $listName = uniqid();
        $value1 = uniqid();
        $value2 = uniqid();
        $value3 = uniqid();
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value1, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value2, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value3, 2, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals([$value3, $value2], $response->asHit()->valuesArray());
    }

    public function testListPushFront_TruncateList_NegativeValue()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, -1, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testListPushBackFetchHappyPath()
    {
        $listName = uniqid();
        $value = uniqid();
        $value2 = uniqid();
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::of(6000));
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(1, $response->asSuccess()->listLength());

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $values = $response->asHit()->valuesArray();
        $this->assertNotEmpty($values);
        $this->assertCount(1, $values);
        $this->assertContains($value, $values);

        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value2, null, CollectionTtl::of(6000));
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(2, $response->asSuccess()->listLength());

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $values = $response->asHit()->valuesArray();
        $this->assertNotEmpty($values);
        $this->assertEquals([$value, $value2], $values);
    }

    public function testListPushBack_NoRefreshTtl()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::of(5)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::of(10)->withNoRefreshTtlOnUpdates());
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
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::of(2)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::of(10));
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        sleep(2);
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertCount(2, $response->asHit()->valuesArray());
    }

    public function testListPushBack_TruncateList()
    {
        $listName = uniqid();
        $value1 = uniqid();
        $value2 = uniqid();
        $value3 = uniqid();
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value1, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value2, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value3, 2, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals([$value2, $value3], $response->asHit()->valuesArray());
    }

    public function testListPushBack_TruncateList_NegativeValue()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, -1, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
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
        foreach (range(0, 3) as $ignored) {
            $val = uniqid();
            $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $val, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
            $this->assertNull($response->asError());
            $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
            array_unshift($values, $val);
        }
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($values, $response->asHit()->valuesArray());
        while ($val = array_shift($values)) {
            $response = $this->client->listPopFront($this->TEST_CACHE_NAME, $listName);
            $this->assertNull($response->asError());
            $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
            $this->assertEquals($val, $response->asHit()->valueString());
            $this->assertEquals("$response", get_class($response) . ": {$response->asHit()->valueString()}");
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
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listPopFront($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($value, $response->asHit()->valueString());

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
        foreach (range(0, 3) as $ignored) {
            $val = uniqid();
            $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $val, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
            $this->assertNull($response->asError());
            $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
            $values[] = $val;
        }
        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($values, $response->asHit()->valuesArray());

        while ($val = array_pop($values)) {
            $response = $this->client->listPopBack($this->TEST_CACHE_NAME, $listName);
            $this->assertNull($response->asError());
            $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
            $this->assertEquals($val, $response->asHit()->valueString());
            $this->assertEquals("$response", get_class($response) . ": {$response->asHit()->valueString()}");
        }
    }

    public function testListPopBack_EmptyList()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listPopBack($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($value, $response->asHit()->valueString());

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
        foreach (range(0, 3) as $ignored) {
            $val = uniqid();
            $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $val, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
            $this->assertNull($response->asError());
            $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
            $values[] = $val;
        }
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $valueToRemove, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $valueToRemove, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $expectedValues = $values;
        array_push($expectedValues, $valueToRemove, $valueToRemove);
        $this->assertEquals($expectedValues, $response->asHit()->valuesArray());

        $response = $this->client->listRemoveValue($this->TEST_CACHE_NAME, $listName, $valueToRemove);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($values, $response->asHit()->valuesArray());
    }

    public function testListRemoveValues_ValueNotPresent()
    {
        $listName = uniqid();
        $values = [];
        foreach (range(0, 3) as $ignored) {
            $val = uniqid();
            $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $val, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
            $this->assertNull($response->asError());
            $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
            $values[] = $val;
        }

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($values, $response->asHit()->valuesArray());

        $response = $this->client->listRemoveValue($this->TEST_CACHE_NAME, $listName, "i-am-not-in-the-list");
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($values, $response->asHit()->valuesArray());
    }

    public function testListLength_HappyPath()
    {
        $listName = uniqid();
        foreach (range(0, 3) as $i) {
            $val = uniqid();
            $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $val, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
            $this->assertNull($response->asError());
            $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

            $response = $this->client->listLength($this->TEST_CACHE_NAME, $listName);
            $this->assertNull($response->asError());
            $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
            $this->assertEquals($i + 1, $response->asSuccess()->length());
            $this->assertEquals("$response", get_class($response) . ": {$response->asSuccess()->length()}");
        }
    }

    public function testListLength_MissingList()
    {
        $response = $this->client->listLength($this->TEST_CACHE_NAME, uniqid());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(0, $response->asSuccess()->length());
    }

    // Dictionary tests
    public function testDictionary_IsMissing()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testDictionary_HappyPath()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = uniqid();
        $response = $this->client->dictionarySetField($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
    }

    public function testDictionaryFieldMissing()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = uniqid();
        $response = $this->client->dictionarySetField($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $otherField = uniqid();
        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $otherField);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testDictionaryNoRefreshTtl()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = uniqid();
        $response = $this->client->dictionarySetField($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, CollectionTtl::of(5)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(1);

        $response = $this->client->dictionarySetField($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, CollectionTtl::of(10)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(4);

        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testDictionaryEmptyDictionaryName_IsError()
    {
        $dictionaryName = "";
        $field = uniqid();
        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionaryEmptyFieldName_IsError()
    {
        $dictionaryName = uniqid();
        $field = "";
        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionaryEmptyValue_IsError()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = "";
        $response = $this->client->dictionarySetField($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionaryRefreshTtl()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = uniqid();
        $response = $this->client->dictionarySetField($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, CollectionTtl::of(2)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionarySetField($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, CollectionTtl::of(10));
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        sleep(2);
        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($value, $response->asHit()->valueString());
    }

    public function testDictionaryDelete_HappyPath()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $response = $this->client->dictionarySetField($this->TEST_CACHE_NAME, $dictionaryName, $field, uniqid(), CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $response = $this->client->delete($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testDictionaryIncrement_NullFieldError()
    {
        $dictionaryName = uniqid();
        $field = "";
        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, 1, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionaryIncrement_HappyPath()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, 1, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(1, $response->asSuccess()->valueInt());

        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, 41, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(42, $response->asSuccess()->valueInt());

        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, -1042, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(-1000, $response->asSuccess()->valueInt());

        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals("-1000", $response->asHit()->valueString());
    }

    public function testDictionaryIncrement_RefreshTtl()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, 1, CollectionTtl::of(2)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, 1, CollectionTtl::of(10));
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(2);

        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals("2", $response->asHit()->valueString());
    }

    public function testDictionaryIncrement_NoRefreshTtl()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, 1, CollectionTtl::of(5)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, 1, CollectionTtl::of(10)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(6);

        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testDictionaryIncrement_SetAndReset()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $response = $this->client->dictionarySetField($this->TEST_CACHE_NAME, $dictionaryName, $field, "10", CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, 0, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(10, $response->asSuccess()->valueInt());

        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, 90, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(100, $response->asSuccess()->valueInt());

        $response = $this->client->dictionarySetField($this->TEST_CACHE_NAME, $dictionaryName, $field, "0", CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, 0, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(0, $response->asSuccess()->valueInt());
    }

    public function testDictionaryIncrement_FailedPrecondition()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $response = $this->client->dictionarySetField($this->TEST_CACHE_NAME, $dictionaryName, $field, "amcaface", CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, 1);
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

        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $field1);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");

        $response = $this->client->dictionarySetField($this->TEST_CACHE_NAME, $dictionaryName, $field1, $value1, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryRemoveField($this->TEST_CACHE_NAME, $dictionaryName, $field1);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $field1);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");

        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $field2);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");

        $response = $this->client->dictionaryRemoveField($this->TEST_CACHE_NAME, $dictionaryName, $field2);
        $this->assertNull($response->asError());

        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $field2);
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

        $response = $this->client->dictionarySetField($this->TEST_CACHE_NAME, $dictionaryName, $fields[0], uniqid(), CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $response = $this->client->dictionarySetField($this->TEST_CACHE_NAME, $dictionaryName, $fields[1], uniqid(), CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $response = $this->client->dictionarySetField($this->TEST_CACHE_NAME, $dictionaryName, $otherField, uniqid(), CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());

        $response = $this->client->dictionaryRemoveFields($this->TEST_CACHE_NAME, $dictionaryName, $fields);
        $this->assertNull($response->asError());

        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $otherField);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $fields[0]);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");

        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $fields[1]);
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
        $response = $this->client->dictionarySetField($this->TEST_CACHE_NAME, $dictionaryName, $field1, $value1, CollectionTtl::of(10));
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $response = $this->client->dictionarySetField($this->TEST_CACHE_NAME, $dictionaryName, $field2, $value2, CollectionTtl::of(10));
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryFetch($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($response->asHit()->valuesDictionary(), $contentDictionary);
    }

    public function testDictionaryFetchDictionaryDoesNotExist_Noop()
    {
        $dictionaryName = uniqid();
        $response = $this->client->dictionaryFetch($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");

        $response = $this->client->delete($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryFetch($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testDictionaryDeleteDictionaryExists_HappyPath()
    {
        $dictionaryName = uniqid();
        $response = $this->client->dictionarySetField($this->TEST_CACHE_NAME, $dictionaryName, uniqid(), uniqid(), CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionarySetField($this->TEST_CACHE_NAME, $dictionaryName, uniqid(), uniqid(), CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionarySetField($this->TEST_CACHE_NAME, $dictionaryName, uniqid(), uniqid(), CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryFetch($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $response = $this->client->delete($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryFetch($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testDictionarySetFieldsWithNullDictionaryName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $dictionaryName = null;
        $items = [uniqid()];
        $this->client->dictionarySetFields($this->TEST_CACHE_NAME, $dictionaryName, $items, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
    }

    public function testDictionarySetFieldsWithEmptyDictionaryName_IsError()
    {
        $dictionaryName = "";
        $items = [uniqid()];
        $response = $this->client->dictionarySetFields($this->TEST_CACHE_NAME, $dictionaryName, $items, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionarySetFieldsWithNullItems_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $dictionaryName = uniqid();
        $items = null;
        $this->client->dictionarySetFields($this->TEST_CACHE_NAME, $dictionaryName, $items, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
    }

    public function testDictionarySetFieldsWithEmptyItems_IsError()
    {
        $dictionaryName = uniqid();

        $items = [];
        $response = $this->client->dictionarySetFields($this->TEST_CACHE_NAME, $dictionaryName, $items, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());

        $items = [""=> "value", "key"=>"anothervalue"];
        $response = $this->client->dictionarySetFields($this->TEST_CACHE_NAME, $dictionaryName, $items, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionarySetFields_HappyPath()
    {
        $dictionaryName = uniqid();
        $field1 = uniqid();
        $field2 = uniqid();
        $value1 = uniqid();
        $value2 = uniqid();
        $items = [$field1 => $value1, $field2 => $value2];
        $response = $this->client->dictionarySetFields($this->TEST_CACHE_NAME, $dictionaryName, $items, CollectionTtl::of(10)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $field1);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($value1, $response->asHit()->valueString());

        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $field2);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($value2, $response->asHit()->valueString());
    }

    public function testDictionarySetFieldsRefreshTtl_HappyPath()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = uniqid();
        $content = [$field => $value];
        $response = $this->client->dictionarySetFields($this->TEST_CACHE_NAME, $dictionaryName, $content, CollectionTtl::of(2)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionarySetFields($this->TEST_CACHE_NAME, $dictionaryName, $content, CollectionTtl::of(10));
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(2);

        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($value, $response->asHit()->valueString());
    }

    public function testDictionarySetFieldsNoRefreshTtl_HappyPath()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = uniqid();
        $content = [$field => $value];
        $response = $this->client->dictionarySetFields($this->TEST_CACHE_NAME, $dictionaryName, $content, CollectionTtl::of(5)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(1);

        $response = $this->client->dictionarySetFields($this->TEST_CACHE_NAME, $dictionaryName, $content, CollectionTtl::of(10)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(4);

        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testDictionaryGetFieldsWithNullDictionaryName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $dictionaryName = null;
        $this->client->dictionaryGetFields($this->TEST_CACHE_NAME, $dictionaryName, [uniqid()]);
    }

    public function testDictionaryGetFieldsWithEmptyDictionaryName_IsError()
    {
        $dictionaryName = "";
        $response = $this->client->dictionaryGetFields($this->TEST_CACHE_NAME, $dictionaryName, [uniqid()]);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionaryGetFieldsWithNullFields_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $dictionaryName = uniqid();
        $fields = null;
        $this->client->dictionaryGetFields($this->TEST_CACHE_NAME, $dictionaryName, $fields);
    }

    public function testDictionaryGetFieldsWithEmptyFields_IsError()
    {
        $dictionaryName = uniqid();
        $fields = [""];
        $response = $this->client->dictionaryGetFields($this->TEST_CACHE_NAME, $dictionaryName, $fields);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());

        $fields = [];
        $response = $this->client->dictionaryGetFields($this->TEST_CACHE_NAME, $dictionaryName, $fields);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testDictionaryGetFields_HappyPath()
    {
        $dictionaryName = uniqid();
        $field1 = "1234"; // explicit integer-like string to make sure php casting doesn't break validation
        $field2 = uniqid();
        $field3 = uniqid();
        $value1 = uniqid();
        $value2 = uniqid();
        $items = [$field1 => $value1, $field2 => $value2];
        $response = $this->client->dictionarySetFields($this->TEST_CACHE_NAME, $dictionaryName, $items, CollectionTtl::of(600)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryGetFields($this->TEST_CACHE_NAME, $dictionaryName, [$field1, $field2, $field3]);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $counter = 0;
        foreach ($response->asHit()->responses() as $response) {
            if ($counter == 2) {
                $this->assertEquals(DictionaryGetFieldMiss::class, get_class($response));
            } else {
                $this->assertEquals(DictionaryGetFieldHit::class, get_class($response));
            }
            $counter++;
        }
    }

    public function testDictionaryGetBatchFieldsValuesArray_HappyPath()
    {
        $dictionaryName = uniqid();
        $field1 = "1234"; // explicit integer-like string to make sure php casting doesn't break validation
        $field2 = uniqid();
        $field3 = uniqid();
        $value1 = uniqid();
        $value2 = uniqid();
        $value3 = uniqid();
        $items = [$field1 => $value1, $field2 => $value2, $field3 => $value3];
        $response = $this->client->dictionarySetFields($this->TEST_CACHE_NAME, $dictionaryName, $items, CollectionTtl::of(10)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryGetFields($this->TEST_CACHE_NAME, $dictionaryName, [$field1, $field2, $field3]);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($items, $response->asHit()->valuesDictionary());
    }

    public function testDictionaryGetFieldsDictionaryMissing_HappyPath()
    {
        $dictionaryName = uniqid();
        $field1 = uniqid();
        $field2 = uniqid();
        $field3 = uniqid();
        $response = $this->client->dictionaryGetFields($this->TEST_CACHE_NAME, $dictionaryName, [$field1, $field2, $field3]);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a hit but got: $response");
    }

    public function testDictionaryGetBatchFieldsValuesArray_MixedPath()
    {
        $dictionaryName = uniqid();
        $field1 = "key1";
        $field2 = "key2";
        $field3 = "key3";
        $value1 = "val1";
        $value3 = "val3";
        $items = [$field1 => $value1, $field3 => $value3];
        $response = $this->client->dictionarySetFields($this->TEST_CACHE_NAME, $dictionaryName, $items, CollectionTtl::of(10)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->dictionaryGetFields($this->TEST_CACHE_NAME, $dictionaryName, [$field1, $field2, $field3]);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a success but got: $response");
        $this->assertEquals($items, $response->asHit()->valuesDictionary());
    }

    // __toString() tests

    public function testCacheSetToString_HappyPath()
    {
        $key = uniqid();
        $value = "a short value";
        $response = $this->client->set($this->TEST_CACHE_NAME, $key, $value);
        $this->assertNull($response->asError());
        $this->assertEquals("$response", get_class($response));
    }

    public function testCacheSetToString_LongValues()
    {
        $key = str_repeat("k", 256);
        $value = str_repeat("v", 256);
        $response = $this->client->set($this->TEST_CACHE_NAME, $key, $value);
        $this->assertNull($response->asError());
        $this->assertStringStartsWith(get_class($response), "$response");
    }

    public function testCacheGetToString_HappyPath()
    {
        $key = uniqid();
        $value = "a short value";
        $response = $this->client->set($this->TEST_CACHE_NAME, $key, $value);
        $this->assertNull($response->asError());

        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertEquals("$response", get_class($response) . ": $value");
    }

    public function testCacheGetToString_LongValue()
    {
        $key = uniqid();
        $value = str_repeat("a", 256);
        $response = $this->client->set($this->TEST_CACHE_NAME, $key, $value);
        $this->assertNull($response->asError());

        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertEquals($value, $response->asHit()->valueString());
        $this->assertStringEndsWith("...", "$response");
    }

    public function testCacheListPopFrontToString_HappyPath()
    {
        $listName = uniqid();
        $value = "a short value";
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());

        $response = $this->client->listPopFront($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertEquals("$response", get_class($response) . ": $value");
    }

    public function testCacheListPopFrontToString_LongValue()
    {
        $listName = uniqid();
        $value = str_repeat("a", 256);
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());

        $response = $this->client->listPopFront($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertEquals($value, $response->asHit()->valueString());
        $this->assertStringEndsWith("...", "$response");
    }

    public function testCacheListPopBackToString_HappyPath()
    {
        $listName = uniqid();
        $value = "a short value";
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());

        $response = $this->client->listPopBack($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertEquals("$response", get_class($response) . ": $value");
    }

    public function testCacheListPopBackToString_LongValue()
    {
        $listName = uniqid();
        $value = str_repeat("a", 256);
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());

        $response = $this->client->listPopBack($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertEquals($value, $response->asHit()->valueString());
        $this->assertStringEndsWith("...", "$response");
    }

    public function testCacheListFetchToString_HappyPath()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());

        $response = $this->client->listFetch($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertCount(3, $response->asHit()->valuesArray());
        $this->assertEquals("$response", get_class($response) . ": 3 items");
    }

    public function testCacheListPushFrontToString_HappyPath()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertEquals("$response", get_class($response) . ": 1 items");
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertEquals("$response", get_class($response) . ": 2 items");
        $response = $this->client->listPushFront($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::fromCacheTtl(2)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertEquals("$response", get_class($response) . ": 3 items");
    }

    public function testCacheListPushBackToString_HappyPath()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertEquals("$response", get_class($response) . ": 1 items");
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertEquals("$response", get_class($response) . ": 2 items");
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertEquals("$response", get_class($response) . ": 3 items");
    }

    public function testCacheListLengthToString_HappyPath()
    {
        $listName = uniqid();
        $value = uniqid();
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $response = $this->client->listPushBack($this->TEST_CACHE_NAME, $listName, $value, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());

        $response = $this->client->listLength($this->TEST_CACHE_NAME, $listName);
        $this->assertNull($response->asError());
        $this->assertEquals("$response", get_class($response) . ": 3");
    }

    public function testDictionaryGetFieldToString_HappyPath()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = uniqid();
        $response = $this->client->dictionarySetField($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());

        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals("$response", get_class($response) . ": $value");
    }

    public function testDictionaryGetFieldToString_LongValue()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = str_repeat("a", 256);
        $response = $this->client->dictionarySetField($this->TEST_CACHE_NAME, $dictionaryName, $field, $value, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());

        $response = $this->client->dictionaryGetField($this->TEST_CACHE_NAME, $dictionaryName, $field);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($value, $response->asHit()->valueString());
        $this->assertStringEndsWith("...", "$response");
    }

    public function testDictionaryFetchToString_HappyPath()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $value = uniqid();
        for ($i = 0; $i < 5; $i++) {
            $response = $this->client->dictionarySetField($this->TEST_CACHE_NAME, $dictionaryName, "$field-$i", $value, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
            $this->assertNull($response->asError());
        }
        $response = $this->client->dictionaryFetch($this->TEST_CACHE_NAME, $dictionaryName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals("$response", get_class($response) . ": 5 items");
    }

    public function testDictionaryIncrementToString_HappyPath()
    {
        $dictionaryName = uniqid();
        $field = uniqid();
        $response = $this->client->dictionarySetField($this->TEST_CACHE_NAME, $dictionaryName, $field, "1", CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());

        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, 1, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertEquals("$response", get_class($response) . ": 2");

        $response = $this->client->dictionaryIncrement($this->TEST_CACHE_NAME, $dictionaryName, $field, 10, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertEquals("$response", get_class($response) . ": 12");
    }

    public function testSetAddElementWithNullCacheName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $setName = uniqid();
        $element = uniqid();
        $this->client->setAddElement(null, $setName, $element, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
    }

    public function testSetAddElementWithEmptyCacheName_ThrowsException()
    {
        $setName = uniqid();
        $element = uniqid();
        $response = $this->client->setAddElement("", $setName, $element, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetAddElementWithNullSetName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $element = uniqid();
        $this->client->setAddElement($this->TEST_CACHE_NAME, null, $element, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
    }

    public function testSetAddElementWithEmptySetName_ThrowsException()
    {
        $element = uniqid();
        $response = $this->client->setAddElement($this->TEST_CACHE_NAME, "", $element, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetAddElementWithNullElement_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $setName = uniqid();
        $this->client->setAddElement($this->TEST_CACHE_NAME, $setName, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
    }

    public function testSetAddElementWithEmptyElement_ThrowsException()
    {
        $setName = uniqid();
        $response = $this->client->setAddElement($this->TEST_CACHE_NAME, $setName, "", CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetAddElementsWithNullCacheName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $setName = uniqid();
        $elements = [uniqid(), uniqid()];
        $this->client->setAddElements(null, $setName, $elements, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
    }

    public function testSetAddElementsWithEmptyCacheName_ThrowsException()
    {
        $setName = uniqid();
        $elements = [uniqid(), uniqid()];
        $response = $this->client->setAddElements("", $setName, $elements, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetAddElementsWithNullSetName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $elements = [uniqid(), uniqid()];
        $this->client->setAddElements($this->TEST_CACHE_NAME, null, $elements, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
    }

    public function testSetAddElementsWithEmptySetName_ThrowsException()
    {
        $elements = [uniqid(), uniqid()];
        $response = $this->client->setAddElements($this->TEST_CACHE_NAME, "", $elements, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetAddElementsWithNullElements_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $setName = uniqid();
        $this->client->setAddElements($this->TEST_CACHE_NAME, $setName, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
    }

    public function testSetAddElementsWithNoElements_ThrowsException()
    {
        $setName = uniqid();
        $elements = [uniqid(), uniqid()];
        $response = $this->client->setAddElements($this->TEST_CACHE_NAME, $setName, [], CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetAddElementsWithEmptyElement_ThrowsException()
    {
        $setName = uniqid();
        $elements = [uniqid(), uniqid()];
        $response = $this->client->setAddElements($this->TEST_CACHE_NAME, $setName, [''], CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetContainsElementsWithNullCacheName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $setName = uniqid();
        $elements = [uniqid(), uniqid()];
        $this->client->setContainsElements(null, $setName, $elements, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
    }

    public function testSetContainsElementsWithEmptyCacheName_ThrowsException()
    {
        $setName = uniqid();
        $elements = [uniqid(), uniqid()];
        $response = $this->client->setContainsElements("", $setName, $elements, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetContainsElementsWithNullSetName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $elements = [uniqid(), uniqid()];
        $this->client->setContainsElements($this->TEST_CACHE_NAME, null, $elements, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
    }

    public function testSetContainsElementsWithEmptySetName_ThrowsException()
    {
        $elements = [uniqid(), uniqid()];
        $response = $this->client->setContainsElements($this->TEST_CACHE_NAME, "", $elements, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetContainsElementsWithNullElements_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $setName = uniqid();
        $this->client->setContainsElements($this->TEST_CACHE_NAME, $setName, null, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
    }

    public function testSetContainsElementsWithNoElements_ThrowsException()
    {
        $setName = uniqid();
        $elements = [uniqid(), uniqid()];
        $response = $this->client->setContainsElements($this->TEST_CACHE_NAME, $setName, [], CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetContainsElementsWithEmptyElement_ThrowsException()
    {
        $setName = uniqid();
        $elements = [uniqid(), uniqid()];
        $response = $this->client->setContainsElements($this->TEST_CACHE_NAME, $setName, [''], CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
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


    public function testSetLength_HappyPath()
    {
        $setName = uniqid();
        foreach (range(0, 3) as $i) {
            $val = uniqid();
            $response = $this->client->setAddElement($this->TEST_CACHE_NAME, $setName, $val, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
            $this->assertNull($response->asError());
            $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

            $response = $this->client->setLength($this->TEST_CACHE_NAME, $setName);
            $this->assertNull($response->asError());
            $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
            $this->assertEquals($i + 1, $response->asSuccess()->length());
            $this->assertEquals("$response", get_class($response) . ": {$response->asSuccess()->length()}");
        }
    }

    public function testSetLength_MissingSet()
    {
        $response = $this->client->setLength($this->TEST_CACHE_NAME, uniqid());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(0, $response->asSuccess()->length());
    }


    public function testSetAddElementSetFetch_HappyPath()
    {
        $setName = uniqid();
        $element = uniqid();
        $response = $this->client->setAddElement($this->TEST_CACHE_NAME, $setName, $element, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->setFetch($this->TEST_CACHE_NAME, $setName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($element, $response->asHit()->valuesArray()[0]);
    }

    public function testSetAddElementSetFetch_NoRefreshTtl()
    {
        $setName = uniqid();
        $element = uniqid();
        $response = $this->client->setAddElement($this->TEST_CACHE_NAME, $setName, $element, CollectionTtl::of(5)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(1);

        $response = $this->client->setAddElement($this->TEST_CACHE_NAME, $setName, $element, CollectionTtl::of(10)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(4);

        $response = $this->client->setFetch($this->TEST_CACHE_NAME, $setName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testSetAddElementSetFetch_RefreshTtl()
    {
        $setName = uniqid();
        $element = uniqid();
        $response = $this->client->setAddElement($this->TEST_CACHE_NAME, $setName, $element, CollectionTtl::of(2)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->setAddElement($this->TEST_CACHE_NAME, $setName, $element, CollectionTtl::of(10));
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(2);

        $response = $this->client->setFetch($this->TEST_CACHE_NAME, $setName);
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($element, $response->asHit()->valuesArray()[0]);
    }

    public function testSetAddElementsSetFetch_HappyPath()
    {
        $setName = uniqid();
        $elements = [uniqid(), uniqid()];
        $response = $this->client->setAddElements($this->TEST_CACHE_NAME, $setName, $elements, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->setFetch($this->TEST_CACHE_NAME, $setName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        sort($elements);
        $fetchedElements = $response->asHit()->valuesArray();
        sort($fetchedElements);
        $this->assertEquals($elements, $fetchedElements);
    }

    public function testSetAddElementsSetFetch_NoRefreshTtl()
    {
        $setName = uniqid();
        $elements = [uniqid(), uniqid()];
        $response = $this->client->setAddElements($this->TEST_CACHE_NAME, $setName, $elements, CollectionTtl::of(5)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(1);

        $response = $this->client->setAddElements($this->TEST_CACHE_NAME, $setName, $elements, CollectionTtl::of(10)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(4);

        $response = $this->client->setFetch($this->TEST_CACHE_NAME, $setName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testSetAddElementsSetFetch_RefreshTtl()
    {
        $setName = uniqid();
        $elements = [uniqid(), uniqid()];
        $response = $this->client->setAddElements($this->TEST_CACHE_NAME, $setName, $elements, CollectionTtl::of(2)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->setAddElements($this->TEST_CACHE_NAME, $setName, $elements, CollectionTtl::of(10));
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(2);

        $response = $this->client->setFetch($this->TEST_CACHE_NAME, $setName);
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        sort($elements);
        $fetchedElements = $response->asHit()->valuesArray();
        sort($fetchedElements);
        $this->assertEquals($elements, $fetchedElements);
    }

    public function testSetContainsElements_ExactMatch()
    {
        $setName = uniqid();
        $elements = ["foo", "bar", "baz"];

        $response = $this->client->setAddElements($this->TEST_CACHE_NAME, $setName, ["foo", "bar", "baz"], CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->setContainsElements($this->TEST_CACHE_NAME, $setName, $elements);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $expectedExists = [true, true, true];
        $expectedExistsDict = [];
        $expectedExistsDict["foo"] = true;
        $expectedExistsDict["bar"] = true;
        $expectedExistsDict["baz"] = true;

        $this->assertEquals($response->asHit()->containsElements(), $expectedExists);
        $this->assertEquals($response->asHit()->containsElementsDictionary(), $expectedExistsDict);
    }

    public function testSetContainsElements_SubsetOfExistingSet()
    {
        $setName = uniqid();
        $elements = ["foo", "bar", "baz"];

        $response = $this->client->setAddElements($this->TEST_CACHE_NAME, $setName, ["foo", "bar", "baz", "bam", "beep", "boop"], CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->setContainsElements($this->TEST_CACHE_NAME, $setName, $elements);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $expectedExists = [true, true, true];
        $expectedExistsDict = [];
        $expectedExistsDict["foo"] = true;
        $expectedExistsDict["bar"] = true;
        $expectedExistsDict["baz"] = true;

        $this->assertEquals($response->asHit()->containsElements(), $expectedExists);
        $this->assertEquals($response->asHit()->containsElementsDictionary(), $expectedExistsDict);
    }


    public function testSetContainsElements_NotAllElementsInExistingSet()
    {
        $setName = uniqid();
        $elements = ["foo", "bar", "baz"];

        $response = $this->client->setAddElements($this->TEST_CACHE_NAME, $setName, ["bar", "bam", "beep", "boop"], CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->setContainsElements($this->TEST_CACHE_NAME, $setName, $elements);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $expectedExists = [false, true, false];
        $expectedExistsDict = [];
        $expectedExistsDict["foo"] = false;
        $expectedExistsDict["bar"] = true;
        $expectedExistsDict["baz"] = false;

        $this->assertEquals($response->asHit()->containsElements(), $expectedExists);
        $this->assertEquals($response->asHit()->containsElementsDictionary(), $expectedExistsDict);
    }


    public function testSetContainsElements_NoMatchingElementsInExistingSet()
    {
        $setName = uniqid();
        $elements = ["foo", "bar", "baz"];

        $response = $this->client->setAddElements($this->TEST_CACHE_NAME, $setName, ["bam", "beep", "boop"], CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->setContainsElements($this->TEST_CACHE_NAME, $setName, $elements);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $expectedExists = [false, false, false];
        $expectedExistsDict = [];
        $expectedExistsDict["foo"] = false;
        $expectedExistsDict["bar"] = false;
        $expectedExistsDict["baz"] = false;

        $this->assertEquals($response->asHit()->containsElements(), $expectedExists);
        $this->assertEquals($response->asHit()->containsElementsDictionary(), $expectedExistsDict);
    }

    public function testSetContainsElements_SetDoesNotExist()
    {
        $setName = uniqid();
        $elements = ["foo", "bar"];

        $response = $this->client->setContainsElements($this->TEST_CACHE_NAME, $setName, $elements);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testSetContainsElements_ExistingSetIsEmpty()
    {
        $setName = uniqid();
        $elements = ["foo", "bar"];

        $response = $this->client->setAddElement($this->TEST_CACHE_NAME, $setName, "baz", CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->setRemoveElement($this->TEST_CACHE_NAME, $setName, "baz");
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        // Empty sets manifest the same as a miss
        $response = $this->client->setContainsElements($this->TEST_CACHE_NAME, $setName, $elements);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }


    public function testSetRemoveElementWithNullCacheName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $setName = uniqid();
        $element = uniqid();
        $this->client->setRemoveElement(null, $setName, $element);
    }

    public function testSetRemoveElementWithEmptyCacheName_ThrowsException()
    {
        $setName = uniqid();
        $element = uniqid();
        $response = $this->client->setRemoveElement("", $setName, $element);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetRemoveElementWithNullSetName_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $element = uniqid();
        $this->client->setRemoveElement($this->TEST_CACHE_NAME, null, $element);
    }

    public function testSetRemoveElementWithEmptySetName_ThrowsException()
    {
        $element = uniqid();
        $response = $this->client->setRemoveElement($this->TEST_CACHE_NAME, "", $element);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetRemoveElementWithNullElement_ThrowsException()
    {
        $this->expectException(TypeError::class);
        $setName = uniqid();
        $this->client->setRemoveElement($this->TEST_CACHE_NAME, $setName, null);
    }

    public function testSetRemoveElementWithEmptyElement_ThrowsException()
    {
        $setName = uniqid();
        $response = $this->client->setRemoveElement($this->TEST_CACHE_NAME, $setName, "");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSetRemoveElement_HappyPath()
    {
        $setName = uniqid();
        $element = uniqid();
        $response = $this->client->setAddElement($this->TEST_CACHE_NAME, $setName, $element, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->setRemoveElement($this->TEST_CACHE_NAME, $setName, uniqid());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->setFetch($this->TEST_CACHE_NAME, $setName);
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($element, $response->asHit()->valuesArray()[0]);

        $response = $this->client->setRemoveElement($this->TEST_CACHE_NAME, $setName, $element);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->setFetch($this->TEST_CACHE_NAME, $setName);
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testSetRemoveElement_SetIsMissingElement_Noop()
    {
        $setName = uniqid();

        $response = $this->client->setFetch($this->TEST_CACHE_NAME, $setName);
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");

        $response = $this->client->setRemoveElement($this->TEST_CACHE_NAME, $setName, uniqid());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->setFetch($this->TEST_CACHE_NAME, $setName);
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testSetDelete_SetExists_HappyPath()
    {
        $setName = uniqid();
        $response = $this->client->setAddElement($this->TEST_CACHE_NAME, $setName, uniqid(), CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got $response.");
        $response = $this->client->setAddElement($this->TEST_CACHE_NAME, $setName, uniqid(), CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got $response.");
        $response = $this->client->setAddElement($this->TEST_CACHE_NAME, $setName, uniqid(), CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got $response.");

        $response = $this->client->setFetch($this->TEST_CACHE_NAME, $setName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got $response.");

        $response = $this->client->delete($this->TEST_CACHE_NAME, $setName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got $response.");

        $response = $this->client->setFetch($this->TEST_CACHE_NAME, $setName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got $response.");
    }

    // placeholder: sortedSetLengthByScore

    public function testSortedSetPutElement_HappyPath()
    {
        $sortedSetName = uniqid();
        $value = uniqid();
        $score = 1.0;
        $response = $this->client->sortedSetPutElement($this->TEST_CACHE_NAME, $sortedSetName, $value, $score);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->sortedSetFetchByRank($this->TEST_CACHE_NAME, $sortedSetName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $valuesArray = $response->asHit()->valuesArray();
        $this->assertArrayHasKey($value, $valuesArray, "Expected value '$value' not found in the array");
        $this->assertEquals($score, $valuesArray[$value], "The score for value '$value' does not match the expected score.");
    }

    public function testSortedSetPutElementWithNonexistentCache_ThrowsException()
    {
        $cacheName = uniqid();
        $sortedSetName = uniqid();
        $value = uniqid();
        $score = 1.0;
        $response = $this->client->sortedSetPutElement($cacheName, $sortedSetName, $value, $score);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::CACHE_NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetPutElementWithNullCacheName_ThrowsException()
    {
        $sortedSetName = uniqid();
        $value = uniqid();
        $score = 1.0;
        $response = $this->client->sortedSetPutElement((string)null, $sortedSetName, $value, $score);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetPutElementWithEmptyCacheName_ThrowsException()
    {
        $sortedSetName = uniqid();
        $value = uniqid();
        $score = 1.0;
        $response = $this->client->sortedSetPutElement("", $sortedSetName, $value, $score);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetPutElementWithEmptyValue_ThrowsException()
    {
        $sortedSetName = uniqid();
        $score = 1.0;
        $response = $this->client->sortedSetPutElement($this->TEST_CACHE_NAME, $sortedSetName, (string)null, $score);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetPutElements_HappyPath()
    {
        $sortedSetName = uniqid();
        $elements = [
            "bar" => 2.0,
            "baz" => 3.0,
        ];

        $response = $this->client->sortedSetPutElements($this->TEST_CACHE_NAME, $sortedSetName, $elements);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->sortedSetFetchByRank($this->TEST_CACHE_NAME, $sortedSetName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = $elements;

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // add more elements to the set
        $newElements = [
            "foo" => 1.0,
            "qux" => 4.0,
        ];

        $response = $this->client->sortedSetPutElements($this->TEST_CACHE_NAME, $sortedSetName, $newElements);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->sortedSetFetchByRank($this->TEST_CACHE_NAME, $sortedSetName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = [
            "foo" => 1.0,
            "bar" => 2.0,
            "baz" => 3.0,
            "qux" => 4.0,
        ];;

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");
    }

    public function testSortedSetPutElementsWithNonexistentCache_ThrowsException()
    {
        $cacheName = uniqid();
        $sortedSetName = uniqid();
        $elements = [
            "foo" => 1.0,
        ];
        $response = $this->client->sortedSetPutElements($cacheName, $sortedSetName, $elements);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::CACHE_NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetPutElementsWithNullCacheName_ThrowsException()
    {
        $sortedSetName = uniqid();
        $elements = [
            "foo" => 1.0,
        ];
        $response = $this->client->sortedSetPutElements((string)null, $sortedSetName, $elements);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetPutElementsWithEmptyCacheName_ThrowsException()
    {
        $sortedSetName = uniqid();
        $elements = [
            "foo" => 1.0,
        ];
        $response = $this->client->sortedSetPutElements("", $sortedSetName, $elements);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetPutElementsWithNullValue_ThrowsException()
    {
        $sortedSetName = uniqid();
        $elements = [
            (string)null => 1.0,
        ];
        $response = $this->client->sortedSetPutElements("", $sortedSetName, $elements);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetIncrementScore_HappyPath()
    {
        $sortedSetName = uniqid();
        $value = uniqid();

        // Increment the score of an element that does not yet exist
        $response = $this->client->sortedSetIncrementScore($this->TEST_CACHE_NAME, $sortedSetName, $value, 1.5);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(1.5, $response->asSuccess()->score());

        // Increment the score again
        $response = $this->client->sortedSetIncrementScore($this->TEST_CACHE_NAME, $sortedSetName, $value, 3.0);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(4.5, $response->asSuccess()->score());

        // Decrement the score
        $response = $this->client->sortedSetIncrementScore($this->TEST_CACHE_NAME, $sortedSetName, $value, -4.0);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(0.5, $response->asSuccess()->score());

        // Fetch the element to ensure the score is correctly updated
        $response = $this->client->sortedSetFetchByRank($this->TEST_CACHE_NAME, $sortedSetName);
        $this->assertNull($response->asError());
        $hit = $response->asHit();
        $this->assertNotNull($hit, "Expected a hit but got: $response");
        $this->assertCount(1, $hit->valuesArray());
        $this->assertArrayHasKey($value, $hit->valuesArray());
        $this->assertEquals(0.5, $hit->valuesArray()[$value]);
    }

    public function testSortedSetIncrementScore_WithTtl_RefreshTtl()
    {
        $sortedSetName = uniqid();
        $value = uniqid();

        // Increment the score with a specific TTL
        $response = $this->client->sortedSetIncrementScore($this->TEST_CACHE_NAME, $sortedSetName, $value, 2.5, CollectionTtl::of(5));
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        // Re-increment score and update TTL to extend it
        $response = $this->client->sortedSetIncrementScore($this->TEST_CACHE_NAME, $sortedSetName, $value, 2.5, CollectionTtl::of(10));
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(6);

        // Fetch the element to ensure the TTL was refreshed and value remains
        $response = $this->client->sortedSetFetchByRank($this->TEST_CACHE_NAME, $sortedSetName);
        $this->assertNull($response->asError());
        $hit = $response->asHit();
        $this->assertNotNull($hit, "Expected a hit but got: $response");
        $this->assertEquals(5.0, $hit->valuesArray()[$value]);
    }

    public function testSortedSetIncrementScore_NoRefreshTtl()
    {
        $sortedSetName = uniqid();
        $value = uniqid();

        // Increment the score with a specific TTL but no refresh on updates
        $response = $this->client->sortedSetIncrementScore($this->TEST_CACHE_NAME, $sortedSetName, $value, 2.0, CollectionTtl::of(5));
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        // Increment again, but TTL should not refresh
        $response = $this->client->sortedSetIncrementScore($this->TEST_CACHE_NAME, $sortedSetName, $value, 1.0, CollectionTtl::of(10)->withNoRefreshTtlOnUpdates());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        sleep(6);

        // After the TTL expires, the element should no longer exist
        $response = $this->client->sortedSetFetchByRank($this->TEST_CACHE_NAME, $sortedSetName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

  public function testSortedSetIncrementScore_InvalidArguments()
    {
        $sortedSetName = uniqid();

        // Empty value should trigger an error
        $response = $this->client->sortedSetIncrementScore($this->TEST_CACHE_NAME, $sortedSetName, "", 1.0);
        $this->assertNotNull($response->asError(), "Expected an error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());

        // Empty cache name should trigger an error
        $response = $this->client->sortedSetIncrementScore("", $sortedSetName, uniqid(), 1.0);
        $this->assertNotNull($response->asError(), "Expected an error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetIncrementScore_NonexistentCache_ThrowsException()
    {
        $cacheName = uniqid();
        $sortedSetName = uniqid();
        $value = uniqid();

        // Increment score in a non-existent cache
        $response = $this->client->sortedSetIncrementScore($cacheName, $sortedSetName, $value, 1.0);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::CACHE_NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetFetchByRank_HappyPath()
    {
        $sortedSetName = uniqid();

        $elements = [
            "foo" => 1.0,
            "bar" => 2.0,
            "baz" => 3.0,
            "qux" => 4.0,
        ];

        // the fetch returns a miss when the set does not yet exist
        $response = $this->client->sortedSetFetchByRank($this->TEST_CACHE_NAME, $sortedSetName);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");

        $response = $this->client->sortedSetPutElements($this->TEST_CACHE_NAME, $sortedSetName, $elements);
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        // full array ascending
        $response = $this->client->sortedSetFetchByRank($this->TEST_CACHE_NAME, $sortedSetName);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = $elements;

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // full array descending
        $response = $this->client->sortedSetFetchByRank($this->TEST_CACHE_NAME, $sortedSetName, null, null, SORT_DESC);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = array_reverse($elements);

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // limit by start rank
        $response = $this->client->sortedSetFetchByRank($this->TEST_CACHE_NAME, $sortedSetName, 1);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $this->assertCount(3, $fetchedElements, "Expected 3 elements, but got " . count($fetchedElements));
        $expectedElements = [
            "bar" => 2.0,
            "baz" => 3.0,
            "qux" => 4.0,
        ];

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // limit by end rank
        $response = $this->client->sortedSetFetchByRank($this->TEST_CACHE_NAME, $sortedSetName, null, 3);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $this->assertCount(3, $fetchedElements, "Expected 3 elements, but got " . count($fetchedElements));
        $expectedElements = [
            "foo" => 1.0,
            "bar" => 2.0,
            "baz" => 3.0,
        ];

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // limit by start and end rank
        $response = $this->client->sortedSetFetchByRank($this->TEST_CACHE_NAME, $sortedSetName, 1, 3);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $this->assertCount(2, $fetchedElements, "Expected 2 elements, but got " . count($fetchedElements));
        $expectedElements = [
            "bar" => 2.0,
            "baz" => 3.0,
        ];

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // descending with start and end rank
        $response = $this->client->sortedSetFetchByRank($this->TEST_CACHE_NAME, $sortedSetName, 1, 3, SORT_DESC);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $this->assertCount(2, $fetchedElements, "Expected 2 elements, but got " . count($fetchedElements));
        $expectedElements = [
            "baz" => 3.0,
            "bar" => 2.0,
        ];

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");
    }

    public function testSortedSetFetchByRankWithNonexistantCache_ThrowsException()
    {
        $cacheName = uniqid();
        $sortedSetName = uniqid();
        $response = $this->client->sortedSetFetchByRank($cacheName, $sortedSetName);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::CACHE_NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetFetchByRankWithNullCacheName_ThrowsException()
    {
        $sortedSetName = uniqid();
        $response = $this->client->sortedSetFetchByRank((string)null, $sortedSetName);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetFetchByRankWithEmptyCacheName_ThrowsException()
    {
        $sortedSetName = uniqid();
        $response = $this->client->sortedSetFetchByRank("", $sortedSetName);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetFetchByRankWithStartRankGreaterThanEndRank_ThrowsException()
    {
        $sortedSetName = uniqid();
        $startRank = 100;
        $endRank = 1;
        $response = $this->client->sortedSetFetchByRank($this->TEST_CACHE_NAME, $sortedSetName, $startRank, $endRank);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetFetchByRankWithNegativeStartRankLessThanEndRank_ThrowsException()
    {
        $sortedSetName = uniqid();
        $startRank = -1;
        $endRank = -100;
        $response = $this->client->sortedSetFetchByRank($this->TEST_CACHE_NAME, $sortedSetName, $startRank, $endRank);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetFetchByScore_HappyPath()
    {
        $sortedSetName = uniqid();

        $elements = [
            "foo" => 1.0,
            "bar" => 2.0,
            "baz" => 3.0,
            "qux" => 4.0,
        ];

        // the fetch returns a miss when the set does not yet exist
        $response = $this->client->sortedSetFetchByScore($this->TEST_CACHE_NAME, $sortedSetName);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");

        $response = $this->client->sortedSetPutElements($this->TEST_CACHE_NAME, $sortedSetName, $elements);
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        // full array ascending
        $response = $this->client->sortedSetFetchByScore($this->TEST_CACHE_NAME, $sortedSetName);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = $elements;

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // full array descending
        $response = $this->client->sortedSetFetchByScore($this->TEST_CACHE_NAME, $sortedSetName, null, null, SORT_DESC);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = array_reverse($elements);

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // limit by min score
        $response = $this->client->sortedSetFetchByScore($this->TEST_CACHE_NAME, $sortedSetName, 1.1);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = [
            "bar" => 2.0,
            "baz" => 3.0,
            "qux" => 4.0,
        ];

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // limit by max score
        $response = $this->client->sortedSetFetchByScore($this->TEST_CACHE_NAME, $sortedSetName, null, 3.0);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = [
            "foo" => 1.0,
            "bar" => 2.0,
            "baz" => 3.0,
        ];

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // limit by min and max score
        $response = $this->client->sortedSetFetchByScore($this->TEST_CACHE_NAME, $sortedSetName, 1.1, 3.1);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = [
            "bar" => 2.0,
            "baz" => 3.0,
        ];

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // descending with min and max score
        $response = $this->client->sortedSetFetchByScore($this->TEST_CACHE_NAME, $sortedSetName, 1.1, 3.0, SORT_DESC);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = [
            "baz" => 3.0,
            "bar" => 2.0,
        ];

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // skip elements with offset
        $response = $this->client->sortedSetFetchByScore($this->TEST_CACHE_NAME, $sortedSetName, null, null, null, 2);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = [
            "baz" => 3.0,
            "qux" => 4.0,
        ];

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // limit by min score and skip by offset
        $response = $this->client->sortedSetFetchByScore($this->TEST_CACHE_NAME, $sortedSetName, 1.1, null, null, 2);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = [
            "qux" => 4.0,
        ];

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // limit by count
        $response = $this->client->sortedSetFetchByScore($this->TEST_CACHE_NAME, $sortedSetName, null, null, null, null, 2);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = [
            "foo" => 1.0,
            "bar" => 2.0,
        ];

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // limit by count to 0
        $response = $this->client->sortedSetFetchByScore($this->TEST_CACHE_NAME, $sortedSetName, null, null, null, null, 0);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = [];

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");
    }

    public function testSortedSetFetchByScoreWithNonexistantCache_ThrowsException()
    {
        $cacheName = uniqid();
        $sortedSetName = uniqid();
        $response = $this->client->sortedSetFetchByScore($cacheName, $sortedSetName);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::CACHE_NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetFetchByScoreWithNullCacheName_ThrowsException()
    {
        $sortedSetName = uniqid();
        $response = $this->client->sortedSetFetchByScore((string)null, $sortedSetName);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetFetchByScoreWithEmptyCacheName_ThrowsException()
    {
        $sortedSetName = uniqid();
        $response = $this->client->sortedSetFetchByScore("", $sortedSetName);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetFetchByScoreWithMinScoreLargerThanMaxScore_ThrowsException()
    {
        $sortedSetName = uniqid();
        $minScore = 100.0;
        $maxScore = 1.0;
        $response = $this->client->sortedSetFetchByScore($this->TEST_CACHE_NAME, $sortedSetName, $minScore, $maxScore);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetRemoveElement_HappyPath()
    {
        // 1. Remove an element from a sorted set that does not exist
        $sortedSetName = uniqid();
        $value = uniqid();
        $response = $this->client->sortedSetRemoveElement($this->TEST_CACHE_NAME, $sortedSetName, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->sortedSetFetchByRank($this->TEST_CACHE_NAME, $sortedSetName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");

        // 2. Remove an element from sorted set
        // Set up: Add an element to the sorted set
        $sortedSetName = uniqid();
        $value = uniqid();
        $score = 1.0;
        $response = $this->client->sortedSetPutElement($this->TEST_CACHE_NAME, $sortedSetName, $value, $score);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        // 2.1 Remove a different element from the sorted set
        $response = $this->client->sortedSetRemoveElement($this->TEST_CACHE_NAME, $sortedSetName, uniqid());
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        // Ensure the original element is still in the sorted set
        $response = $this->client->sortedSetFetchByRank($this->TEST_CACHE_NAME, $sortedSetName);
        $this->assertNull($response->asError());
        $hit = $response->asHit();
        $this->assertNotNull($hit, "Expected a hit but got: $response");
        $this->assertCount(1, $hit->valuesArray());
        $this->assertArrayHasKey($value, $hit->valuesArray());
        $this->assertEquals($score, $hit->valuesArray()[$value]);

        // 2.2 Remove the original element from the sorted set
        $response = $this->client->sortedSetRemoveElement($this->TEST_CACHE_NAME, $sortedSetName, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        // Ensure the sorted set is now empty
        $response = $this->client->sortedSetFetchByRank($this->TEST_CACHE_NAME, $sortedSetName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testSortedSetRemoveElementWithNonexistentCache_ThrowsException()
    {
        $cacheName = uniqid();
        $sortedSetName = uniqid();
        $value = uniqid();

        $response = $this->client->sortedSetRemoveElement($cacheName, $sortedSetName, $value);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::CACHE_NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetRemoveElementWithNullCacheName_ThrowsException()
    {
        $sortedSetName = uniqid();
        $value = uniqid();

        $response = $this->client->sortedSetRemoveElement((string)null, $sortedSetName, $value);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetRemoveElementWithEmptyCacheName_ThrowsException()
    {
        $sortedSetName = uniqid();
        $value = uniqid();

        $response = $this->client->sortedSetRemoveElement("", $sortedSetName, $value);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetRemoveElementWithEmptyValue_ThrowsException()
    {
        $sortedSetName = uniqid();

        $response = $this->client->sortedSetRemoveElement($this->TEST_CACHE_NAME, $sortedSetName, (string)null);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetRemoveElements_HappyPath()
    {
        // 1. Remove elements from a sorted set that does not exist
        $sortedSetName = uniqid();
        $values = [uniqid(), uniqid()];
        $response = $this->client->sortedSetRemoveElements($this->TEST_CACHE_NAME, $sortedSetName, $values);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->sortedSetFetchByRank($this->TEST_CACHE_NAME, $sortedSetName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");

        // 2. Remove multiple elements from sorted set
        // Set up: Add elements to the sorted set
        $sortedSetName = uniqid();
        $value1 = uniqid();
        $value2 = uniqid();
        $score1 = 1.0;
        $score2 = 2.0;
        $response = $this->client->sortedSetPutElement($this->TEST_CACHE_NAME, $sortedSetName, $value1, $score1);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->sortedSetPutElement($this->TEST_CACHE_NAME, $sortedSetName, $value2, $score2);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        // 2.1 Remove a different set of elements from the sorted set (only value1 should remain)
        $response = $this->client->sortedSetRemoveElements($this->TEST_CACHE_NAME, $sortedSetName, [uniqid()]);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        // Ensure the original elements are still in the sorted set
        $response = $this->client->sortedSetFetchByRank($this->TEST_CACHE_NAME, $sortedSetName);
        $this->assertNull($response->asError());
        $hit = $response->asHit();
        $this->assertNotNull($hit, "Expected a hit but got: $response");
        $this->assertCount(2, $hit->valuesArray());
        $this->assertArrayHasKey($value1, $hit->valuesArray());
        $this->assertEquals($score1, $hit->valuesArray()[$value1]);
        $this->assertArrayHasKey($value2, $hit->valuesArray());
        $this->assertEquals($score2, $hit->valuesArray()[$value2]);

        // 2.2 Remove both elements from the sorted set
        $response = $this->client->sortedSetRemoveElements($this->TEST_CACHE_NAME, $sortedSetName, [$value1, $value2]);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        // Ensure the sorted set is now empty
        $response = $this->client->sortedSetFetchByRank($this->TEST_CACHE_NAME, $sortedSetName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }

    public function testSortedSetRemoveElementsWithNonexistentCache_ThrowsException()
    {
        $cacheName = uniqid();
        $sortedSetName = uniqid();
        $values = [uniqid(), uniqid()];

        $response = $this->client->sortedSetRemoveElements($cacheName, $sortedSetName, $values);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::CACHE_NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetRemoveElementsWithNullCacheName_ThrowsException()
    {
        $sortedSetName = uniqid();
        $values = [uniqid(), uniqid()];

        $response = $this->client->sortedSetRemoveElements((string)null, $sortedSetName, $values);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetRemoveElementsWithEmptyCacheName_ThrowsException()
    {
        $sortedSetName = uniqid();
        $values = [uniqid(), uniqid()];

        $response = $this->client->sortedSetRemoveElements("", $sortedSetName, $values);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetRemoveElementsWithEmptyValuesArray_ThrowsException()
    {
        $sortedSetName = uniqid();
        $values = [];

        $response = $this->client->sortedSetRemoveElements($this->TEST_CACHE_NAME, $sortedSetName, $values);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetRemoveElementsWithNullValuesArray_ThrowsException()
    {
        $sortedSetName = uniqid();

        $response = $this->client->sortedSetRemoveElements($this->TEST_CACHE_NAME, $sortedSetName, [null]);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetGetScore_HappyPath()
    {
        $sortedSetName = uniqid();
        $value = uniqid();
        $score = 1.0;

        // Add the element to the sorted set
        $response = $this->client->sortedSetPutElement($this->TEST_CACHE_NAME, $sortedSetName, $value, $score);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        // Fetch the score for the element
        $response = $this->client->sortedSetGetScore($this->TEST_CACHE_NAME, $sortedSetName, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $hit = $response->asHit();
        $this->assertEquals($value, $hit->valueString(), "The value does not match the expected value.");
        $this->assertEquals($score, $hit->score(), "The score does not match the expected value.");
    }

    public function testSortedSetGetScore_Miss()
    {
        // 1. First test on a non-existent sorted set
        $sortedSetName = uniqid();
        $value = uniqid();

        // Ensure the sorted set does not exist
        $response = $this->client->sortedSetGetScore($this->TEST_CACHE_NAME, $sortedSetName, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");

        // 2. Second test a sorted set that exists but lacks the element
        $sortedSetName = uniqid();
        $value = uniqid();
        $score = 1.0;

        // Add the element to the sorted set
        $response = $this->client->sortedSetPutElement($this->TEST_CACHE_NAME, $sortedSetName, $value, $score);
        $this->assertNull($response->asError());

        // Ensure the element does not exist in the sorted set
        $queriedValue = uniqid();
        $response = $this->client->sortedSetGetScore($this->TEST_CACHE_NAME, $sortedSetName, $queriedValue);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");

        $miss = $response->asMiss();
        $this->assertEquals($queriedValue, $miss->valueString(), "The value does not match the expected value.");
    }

    public function testSortedSetGetScore_Error()
    {
        $sortedSetName = uniqid();
        $value = uniqid();

        // Using an invalid cache name to trigger an error
        $response = $this->client->sortedSetGetScore("", $sortedSetName, $value);
        $this->assertNotNull($response->asError(), "Expected an error but got: $response");

        $error = $response->asError();
        $this->assertEquals($value, $error->valueString());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $error->errorCode());
    }

    public function testSortedSetGetScore_NonexistentCache()
    {
        $sortedSetName = uniqid();
        $value = uniqid();
        $cacheName = uniqid(); // Non-existent cache

        // Fetch the score for the element in a non-existent cache
        $response = $this->client->sortedSetGetScore($cacheName, $sortedSetName, $value);
        $this->assertNotNull($response->asError(), "Expected an error but got: $response");
        $this->assertEquals(MomentoErrorCode::CACHE_NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetLengthByScore_HappyPath()
    {
        $sortedSetName = uniqid();

        $elements = [
            "foo" => 1.0,
            "bar" => 2.0,
            "baz" => 3.0,
            "qux" => 4.0,
        ];

        // the length is 0 when the set does not yet exist
        $response = $this->client->sortedSetLengthByScore($this->TEST_CACHE_NAME, $sortedSetName);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");

        $response = $this->client->sortedSetPutElements($this->TEST_CACHE_NAME, $sortedSetName, $elements);
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        // full set
        $response = $this->client->sortedSetLengthByScore($this->TEST_CACHE_NAME, $sortedSetName);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a success but got: $response");

        $fetchedLength = $response->asHit()->length();
        $expectedLength = 4;
        $this->assertEquals($expectedLength, $fetchedLength, "expected length of non-existent sorted set to be $expectedLength, not $fetchedLength");

        // limit by min score
        $response = $this->client->sortedSetLengthByScore($this->TEST_CACHE_NAME, $sortedSetName, 1.1);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a success but got: $response");

        $fetchedLength = $response->asHit()->length();
        $expectedLength = 3;
        $this->assertEquals($expectedLength, $fetchedLength, "expected length of non-existent sorted set to be $expectedLength, not $fetchedLength");

        // limit by max score
        $response = $this->client->sortedSetLengthByScore($this->TEST_CACHE_NAME, $sortedSetName, null, 3.9);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a success but got: $response");

        $fetchedLength = $response->asHit()->length();
        $expectedLength = 3;
        $this->assertEquals($expectedLength, $fetchedLength, "expected length of non-existent sorted set to be $expectedLength, not $fetchedLength");

        // limit by min and max score
        $response = $this->client->sortedSetLengthByScore($this->TEST_CACHE_NAME, $sortedSetName, 1.1, 3.9);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a success but got: $response");

        $fetchedLength = $response->asHit()->length();
        $expectedLength = 2;
        $this->assertEquals($expectedLength, $fetchedLength, "expected length of non-existent sorted set to be $expectedLength, not $fetchedLength");

        // no elements in score range
        $response = $this->client->sortedSetLengthByScore($this->TEST_CACHE_NAME, $sortedSetName, 100.0);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a success but got: $response");

        $fetchedLength = $response->asHit()->length();
        $expectedLength = 0;
        $this->assertEquals($expectedLength, $fetchedLength, "expected length of non-existent sorted set to be $expectedLength, not $fetchedLength");
    }

    public function testSortedSetLengthByScoreWithNonexistantCache_ThrowsException()
    {
        $cacheName = uniqid();
        $sortedSetName = uniqid();
        $response = $this->client->sortedSetLengthByScore($cacheName, $sortedSetName);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::CACHE_NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetLengthByScoreWithNullCacheName_ThrowsException()
    {
        $sortedSetName = uniqid();
        $response = $this->client->sortedSetLengthByScore((string)null, $sortedSetName);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetLengthByScoreWithEmptyCacheName_ThrowsException()
    {
        $sortedSetName = uniqid();
        $response = $this->client->sortedSetLengthByScore("", $sortedSetName);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testSortedSetLengthByScoreWithMinScoreLargerThanMaxScore_ThrowsException()
    {
        $sortedSetName = uniqid();
        $minScore = 100.0;
        $maxScore = 1.0;
        $response = $this->client->sortedSetLengthByScore($this->TEST_CACHE_NAME, $sortedSetName, $minScore, $maxScore);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testGetBatch_HappyPath()
    {
        $cacheName = $this->TEST_CACHE_NAME;
        $key1 = uniqid();
        $key2 = uniqid();
        $key3 = uniqid();
        $keys = [$key1, $key2, $key3];

        $value1 = uniqid();
        $value2 = uniqid();
        $value3 = uniqid();
        $expectedValues = [$value1, $value2, $value3];

        $response = $this->client->set($cacheName, $key1, $value1);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals("$response", get_class($response));

        $response = $this->client->set($cacheName, $key2, $value2);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals("$response", get_class($response));

        $response = $this->client->set($cacheName, $key3, $value3);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals("$response", get_class($response));


        $response = $this->client->getBatch($cacheName, $keys);

        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $responses = $response->asSuccess()->values();
        $this->assertCount(sizeof($keys), $responses);
        $this->assertEquals($responses, $expectedValues);
    }

    public function testSetBatch_HappyPath()
    {
        $cacheName = $this->TEST_CACHE_NAME;
        $key1 = uniqid();
        $key2 = uniqid();
        $key3 = uniqid();
        $keys = [$key1, $key2, $key3];

        $value1 = uniqid();
        $value2 = uniqid();
        $value3 = uniqid();
        $expectedValues = [$value1, $value2, $value3];

        $items = array(
            $key1 => $value1,
            $key2 => $value2,
            $key3 => $value3
        );

        $setBatchResponse = $this->client->setBatch($cacheName, $items, 60);
        $this->assertNull($setBatchResponse->asError());
        $this->assertNotNull($setBatchResponse->asSuccess(), "Expected a success but got: $setBatchResponse");
        $setResponses = $setBatchResponse->asSuccess()->results();
        $this->assertCount(sizeof($keys), $setResponses);

        $getBatchResponse = $this->client->getBatch($cacheName, $keys);
        $success = $getBatchResponse->asSuccess();
        $this->assertNull($getBatchResponse->asError());
        $this->assertNotNull($success, "Expected a success but got: $getBatchResponse");

        $responses = $success->values();
        $this->assertCount(sizeof($keys), $responses);
        $this->assertEquals($responses, $expectedValues);
    }

    public function testGetBatchSetBatch()
    {
        $cacheName = $this->TEST_CACHE_NAME;
        $key1 = uniqid();
        $key2 = uniqid();
        $key3 = uniqid();
        $keys = [$key1, $key2, $key3, "key4"];

        $value1 = uniqid();
        $value2 = uniqid();
        $value3 = uniqid();
        $expectedValues = [$value1, $value2, $value3, null];

        $items = array(
            $key1 => $value1,
            $key2 => $value2,
            $key3 => $value3
        );

        $setBatchResponse = $this->client->setBatch($cacheName, $items, 60);
        $this->assertNull($setBatchResponse->asError());
        $this->assertNotNull($setBatchResponse->asSuccess(), "Expected a success but got: $setBatchResponse");

        $getBatchResponse = $this->client->getBatch($cacheName, $keys);
        $this->assertNull($getBatchResponse->asError());
        $this->assertNotNull($getBatchResponse->asSuccess(), "Expected a success but got: $getBatchResponse");

        $successResponse = $getBatchResponse->asSuccess();

        $results = $successResponse->results();
        $expectedClasses = [GetHit::class, GetHit::class, GetHit::class, GetMiss::class];

        foreach ($expectedClasses as $index => $expectedClass) {
            $this->assertInstanceOf($expectedClass, $results[$index]);
        }

        $responses = $successResponse->values();
        $this->assertEquals($responses, $expectedValues);
    }

    public function testGetBatchSetBatchAsync()
    {
        $cacheName = $this->TEST_CACHE_NAME;
        $key1 = uniqid();
        $key2 = uniqid();
        $key3 = uniqid();
        $keys = [$key1, $key2, $key3, "key4"];

        $value1 = uniqid();
        $value2 = uniqid();
        $value3 = uniqid();
        $expectedValues = [$value1, $value2, $value3, null];

        $items = array(
            $key1 => $value1,
            $key2 => $value2,
            $key3 => $value3
        );

        $setBatchResponseFuture = $this->client->setBatchAsync($cacheName, $items, 60);
        $setBatchResponse = $setBatchResponseFuture->wait();
        $this->assertNull($setBatchResponse->asError());
        $this->assertNotNull($setBatchResponse->asSuccess(), "Expected a success but got: $setBatchResponse");

        $getBatchResponse = $this->client->getBatchAsync($cacheName, $keys);
        $getBatchResponse = $getBatchResponse->wait();
        $this->assertNull($getBatchResponse->asError());
        $this->assertNotNull($getBatchResponse->asSuccess(), "Expected a success but got: $getBatchResponse");

        $successResponse = $getBatchResponse->asSuccess();

        $results = $successResponse->results();
        $expectedClasses = [GetHit::class, GetHit::class, GetHit::class, GetMiss::class];

        foreach ($expectedClasses as $index => $expectedClass) {
            $this->assertInstanceOf($expectedClass, $results[$index]);
        }

        $responses = $successResponse->values();
        $this->assertEquals($responses, $expectedValues);
    }

    public function testItemGetTtl() {
        $cacheName = $this->TEST_CACHE_NAME;
        $key = uniqid();
        $value = uniqid();
        $ttlSeconds = 60;

        $this->client->set($cacheName, $key, $value, $ttlSeconds);

        $itemGetTtlResponse = $this->client->itemGetTtl($cacheName, $key);
        $this->assertNull($itemGetTtlResponse->asError());
        $this->assertNull($itemGetTtlResponse->asMiss());
        $this->assertNotNull($itemGetTtlResponse->asHit(), "Expected a hit but got: $itemGetTtlResponse");

        $ttl = $itemGetTtlResponse->asHit()->remainingTtlMillis();
        $this->assertGreaterThan(0, $ttlSeconds);
    }

    public function testItemGetTtlAsync() {
        $cacheName = $this->TEST_CACHE_NAME;
        $key = uniqid();
        $value = uniqid();
        $ttlSeconds = 60;

        $this->client->setAsync($cacheName, $key, $value, $ttlSeconds)->wait();

        $itemGetTtlResponseFuture = $this->client->itemGetTtlAsync($cacheName, $key);
        $itemGetTtlResponse = $itemGetTtlResponseFuture->wait();
        $this->assertNull($itemGetTtlResponse->asError());
        $this->assertNull($itemGetTtlResponse->asMiss());
        $this->assertNotNull($itemGetTtlResponse->asHit(), "Expected a hit but got: $itemGetTtlResponse");

        $ttl = $itemGetTtlResponse->asHit()->remainingTtlMillis();
        $this->assertGreaterThan(0, $ttlSeconds);
    }

    public function testUpdateTtl() {
        $cacheName = $this->TEST_CACHE_NAME;
        $key = uniqid();
        $value = uniqid();
        $ttlSeconds = 10;
        $newTtlMilliseconds = 60 * 1000;

        $updateTtlResponse = $this->client->updateTtl($cacheName, $key, $newTtlMilliseconds);
        $this->assertNotNull($updateTtlResponse->asMiss(), "Expected a miss but got: $updateTtlResponse");

        $this->client->set($cacheName, $key, $value, $ttlSeconds);

        $updateTtlResponse = $this->client->updateTtl($cacheName, $key, $newTtlMilliseconds);
        $this->assertNull($updateTtlResponse->asError());
        $this->assertNull($updateTtlResponse->asMiss());
        $this->assertNotNull($updateTtlResponse->asSet(), "Expected a set but got: $updateTtlResponse");

        $itemGetTtlResponse = $this->client->itemGetTtl($cacheName, $key);
        $remainingTtl = $itemGetTtlResponse->asHit()->remainingTtlMillis();
        $this->assertGreaterThan($ttlSeconds * 1000, $remainingTtl);
        $this->assertLessThan($newTtlMilliseconds, $remainingTtl);
    }

    public function testUpdateTtlAsync() {
        $cacheName = $this->TEST_CACHE_NAME;
        $key = uniqid();
        $value = uniqid();
        $ttlSeconds = 10;
        $newTtlMilliseconds = 60 * 1000;

        $updateTtlResponseFuture = $this->client->updateTtlAsync($cacheName, $key, $newTtlMilliseconds);
        $updateTtlResponse = $updateTtlResponseFuture->wait();
        $this->assertNotNull($updateTtlResponse->asMiss(), "Expected a miss but got: $updateTtlResponse");

        $this->client->set($cacheName, $key, $value, $ttlSeconds);

        $updateTtlResponseFuture = $this->client->updateTtlAsync($cacheName, $key, $newTtlMilliseconds);
        $updateTtlResponse = $updateTtlResponseFuture->wait();
        $this->assertNull($updateTtlResponse->asError());
        $this->assertNull($updateTtlResponse->asMiss());
        $this->assertNotNull($updateTtlResponse->asSet(), "Expected a set but got: $updateTtlResponse");

        $itemGetTtlResponse = $this->client->itemGetTtl($cacheName, $key);
        $remainingTtl = $itemGetTtlResponse->asHit()->remainingTtlMillis();
        $this->assertGreaterThan($ttlSeconds * 1000, $remainingTtl);
        $this->assertLessThan($newTtlMilliseconds, $remainingTtl);
    }

    public function testIncreaseTtl() {
        $cacheName = $this->TEST_CACHE_NAME;
        $key = uniqid();
        $value = uniqid();
        $initialTtlSeconds = 10;
        $increasedTtlMilliseconds = 60 * 1000;

        // increaseTtl -> Miss
        $increaseTtlResponse = $this->client->increaseTtl($cacheName, $key, $increasedTtlMilliseconds);
        $this->assertNotNull($increaseTtlResponse->asMiss(), "Expected a miss but got: $increaseTtlResponse");

        $this->client->set($cacheName, $key, $value, $initialTtlSeconds);

        // increaseTtl -> NotSet
        $tooLowTtlMilliseconds = 5 * 1000;
        $increaseTtlResponse = $this->client->increaseTtl($cacheName, $key, $tooLowTtlMilliseconds);
        $this->assertNotNull($increaseTtlResponse->asNotSet(), "Expected a not set but got: $increaseTtlResponse");

        // increaseTtl -> Set
        $increaseTtlResponse = $this->client->increaseTtl($cacheName, $key, $increasedTtlMilliseconds);
        $this->assertNull($increaseTtlResponse->asError());
        $this->assertNull($increaseTtlResponse->asMiss());
        $this->assertNotNull($increaseTtlResponse->asSet(), "Expected a set but got: $increaseTtlResponse");

        $itemGetTtlResponse = $this->client->itemGetTtl($cacheName, $key);
        $remainingTtl = $itemGetTtlResponse->asHit()->remainingTtlMillis();
        $this->assertGreaterThan($initialTtlSeconds * 1000, $remainingTtl);
        $this->assertLessThan($increasedTtlMilliseconds, $remainingTtl);
    }

    public function testIncreaseTtlAsync() {
        $cacheName = $this->TEST_CACHE_NAME;
        $key = uniqid();
        $value = uniqid();
        $initialTtlSeconds = 10;
        $increasedTtlMilliseconds = 60 * 1000;

        // increaseTtl -> Miss
        $increaseTtlResponseFuture = $this->client->increaseTtlAsync($cacheName, $key, $increasedTtlMilliseconds);
        $increaseTtlResponse = $increaseTtlResponseFuture->wait();
        $this->assertNotNull($increaseTtlResponse->asMiss(), "Expected a miss but got: $increaseTtlResponse");

        $this->client->set($cacheName, $key, $value, $initialTtlSeconds);

        // increaseTtl -> NotSet
        $tooLowTtlMilliseconds = 5 * 1000;
        $increaseTtlResponseFuture = $this->client->increaseTtlAsync($cacheName, $key, $tooLowTtlMilliseconds);
        $increaseTtlResponse = $increaseTtlResponseFuture->wait();
        $this->assertNotNull($increaseTtlResponse->asNotSet(), "Expected a not set but got: $increaseTtlResponse");

        // increaseTtl -> Set
        $increaseTtlResponseFuture = $this->client->increaseTtlAsync($cacheName, $key, $increasedTtlMilliseconds);
        $increaseTtlResponse = $increaseTtlResponseFuture->wait();
        $this->assertNull($increaseTtlResponse->asError());
        $this->assertNull($increaseTtlResponse->asMiss());
        $this->assertNotNull($increaseTtlResponse->asSet(), "Expected a set but got: $increaseTtlResponse");

        $itemGetTtlResponse = $this->client->itemGetTtl($cacheName, $key);
        $remainingTtl = $itemGetTtlResponse->asHit()->remainingTtlMillis();
        $this->assertGreaterThan($initialTtlSeconds * 1000, $remainingTtl);
        $this->assertLessThan($increasedTtlMilliseconds, $remainingTtl);
    }

    public function testDecreaseTtl() {
        $cacheName = $this->TEST_CACHE_NAME;
        $key = uniqid();
        $value = uniqid();
        $initialTtlSeconds = 30;
        $decreasedTtlMilliseconds = 20 * 1000;

        // decreaseTtl -> Miss
        $decreaseTtlResponse = $this->client->decreaseTtl($cacheName, $key, $decreasedTtlMilliseconds);
        $this->assertNotNull($decreaseTtlResponse->asMiss(), "Expected a miss but got: $decreaseTtlResponse");

        $this->client->set($cacheName, $key, $value, $initialTtlSeconds);

        // decreaseTtl -> NotSet
        $tooHighTtlMilliseconds = 60 * 1000;
        $decreaseTtlResponse = $this->client->decreaseTtl($cacheName, $key, $tooHighTtlMilliseconds);
        $this->assertNotNull($decreaseTtlResponse->asNotSet(), "Expected a not set but got: $decreaseTtlResponse");

        // decreaseTtl -> Set
        $decreaseTtlResponse = $this->client->decreaseTtl($cacheName, $key, $decreasedTtlMilliseconds);
        $this->assertNull($decreaseTtlResponse->asError());
        $this->assertNull($decreaseTtlResponse->asMiss());
        $this->assertNotNull($decreaseTtlResponse->asSet(), "Expected a set but got: $decreaseTtlResponse");

        $itemGetTtlResponse = $this->client->itemGetTtl($cacheName, $key);
        $remainingTtl = $itemGetTtlResponse->asHit()->remainingTtlMillis();
        $this->assertGreaterThan(0, $remainingTtl);
        $this->assertLessThanOrEqual($decreasedTtlMilliseconds, $remainingTtl);
    }

    public function testDecreaseTtlAsync() {
        $cacheName = $this->TEST_CACHE_NAME;
        $key = uniqid();
        $value = uniqid();
        $initialTtlSeconds = 30;
        $decreasedTtlMilliseconds = 20 * 1000;

        // decreaseTtl -> Miss
        $decreaseTtlResponseFuture = $this->client->decreaseTtlAsync($cacheName, $key, $decreasedTtlMilliseconds);
        $decreaseTtlResponse = $decreaseTtlResponseFuture->wait();
        $this->assertNotNull($decreaseTtlResponse->asMiss(), "Expected a miss but got: $decreaseTtlResponse");

        $this->client->set($cacheName, $key, $value, $initialTtlSeconds);

        // decreaseTtl -> NotSet
        $tooHighTtlMilliseconds = 60 * 1000;
        $decreaseTtlResponseFuture = $this->client->decreaseTtlAsync($cacheName, $key, $tooHighTtlMilliseconds);
        $decreaseTtlResponse = $decreaseTtlResponseFuture->wait();
        $this->assertNotNull($decreaseTtlResponse->asNotSet(), "Expected a not set but got: $decreaseTtlResponse");

        // decreaseTtl -> Set
        $decreaseTtlResponseFuture = $this->client->decreaseTtlAsync($cacheName, $key, $decreasedTtlMilliseconds);
        $decreaseTtlResponse = $decreaseTtlResponseFuture->wait();
        $this->assertNull($decreaseTtlResponse->asError());
        $this->assertNull($decreaseTtlResponse->asMiss());
        $this->assertNotNull($decreaseTtlResponse->asSet(), "Expected a set but got: $decreaseTtlResponse");

        $itemGetTtlResponse = $this->client->itemGetTtl($cacheName, $key);
        $remainingTtl = $itemGetTtlResponse->asHit()->remainingTtlMillis();
        $this->assertGreaterThan(0, $remainingTtl);
        $this->assertLessThanOrEqual($decreasedTtlMilliseconds, $remainingTtl);
    }
}
