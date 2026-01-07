<?php

declare(strict_types=1);

namespace Momento\Tests\Cache;

use Momento\Auth\ICredentialProvider;
use Momento\Auth\CredentialProvider;
use Momento\Cache\CacheOperationTypes\GetHit;
use Momento\Cache\CacheOperationTypes\GetMiss;
use Momento\Cache\CacheClient;
use Momento\Cache\Psr16CacheClient;
use Momento\Config\Configurations;
use Momento\Config\IConfiguration;
use Momento\Config\ReadConcern;
use Momento\Requests\CollectionTtl;
use Momento\Requests\SortedSetUnionStoreAggregateFunction;
use PHPUnit\Framework\TestCase;

/**
 * @covers CacheClient
 */
class ApiKeyV2Test extends TestCase
{
    private IConfiguration $configuration;
    private ICredentialProvider $authProvider;
    private int $DEFAULT_TTL_SECONDS = 10;
    private CacheClient $client;
    private Psr16CacheClient $psr16Client;
    private string $TEST_CACHE_NAME;

    public function setUp(): void
    {
        $this->configuration = Configurations\Laptop::latest()->withReadConcern(ReadConcern::CONSISTENT);
        $this->authProvider = CredentialProvider::fromEnvironmentVariablesV2();
        $this->client = new CacheClient($this->configuration, $this->authProvider, $this->DEFAULT_TTL_SECONDS);
        $this->psr16Client = new Psr16CacheClient($this->configuration, $this->authProvider, $this->DEFAULT_TTL_SECONDS);
        $this->TEST_CACHE_NAME = uniqid('php-integration-tests-v2-');
        // Ensure test cache exists
        $createResponse = $this->client->createCache($this->TEST_CACHE_NAME);
        if ($createError = $createResponse->asError()) {
            throw $createError->innerException();
        }
    }

    public function tearDown(): void
    {
        $deleteResponse = $this->client->deleteCache($this->TEST_CACHE_NAME);
        if ($deleteError = $deleteResponse->asError()) {
            throw $deleteError->innerException();
        }
        $this->client->close();
    }

    // Control plane

    public function testCreateListDeleteCache()
    {
        $cacheName = uniqid();
        $response = $this->client->createCache($cacheName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->listCaches();
        $this->assertNull($response->asError());
        $successResp = $response->asSuccess();
        $this->assertNotNull($successResp);
        $caches = $successResp->caches();
        $cacheNames = array_map(fn($i) => $i->name(), $caches);
        $this->assertContains($cacheName, $cacheNames);
        $this->assertEquals("$successResp", get_class($successResp) . ": " . join(', ', $cacheNames));

        $response = $this->client->deleteCache($cacheName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
    }

    public function testFlushCache()
    {
        $key = uniqid();
        $value = uniqid();

        $response = $this->client->set($this->TEST_CACHE_NAME, $key, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->flushCache($this->TEST_CACHE_NAME);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
    }


    // Get, set, delete

    public function testSetGetDelete()
    {
        $key = uniqid();
        $value = uniqid();

        $response = $this->client->set($this->TEST_CACHE_NAME, $key, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals("$response", get_class($response));

        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $response = $response->asHit();
        $this->assertEquals($response->valueString(), $value);
        $this->assertEquals("$response", get_class($response) . ": $value");

        $response = $this->client->delete($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->get($this->TEST_CACHE_NAME, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asMiss(), "Expected a miss but got: $response");
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

    // Other scalar methods

    public function testKeysExist()
    {
        $keysToSet = ["key1", "key2", "key3"];
        foreach ($keysToSet as $key) {
            $response = $this->client->set($this->TEST_CACHE_NAME, $key, "hi");
            $this->assertNull($response->asError());
        }

        $keysToTestAllHits = $keysToSet;
        $keysToTestAllMisses = ["nope1", "nope2", "nope3"];
        $keysToTestMixed = array_merge($keysToTestAllHits, $keysToTestAllMisses);
        $expectAllHits = array_map(function () {
            return true;
        }, $keysToTestAllHits);
        $expectAllHitsDict = array_combine($keysToTestAllHits, $expectAllHits);
        $expectAllMisses = array_map(function () {
            return false;
        }, $keysToTestAllMisses);
        $expectAllMissesDict = array_combine($keysToTestAllMisses, $expectAllMisses);
        $expectMixed = array_map(function ($v) {
            return str_starts_with($v, "key");
        }, $keysToTestMixed);
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

    public function testKeyExists()
    {
        $keysToSet = ["key1", "key2", "key3"];
        foreach ($keysToSet as $key) {
            $response = $this->client->set($this->TEST_CACHE_NAME, $key, "hi");
            $this->assertNull($response->asError());
        }

        $response = $this->client->keyExists($this->TEST_CACHE_NAME, "key2");
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertTrue($response->asSuccess()->exists());

        $response = $this->client->keyExists($this->TEST_CACHE_NAME, "nope99");
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertFalse($response->asSuccess()->exists());
    }

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

    public function testIncrementCreatesKey()
    {
        $key = uniqid();
        $response = $this->client->increment($this->TEST_CACHE_NAME, $key);
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(1, $response->asSuccess()->value());
    }

    public function testItemGetTtl()
    {
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
        $this->assertGreaterThan(0, $ttl);
    }

    public function testUpdateTtl()
    {
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

    // Compare and set methods

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

    // Dictionary

    public function testDictionarySetGetField_HappyPath()
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


    // List

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

    // Set

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

    // Sorted set

    public function testSortedSetPutElements_HappyPath()
    {
        $sortedSetName = uniqid();
        $elements = [
            "bar" => 2.0,
            "baz" => 3,
        ];

        $response = $this->client->sortedSetPutElements($this->TEST_CACHE_NAME, $sortedSetName, $elements);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->sortedSetFetchByRank($this->TEST_CACHE_NAME, $sortedSetName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = [
            "bar" => 2.0,
            "baz" => 3.0,
        ];

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // add more elements to the set
        $newElements = [
            "foo" => 1,
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
        ];

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");
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
        $response = $this->client->sortedSetIncrementScore($this->TEST_CACHE_NAME, $sortedSetName, $value, 3);
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
        $response = $this->client->sortedSetFetchByScore($this->TEST_CACHE_NAME, $sortedSetName, null, null, true, true, SORT_DESC);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = array_reverse($elements);

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // limit by min score inclusive
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

        // limit by min score exclusive
        $response = $this->client->sortedSetFetchByScore($this->TEST_CACHE_NAME, $sortedSetName, 1, null, false);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = [
            "bar" => 2.0,
            "baz" => 3.0,
            "qux" => 4.0,
        ];

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // limit by max score inclusive
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

        // limit by max score exclusive
        $response = $this->client->sortedSetFetchByScore($this->TEST_CACHE_NAME, $sortedSetName, null, 4.0, true, false);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = [
            "foo" => 1.0,
            "bar" => 2.0,
            "baz" => 3.0,
        ];

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // limit by min and max score inclusive
        $response = $this->client->sortedSetFetchByScore($this->TEST_CACHE_NAME, $sortedSetName, 1.1, 3.1);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = [
            "bar" => 2.0,
            "baz" => 3.0,
        ];

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // limit by min and max score exclusive
        $response = $this->client->sortedSetFetchByScore($this->TEST_CACHE_NAME, $sortedSetName, 1, 4, false, false);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = [
            "bar" => 2.0,
            "baz" => 3.0,
        ];

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // descending with min and max score
        $response = $this->client->sortedSetFetchByScore($this->TEST_CACHE_NAME, $sortedSetName, 1.1, 3.0, true, true, SORT_DESC);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = [
            "baz" => 3.0,
            "bar" => 2.0,
        ];

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // skip elements with offset
        $response = $this->client->sortedSetFetchByScore($this->TEST_CACHE_NAME, $sortedSetName, null, null, true, true, SORT_ASC, 2);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = [
            "baz" => 3.0,
            "qux" => 4.0,
        ];

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // limit by min score and skip by offset
        $response = $this->client->sortedSetFetchByScore($this->TEST_CACHE_NAME, $sortedSetName, 1.1, null, true, true, SORT_ASC, 2);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = [
            "qux" => 4.0,
        ];

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // limit by count
        $response = $this->client->sortedSetFetchByScore($this->TEST_CACHE_NAME, $sortedSetName, null, null, true, true, SORT_ASC, null, 2);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = [
            "foo" => 1.0,
            "bar" => 2.0,
        ];

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");

        // limit by count to 0
        $response = $this->client->sortedSetFetchByScore($this->TEST_CACHE_NAME, $sortedSetName, null, null, true, true, SORT_ASC, null, 0);
        $this->assertNull($response->asError(), "Error occurred while fetching sorted set '$sortedSetName'");
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");

        $fetchedElements = $response->asHit()->valuesArray();
        $expectedElements = [];

        $this->assertSame($expectedElements, $fetchedElements, "The fetched elements did not match the expected elements.");
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

        // limit by min score (exclusive)
        $response = $this->client->sortedSetLengthByScore($this->TEST_CACHE_NAME, $sortedSetName, 1, null, false);
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

        // limit by max score (exclusive)
        $response = $this->client->sortedSetLengthByScore($this->TEST_CACHE_NAME, $sortedSetName, null, 4, true, false);
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

        // limit by min and max score (exclusive)
        $response = $this->client->sortedSetLengthByScore($this->TEST_CACHE_NAME, $sortedSetName, 1, 4, false, false);
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

    public function unionSets($sets, $dest, $weights, $aggregate, $expected, $returnResponse = false)
    {
        $sources = [];
        foreach ($sets as $setName => $elements) {
            $this->client->sortedSetPutElements($this->TEST_CACHE_NAME, $setName, $elements);
            $sources[] = ["setName" => $setName, "weight" => $weights[$setName]];
        }
        $response = $this->client->sortedSetUnionStore(
            $this->TEST_CACHE_NAME,
            $dest,
            $sources,
            $aggregate,
            60
        );

        if ($returnResponse) {
            return $response;
        }

        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertEquals(count($expected), $response->asSuccess()->length());

        $response = $this->client->sortedSetFetchByScore($this->TEST_CACHE_NAME, $dest);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asHit(), "Expected a hit but got: $response");
        $this->assertEquals($expected, $response->asHit()->valuesArray());
    }

    public function testSortedSetUnionStore_HappyPath()
    {
        $sortedSetName1 = uniqid();
        $sortedSetName2 = uniqid();
        $sortedSetName3 = uniqid();

        $elements1 = [
            "foo" => 1.0,
            "bar" => 2.0,
            "baz" => 3.0,
        ];

        $elements2 = [
            "abc" => 4.0,
            "def" => 5.0,
            "hij" => 6.0,
        ];

        $expectedElements = [
            "foo" => 1.0,
            "bar" => 2.0,
            "baz" => 3.0,
            "abc" => 4.0,
            "def" => 5.0,
            "hij" => 6.0,
        ];
        $this->unionSets(
            [
                $sortedSetName1 => $elements1,
                $sortedSetName2 => $elements2,
            ],
            $sortedSetName3,
            [
                $sortedSetName1 => 1,
                $sortedSetName2 => 1,
            ],
            SortedSetUnionStoreAggregateFunction::SUM,
            $expectedElements
        );
    }

    // Psr16CacheClient

    public function testGetMultiple_HappyPath()
    {
        $items = [
            "key1" => 1,
            "key2" => 2,
            "key3" => 3
        ];
        foreach ($items as $k => $v) {
            $this->assertTrue($this->psr16Client->set($k, $v));
        }
        $multiValues = $this->psr16Client->getMultiple(array_keys($items));
        $this->assertSame($items, $multiValues);
    }

    public function testSetMultiple_HappyPath()
    {
        $items = [
            "key1" => 1,
            "key2" => 2,
            "key3" => 3
        ];
        $this->assertTrue($this->psr16Client->setMultiple($items));
        $values = [];
        foreach ($items as $k => $v) {
            $this->assertTrue((bool)$values[$k] = $this->psr16Client->get($k, $v));
        }
        $this->assertSame($items, $values);
    }

    public function testDeleteMultiple_HappyPath()
    {
        $items = [
            "key1" => 1,
            "key2" => 2,
            "key3" => 3
        ];
        $this->assertTrue($this->psr16Client->setMultiple($items));
        $values = [];
        foreach ($items as $k => $v) {
            $this->assertTrue((bool)$values[$k] = $this->psr16Client->get($k, $v));
        }
        $this->assertSame($items, $values);
        $this->assertTrue($this->psr16Client->deleteMultiple(array_keys($items)));
        foreach ($items as $k => $v) {
            $this->assertFalse($this->psr16Client->has($k, $v));
        }
    }
}
