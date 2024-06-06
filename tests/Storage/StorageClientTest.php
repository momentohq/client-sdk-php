<?php
declare(strict_types=1);

namespace Momento\Tests\Storage;

use Momento\Auth\EnvMomentoTokenProvider;
use Momento\Cache\Errors\MomentoErrorCode;
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

    public function testCreateStoreEmptyName()
    {
        $response = $this->client->createStore("");
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

    public function testDeleteUnknownStore()
    {
        $storeName = uniqid();
        $response = $this->client->deleteStore($storeName);
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::NOT_FOUND_ERROR, $response->asError()->errorCode());
    }

    public function testDeleteNullStoreName()
    {
        $this->expectException(TypeError::class);
        $this->client->deleteStore(null);
    }

    public function testDeleteEmptyStoreName()
    {
        $response = $this->client->deleteStore("");
        $this->assertNotNull($response->asError(), "Expected error but got: $response");
        $this->assertEquals(MomentoErrorCode::INVALID_ARGUMENT_ERROR, $response->asError()->errorCode());
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
        $response = $this->client->set($storeName, $key, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->get($storeName, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $response = $response->asSuccess();
        $this->assertEquals($response->type(), StorageValueType::STRING);
        $this->assertEquals($response->tryGetString(), $value);
        $this->assertEquals("$response", get_class($response) . ": $value");
        $this->assertEquals(null, $response->tryGetInteger());
        $this->assertEquals(null, $response->tryGetDouble());

        $response = $this->client->delete($storeName, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        // TODO: Not sure what to expect here from what used to be a Miss. Will need to
        //  tweak these tests when the service is live.
        $response = $this->client->get($storeName, $key);
        $this->assertNotNull($response->asError());
    }

    public function testSetGetDeleteInteger()
    {
        $storeName = $this->TEST_STORE_NAME;
        $key = uniqid();
        $value = 42;
        $response = $this->client->set($storeName, $key, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->get($storeName, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $response = $response->asSuccess();
        $this->assertEquals($response->type(), StorageValueType::INTEGER);
        $this->assertEquals($response->tryGetInteger(), $value);
        $this->assertEquals("$response", get_class($response) . ": $value");
        $this->assertEquals(null, $response->tryGetString());
        $this->assertEquals(null, $response->tryGetDouble());

        $response = $this->client->delete($storeName, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->get($storeName, $key);
        $this->assertNotNull($response->asError());
    }

    public function testSetGetDeleteDouble()
    {
        $storeName = $this->TEST_STORE_NAME;
        $key = uniqid();
        $value = 3.14;
        $response = $this->client->set($storeName, $key, $value);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->get($storeName, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");
        $response = $response->asSuccess();
        $this->assertEquals($response->type(), StorageValueType::DOUBLE);
        $this->assertEquals($response->tryGetDouble(), $value);
        $this->assertEquals("$response", get_class($response) . ": $value");
        $this->assertEquals(null, $response->tryGetString());
        $this->assertEquals(null, $response->tryGetInteger());

        $response = $this->client->delete($storeName, $key);
        $this->assertNull($response->asError());
        $this->assertNotNull($response->asSuccess(), "Expected a success but got: $response");

        $response = $this->client->get($storeName, $key);
        $this->assertNotNull($response->asError());
    }

    public function testMissingStoreName()
    {
        $storeName = "";
        $key = uniqid();
        $value = "hello, world!";
        $response = $this->client->set($storeName, $key, $value);
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
        $response = $this->client->set($storeName, $key, $value);
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
        $response = $this->client->set($storeName, $key, $value);
        // quick test for null value
        $this->expectException(TypeError::class);
        $response = $this->client->set($storeName, "aRealKey", null);
        $this->expectException(TypeError::class);
        $response = $this->client->get($storeName, $key);
        $this->expectException(TypeError::class);
        $response = $this->client->delete($storeName, $key);
    }
}
