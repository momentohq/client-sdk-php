<?php
declare(strict_types=1);

namespace Momento\Tests\Storage;

use Momento\Auth\EnvMomentoTokenProvider;
use Momento\Cache\Errors\MomentoErrorCode;
use Momento\Cache\Errors\NotFoundError;
use Momento\Config\IStorageConfiguration;
use Momento\Config\Configurations;
use Momento\Config\StorageConfiguration;
use Momento\Config\Transport\StaticGrpcConfiguration;
use Momento\Config\Transport\StaticTransportStrategy;
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
        $this->configuration = Configurations\StorageLaptop::latest();
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
        $grpcConfig = new StaticGrpcConfiguration($deadline);
        $transportStrategy = new StaticTransportStrategy($grpcConfig, $loggerFactory);
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

    public function testStoreNotFound()
    {
        $storeName = uniqid();
        $key = uniqid();
        $response = $this->client->get($storeName, $key);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::NOT_FOUND_ERROR, $response->asError()->errorCode());
        $response = $this->client->put($storeName, $key, "hello, world!");
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::NOT_FOUND_ERROR, $response->asError()->errorCode());
        $response = $this->client->delete($storeName, $key);
        $this->assertNotNull($response->asError());
        $this->assertEquals(MomentoErrorCode::NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testMissingStoreName()
    {
        $storeName = "";
        $key = uniqid();
        $value = "hello, world!";
        $response = $this->client->put($storeName, $key, $value);
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
        $response = $this->client->put($storeName, $key, $value);
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
        $response = $this->client->put($storeName, $key, $value);
        // quick test for null value
        $this->expectException(TypeError::class);
        $response = $this->client->put($storeName, "aRealKey", null);
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
    public function testSetGetDeleteStringExplicit()
    {
        $storeName = $this->TEST_STORE_NAME;
        $key = uniqid();
        $value = "hello, world!";
        $response = $this->client->putString($storeName, $key, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->setGetDeleteTestCommon($storeName, $key, $value, StorageValueType::STRING);
    }

    public function testSetGetDeleteStringImplicit()
    {
        $storeName = $this->TEST_STORE_NAME;
        $key = uniqid();
        $value = "hello, world!";
        $response = $this->client->put($storeName, $key, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->setGetDeleteTestCommon($storeName, $key, $value, StorageValueType::STRING);
    }

    public function testSetGetDeleteBytesExplicit()
    {
        $storeName = $this->TEST_STORE_NAME;
        $key = uniqid();
        $value = "hello, world!";
        $response = $this->client->putBytes($storeName, $key, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->setGetDeleteTestCommon($storeName, $key, $value, StorageValueType::BYTES);
    }

    public function testSetGetDeleteBytesImplicit()
    {
        $storeName = $this->TEST_STORE_NAME;
        $key = uniqid();
        $value = "hello, world!";
        $response = $this->client->put($storeName, $key, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        // PHP doesn't have and can't detect a "bytes" type, so the implicit case here will assume
        // that the value is a string and store it as such.
        $this->setGetDeleteTestCommon($storeName, $key, $value, StorageValueType::STRING);
    }

    public function testSetGetDeleteIntegerExplicit()
    {
        $storeName = $this->TEST_STORE_NAME;
        $key = uniqid();
        $value = 42;
        $response = $this->client->putInteger($storeName, $key, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->setGetDeleteTestCommon($storeName, $key, $value, StorageValueType::INTEGER);
    }

    public function testSetGetDeleteIntegerImplicit()
    {
        $storeName = $this->TEST_STORE_NAME;
        $key = uniqid();
        $value = 42;
        $response = $this->client->put($storeName, $key, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->setGetDeleteTestCommon($storeName, $key, $value, StorageValueType::INTEGER);
    }

    public function testSetGetDeleteDoubleExplicit()
    {
        $storeName = $this->TEST_STORE_NAME;
        $key = uniqid();
        $value = 3.14;
        $response = $this->client->putDouble($storeName, $key, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->setGetDeleteTestCommon($storeName, $key, $value, StorageValueType::DOUBLE);
    }

    public function testSetGetDeleteDoubleImplicit()
    {
        $storeName = $this->TEST_STORE_NAME;
        $key = uniqid();
        $value = 3.14;
        $response = $this->client->put($storeName, $key, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $this->setGetDeleteTestCommon($storeName, $key, $value, StorageValueType::DOUBLE);
    }

    private function setGetDeleteTestCommon($storeName, $key, $value, string $type)
    {
        $response = $this->client->get($storeName, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $response = $response->asSuccess();
        $this->assertEquals($response->type(), $type);
        $this->assertEquals(get_class($response) . ": $value", "$response");

        if ($type != StorageValueType::STRING) {
            $this->assertEquals(null, $response->valueString());
        } else {
            $this->assertEquals($response->valueString(), $value);
        }

        if ($type != StorageValueType::INTEGER) {
            $this->assertEquals(null, $response->valueInteger());
        } else {
            $this->assertEquals($response->valueInteger(), $value);
        }

        if ($type != StorageValueType::DOUBLE) {
            $this->assertEquals(null, $response->valueDouble());
        } else {
            $this->assertEquals($response->valueDouble(), $value);
        }

        if ($type != StorageValueType::BYTES) {
            $this->assertEquals(null, $response->valueBytes());
        } else {
            $this->assertEquals($response->valueBytes(), $value);
        }

        $response = $this->client->delete($storeName, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->get($storeName, $key);
        $this->assertNotNull($response->asError());
        // TODO: This will be changing such that an element not found will not
        //  be surfaced as an error. Instead, it will be a "miss", with some TBD way to
        //  signify the difference between a miss and a user asking for the wrong data type.
        $this->expectException(NotFoundError::class);
        print($response->value());
    }

}
