<?php
declare(strict_types=1);

namespace Momento\Tests\Cache;

use Momento\Auth\AuthUtils;
use Momento\Auth\EnvMomentoTokenProvider;
use Momento\Cache\CacheOperationTypes\DictionaryGetFieldHit;
use Momento\Cache\CacheOperationTypes\DictionaryGetFieldMiss;
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
        $this->authProvider = new EnvMomentoTokenProvider("TEST_AUTH_TOKEN");
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
        $this->expectExceptionMessage("TTL Seconds must be a non-negative integer");
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
        $this->client->set($this->TEST_CACHE_NAME, null, "bar");
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
        $items = [""];
        $response = $this->client->dictionarySetFields($this->TEST_CACHE_NAME, $dictionaryName, $items, CollectionTtl::fromCacheTtl()->withNoRefreshTtlOnUpdates());
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());

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
        $field1 = uniqid();
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
        $field1 = uniqid();
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

        $responses = $response->asSuccess()->getResponses();
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

        $getBatchResponse = $this->client->getBatch($cacheName, $keys);

        $this->assertNull($getBatchResponse->asError());
        $this->assertNotNull($getBatchResponse->asSuccess(), "Expected a success but got: $getBatchResponse");

        $responses = $getBatchResponse->asSuccess()->getResponses();
        $this->assertCount(sizeof($keys), $responses);
        $this->assertEquals($responses, $expectedValues);
    }
}
