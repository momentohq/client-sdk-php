<?php
declare(strict_types=1);

namespace Momento\Tests\Storage;

use Momento\Auth\EnvMomentoTokenProvider;
use Momento\Cache\Errors\MomentoErrorCode;
use Momento\Cache\Errors\StoreNotFoundError;
use Momento\Config\Configurations;
use Momento\Config\IStorageConfiguration;
use Momento\Config\StorageConfiguration;
use Momento\Config\Transport\StaticStorageGrpcConfiguration;
use Momento\Config\Transport\StaticStorageTransportStrategy;
use Momento\Logging\NullLoggerFactory;
use Momento\Storage\PreviewStorageClient;
use Momento\Storage\StorageOperationTypes\StorageValueType;
use PHPUnit\Framework\TestCase;
use TypeError;

/**
 * @covers \Momento\Storage\PreviewStorageClient
 */
class StorageClientTest extends TestCase
{
    private IStorageConfiguration $configuration;
    private EnvMomentoTokenProvider $authProvider;
    private PreviewStorageClient $client;
    private string $TEST_STORE_NAME;

    public function setUp(): void
    {
        $this->configuration = Configurations\Storage\Laptop::latest();
        $this->authProvider = new EnvMomentoTokenProvider("TEST_AUTH_TOKEN");
        $this->client = new PreviewStorageClient($this->configuration, $this->authProvider);
        $this->TEST_STORE_NAME = uniqid("php-storage-integration-tests-");
        // Create a store for testing
        $createResponse = $this->client->createStore($this->TEST_STORE_NAME);
        if ($createError = $createResponse->asError()) {
            throw $createError->innerException();
        }
    }

    public function tearDown(): void
    {
        // Delete the store after testing
        $deleteResponse = $this->client->deleteStore($this->TEST_STORE_NAME);
        if ($deleteError = $deleteResponse->asError()) {
            throw $deleteError->innerException();
        }
        $this->client->close();
    }

    private function getStoreNames(): array
    {
        $response = $this->client->listStores();
        $this->assertNotNull($response->asSuccess());
        return array_map(fn($i) => $i->name(), $response->asSuccess()->stores());
    }

    private function getConfigurationWithDeadline(int $deadline): StorageConfiguration
    {
        $loggerFactory = new NullLoggerFactory();
        $grpcConfig = new StaticStorageGrpcConfiguration($deadline);
        $transportStrategy = new StaticStorageTransportStrategy($grpcConfig, $loggerFactory);
        return new StorageConfiguration($loggerFactory, $transportStrategy);
    }

    // Happy Path Tests
    public function testCreateAndCloseClient() {
        $client = new PreviewStorageClient($this->configuration, $this->authProvider);
        $response = $client->listStores();
        $this->assertNull($response->asError());
        $client->close();
        $client = new PreviewStorageClient($this->configuration, $this->authProvider);
        $response = $client->listStores();
        $this->assertNull($response->asError());
        $client->close();
    }

    public function testCreateDeleteStore()
    {
        $storeName = uniqid();
        $response = $this->client->createStore($storeName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertContains($storeName, $this->getStoreNames());

        $response = $this->client->deleteStore($storeName);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->assertNotContains($storeName, $this->getStoreNames());
    }

    // Error Path Tests
    public function testNegativeRequestTimeout()
    {
        $this->expectExceptionMessage("Request timeout must be greater than zero.");
        $configuration = $this->getConfigurationWithDeadline(-1);
        $client = new PreviewStorageClient($configuration, $this->authProvider);
    }

    public function testZeroRequestTimeout()
    {
        $this->expectExceptionMessage("Request timeout must be greater than zero.");
        $configuration = $this->getConfigurationWithDeadline(0);
        $client = new PreviewStorageClient($configuration, $this->authProvider);
    }

    public function testCreateStoreAlreadyExists()
    {
        $response = $this->client->createStore($this->TEST_STORE_NAME);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asAlreadyExists());
    }

    public function testCreateDeleteStoreEmptyName()
    {
        $response = $this->client->createStore("");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $response = $response->asError();
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->errorCode());
        $this->assertEquals("$response", get_class($response) . ": {$response->message()}");
        $response = $this->client->deleteStore("");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $response = $response->asError();
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->errorCode());
        $this->assertEquals("$response", get_class($response) . ": {$response->message()}");
    }

    public function testCreateStoreNullName()
    {
        $this->expectException(TypeError::class);
        $this->client->createStore(null);
    }

    public function testCreateStoreBadName()
    {
        $this->expectException(TypeError::class);
        $this->client->createStore(1);
    }

    public function testDeleteStoreNullName()
    {
        $this->expectException(TypeError::class);
        $this->client->deleteStore(null);
    }

    public function testDeleteNonexistentStore()
    {
        $storeName = uniqid();
        $response = $this->client->deleteStore($storeName);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::STORE_NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testStoreNotFound()
    {
        $storeName = uniqid();
        $key = uniqid();
        $response = $this->client->get($storeName, $key);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::STORE_NOT_FOUND_ERROR, $response->asError()->errorCode());
        $response = $this->client->putString($storeName, $key, "hello, world!");
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::STORE_NOT_FOUND_ERROR, $response->asError()->errorCode());
        $response = $this->client->delete($storeName, $key);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::STORE_NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testMissingStoreName()
    {
        $storeName = "";
        $key = uniqid();
        $value = "hello, world!";
        $response = $this->client->putString($storeName, $key, $value);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
        $response = $this->client->get($storeName, $key);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
        $response = $this->client->delete($storeName, $key);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testMissingKey()
    {
        $storeName = $this->TEST_STORE_NAME;
        $key = "";
        $value = "hello, world!";
        $response = $this->client->putString($storeName, $key, $value);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
        $response = $this->client->get($storeName, $key);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
        $response = $this->client->delete($storeName, $key);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
    }

    public function testInvalidKey()
    {
        $storeName = $this->TEST_STORE_NAME;
        $key = 42;
        $value = "hello, world!";
        $this->expectException(TypeError::class);
        $response = $this->client->putString($storeName, $key, $value);
        // quick test for null value
        $this->expectException(TypeError::class);
        $response = $this->client->putString($storeName, "aRealKey", null);
        $this->expectException(TypeError::class);
        $response = $this->client->get($storeName, $key);
        $this->expectException(TypeError::class);
        $response = $this->client->delete($storeName, $key);
    }

    // List stores tests
    public function testListStores()
    {
        $storeName = uniqid();
        $response = $this->client->listStores();
        $this->assertNull($response->asError());
        $successResp = $response->asSuccess();
        $this->assertNotNull($successResp);
        $stores = $successResp->stores();
        $storeNames = array_map(fn($i) => $i->name(), $stores);
        $this->assertNotContains($storeName, $storeNames);
        try {
            $response = $this->client->createStore($storeName);
            $this->assertNull($response->asError());

            $listStoresResponse = $this->client->listStores();
            $this->assertNull($listStoresResponse->asError());
            $listStoresResponse = $listStoresResponse->asSuccess();
            $stores = $listStoresResponse->stores();
            $storeNames = array_map(fn($i) => $i->name(), $stores);
            $this->assertContains($storeName, $storeNames);
            $this->assertEquals("$listStoresResponse", get_class($listStoresResponse) . ": " . join(', ', $storeNames));
        } finally {
            $response = $this->client->deleteStore($storeName);
            $this->assertNull($response->asError());
        }
    }

    // Get, Set, Delete tests
    public function testSetGetDeleteString()
    {
        $storeName = $this->TEST_STORE_NAME;
        $key = uniqid();
        $value = "hello, world!";
        $response = $this->client->putString($storeName, $key, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->setGetDeleteTestCommon($storeName, $key, $value, StorageValueType::STRING);
    }

    public function testSetGetDeleteBytes()
    {
        $storeName = $this->TEST_STORE_NAME;
        $key = uniqid();
        $value = "hello, world!";
        $response = $this->client->putBytes($storeName, $key, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->setGetDeleteTestCommon($storeName, $key, $value, StorageValueType::BYTES);
    }

    public function testSetGetDeleteInt()
    {
        $storeName = $this->TEST_STORE_NAME;
        $key = uniqid();
        $value = 42;
        $response = $this->client->putInt($storeName, $key, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->setGetDeleteTestCommon($storeName, $key, $value, StorageValueType::INT);
    }

    public function testSetGetDeleteFloat()
    {
        $storeName = $this->TEST_STORE_NAME;
        $key = uniqid();
        $value = 3.14;
        $response = $this->client->putFloat($storeName, $key, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->setGetDeleteTestCommon($storeName, $key, $value, StorageValueType::FLOAT);
    }

    private function setGetDeleteTestCommon($storeName, $key, $value, string $type)
    {
        $response = $this->client->get($storeName, $key);
        $this->assertNull($response->asError());
        $this->assertNull($response->asNotFound());
        $this->assertNotNull($response->asFound(), "Expected a success but got: $response");
        $response = $response->asFound();
        $this->assertEquals($response->type(), $type);
        $this->assertEquals(get_class($response) . ": $value", "$response");

        if ($type != StorageValueType::STRING) {
            $this->assertEquals(null, $response->valueString());
        } else {
            $this->assertEquals($response->valueString(), $value);
        }

        if ($type != StorageValueType::INT) {
            $this->assertEquals(null, $response->valueInt());
        } else {
            $this->assertEquals($response->valueInt(), $value);
        }

        if ($type != StorageValueType::FLOAT) {
            $this->assertEquals(null, $response->valueFloat());
        } else {
            $this->assertEquals($response->valueFloat(), $value);
        }

        if ($type != StorageValueType::BYTES) {
            $this->assertEquals(null, $response->valueBytes());
        } else {
            $this->assertEquals($response->valueBytes(), $value);
        }

        $response = $this->client->delete($storeName, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        // verify the behavior of a get request for a missing key
        $response = $this->client->get($storeName, $key);
        $this->assertNull($response->asError());
        $this->assertNull($response->asFound());
        $this->assertNotNull($response->asNotFound(), "Expected a success but got: $response");
        $this->assertNull($response->type());
        $this->assertNull($response->value());
        $this->assertNull($response->valueBytes());
        $this->assertNull($response->valueFloat());
        $this->assertNull($response->valueInt());
        $this->assertNull($response->valueString());
    }

    // Error response attribute test
    public function testGetError()
    {
        $storeName = uniqid();
        $key = uniqid();
        $response = $this->client->get($storeName, $key);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::STORE_NOT_FOUND_ERROR, $response->asError()->errorCode());
        $this->expectException(StoreNotFoundError::class);
        $response->value();
        $this->expectException(StoreNotFoundError::class);
        $response->valueInt();
        $this->expectException(StoreNotFoundError::class);
        $response->valueString();
        $this->expectException(StoreNotFoundError::class);
        $response->valueFloat();
        $this->expectException(StoreNotFoundError::class);
        $response->valueBytes();
    }

}
