<?php
declare(strict_types=1);

require "vendor/autoload.php";

use Momento\Auth\CredentialProvider;
use Momento\Cache\CacheClient;
use Momento\Config\Configurations\Laptop;
use Momento\Config\Configurations\Storage\Laptop as StorageLaptop;
use Momento\Storage\PreviewStorageClient;
use Momento\Storage\StorageOperationTypes\StorageValueType;

function example_API_InstantiateCacheClient()
{
    new CacheClient(
        Laptop::latest(),
        CredentialProvider::fromEnvironmentVariable("MOMENTO_API_KEY"),
        60
    );
}

function example_API_InstantiateStorageClient()
{
    new PreviewStorageClient(
        StorageLaptop::latest(),
        CredentialProvider::fromEnvironmentVariable("MOMENTO_API_KEY"),
    );
}

function example_API_CredentialProviderFromEnvVar()
{
    CredentialProvider::fromEnvironmentVariable("MOMENTO_API_KEY");
}

function example_API_CreateCache(CacheClient $cache_client, string $cache_name)
{
    $create_cache_response = $cache_client->createCache($cache_name);
    if ($create_cache_response->asSuccess()) {
        print("Cache $cache_name created\n");
    } elseif ($create_cache_response->asAlreadyExists()) {
        print("Cache $cache_name already exists\n");
    } elseif ($err = $create_cache_response->asError()) {
        print("An error occurred while attempting to create $cache_name: {$err->errorCode()} - {$err->message()}\n");
    }
}

function example_API_DeleteCache(CacheClient $cache_client, string $cache_name)
{
    $delete_cache_response = $cache_client->deleteCache($cache_name);
    if ($err = $delete_cache_response->asError()) {
        print("An error occurred while attempting to delete $cache_name: {$err->errorCode()} - {$err->message()}\n");
    } else {
        print("Cache $cache_name deleted\n");
    }
}

function example_API_ListCaches(CacheClient $cache_client)
{
    $list_caches_response = $cache_client->listCaches();
    if ($success = $list_caches_response->asSuccess()) {
        print("Found caches:\n");
        foreach ($success->caches() as $cache) {
            $cache_name = $cache->name();
            print("- $cache_name\n");
        }
    } elseif ($err = $list_caches_response->asError()) {
        print("An error occurred while attempting to list caches: {$err->errorCode()} - {$err->message()}\n");
    }
}

function example_API_Set(CacheClient $cache_client, string $cache_name) {
    $set_response = $cache_client->set($cache_name, "test-key", "test-value");
    if ($set_response->asSuccess()) {
        print("Key $cache_name stored successfully\n");
    } elseif ($err = $set_response->asError()) {
        print("An error occurred while attempting to store $cache_name: {$err->errorCode()} - {$err->message()}\n");
    }
}

function example_API_Get(CacheClient $cache_client, string $cache_name)
{
    $get_response = $cache_client->get($cache_name, "test-key");
    if ($hit = $get_response->asHit()) {
        print("Retrieved value for key 'test-key': {$hit->valueString()}\n");
    } elseif ($get_response->asMiss()) {
        print("Key 'test-key' was not found in cache $cache_name\n");
    } elseif ($err = $get_response->asError()) {
        print("An error occurred while attempting to get key 'test-key' from cache $cache_name: {$err->errorCode()} - {$err->message()}\n");
    }
}

function example_API_Delete(CacheClient $cache_client, string $cache_name)
{
    $delete_response = $cache_client->delete($cache_name, "test-key");
    if ($delete_response->asSuccess()) {
        print("Key 'test-key' deleted successfully\n");
    } elseif ($err = $delete_response->asError()) {
        print("An error occurred while attempting to delete key 'test-key' from cache $cache_name: {$err->errorCode()} - {$err->message()}\n");
    }
}

function example_API_Storage_CreateStore(PreviewStorageClient $storage_client, string $store_name)
{
    $create_store_response = $storage_client->createStore($store_name);
    if ($create_store_response->asSuccess()) {
        print("Store $store_name created\n");
    } elseif ($create_store_response->asAlreadyExists()) {
        print("Store $store_name already exists\n");
    } elseif ($err = $create_store_response->asError()) {
        print("An error occurred while attempting to create $store_name: {$err->errorCode()} - {$err->message()}\n");
    }
}

function example_API_Storage_ListStores(PreviewStorageClient $storage_client)
{
    $list_stores_response = $storage_client->listStores();
    if ($listSuccess = $list_stores_response->asSuccess()) {
        print("Found stores:\n");
        foreach ($listSuccess->stores() as $store) {
            $store_name = $store->name();
            print("- $store_name\n");
        }
    } elseif ($err = $list_stores_response->asError()) {
        print("An error occurred while attempting to list stores: {$err->errorCode()} - {$err->message()}\n");
    }
}

function example_API_Storage_Put(PreviewStorageClient $storage_client, string $store_name)
{
    $put_response = $storage_client->putString($store_name, "test-key", "test-value");
    if ($put_response->asSuccess()) {
        print("Key 'test-key' stored successfully\n");
    } elseif ($err = $put_response->asError()) {
        print("An error occurred while attempting to store 'test-key': {$err->errorCode()} - {$err->message()}\n");
    }

    // Momento storage also supports int, float, and bytes types.
    // Because strings in PHP are a series of bytes, the putBytes method accepts a string as the value.
    $put_response = $storage_client->putBytes($store_name, "test-key", "test-value");
    $put_response = $storage_client->putInt($store_name, "test-key", 42);
    $put_response = $storage_client->putFloat($store_name, "test-key", 3.14);
}

function example_API_Storage_Get(PreviewStorageClient $storage_client, string $store_name)
{
    $get_response = $storage_client->get($store_name, "test-key");
    if ($found = $get_response->asFound()) {
        $value_type = $found->type();
        if ($value_type == StorageValueType::STRING) {
            print("Got string value: " . $found->valueString() . "\n");
        } elseif ($value_type == StorageValueType::INT) {
            print("Got integer value: " . $found->valueInt() . "\n");
        } elseif ($value_type == StorageValueType::FLOAT) {
            print("Got float value: " . $found->valueFloat() . "\n");
        } elseif ($value_type == StorageValueType::BYTES) {
            // This case is not expected in this example as PHP doesn't have a native byte type
            print("Got bytes value: " . $found->valueBytes() . "\n");
        }
        // You may also pull the value directly from the response without type checking
        print("Retrieved value for key 'test-key': {$get_response->value()}\n");
    } elseif ($get_response->asNotFound()) {
        print("Key 'test-key' was not found in store $store_name\n");
    } elseif ($err = $get_response->asError()) {
        print("An error occurred while attempting to get key 'test-key' from store $store_name: {$err->errorCode()} - {$err->message()}\n");
    }
}

function example_API_Storage_Delete(PreviewStorageClient $storage_client, string $store_name)
{
    $delete_response = $storage_client->delete($store_name, "test-key");
    if ($delete_response->asSuccess()) {
        print("Key 'test-key' deleted successfully\n");
    } elseif ($err = $delete_response->asError()) {
        print("An error occurred while attempting to delete key 'test-key' from store $store_name: {$err->errorCode()} - {$err->message()}\n");
    }
}

function example_API_DeleteStore(PreviewStorageClient $storage_client, string $store_name)
{
    $delete_store_response = $storage_client->deleteStore($store_name);
    if ($err = $delete_store_response->asError()) {
        print("An error occurred while attempting to delete $store_name: {$err->errorCode()} - {$err->message()}\n");
    } else {
        print("Store $store_name deleted\n");
    }
}

function setup()
{
    $cache_client = new CacheClient(
        Laptop::latest(),
        CredentialProvider::fromEnvironmentVariable("MOMENTO_API_KEY"),
        60
    );
    $storage_client = new PreviewStorageClient(
        StorageLaptop::latest(),
        CredentialProvider::fromEnvironmentVariable("MOMENTO_API_KEY"),
    );
    $cache_name = uniqid("php-examples-test-cache-");
    $store_name = uniqid("php-examples-test-store-");
    return [
        $cache_client,
        $storage_client,
        $cache_name,
        $store_name
    ];
}

function teardown($cache_client, $storage_client, $cache_name, $store_name)
{
    $cache_client->deleteCache($cache_name);
    $storage_client->deleteStore($store_name);
}

function main() {
    [$cache_client, $storage_client, $cache_name, $store_name] = setup();

    try {
        example_API_CredentialProviderFromEnvVar();

        example_API_InstantiateCacheClient();
        example_API_CreateCache($cache_client, $cache_name);
        example_API_ListCaches($cache_client);

        example_API_Set($cache_client, $cache_name);
        example_API_Get($cache_client, $cache_name);
        example_API_Delete($cache_client, $cache_name);

        example_API_DeleteCache($cache_client, $cache_name);

        example_API_InstantiateStorageClient();
        example_API_Storage_CreateStore($storage_client, $store_name);
        example_API_Storage_ListStores($storage_client);
        example_API_Storage_Put($storage_client, $store_name);
        example_API_Storage_Get($storage_client, $store_name);
        example_API_Storage_Delete($storage_client, $store_name);
        example_API_DeleteStore($storage_client, $store_name);
    } finally {
        teardown($cache_client, $storage_client, $cache_name, $store_name);
    }
}

main();
