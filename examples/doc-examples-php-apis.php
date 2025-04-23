<?php
declare(strict_types=1);

require "vendor/autoload.php";

use Momento\Auth\CredentialProvider;
use Momento\Cache\CacheClient;
use Momento\Config\Configurations\Laptop;

function example_API_InstantiateCacheClient()
{
    new CacheClient(
        Laptop::latest(),
        CredentialProvider::fromEnvironmentVariable("MOMENTO_API_KEY"),
        60
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

function setup()
{
    $cache_client = new CacheClient(
        Laptop::latest(),
        CredentialProvider::fromEnvironmentVariable("MOMENTO_API_KEY"),
        60
    );
    $cache_name = uniqid("php-examples-test-cache-");
    return [
        $cache_client,
        $cache_name,
    ];
}

function teardown($cache_client, $cache_name)
{
    $cache_client->deleteCache($cache_name);
}

function main() {
    [$cache_client, $cache_name] = setup();

    try {
        example_API_CredentialProviderFromEnvVar();

        example_API_InstantiateCacheClient();
        example_API_CreateCache($cache_client, $cache_name);
        example_API_ListCaches($cache_client);

        example_API_Set($cache_client, $cache_name);
        example_API_Get($cache_client, $cache_name);
        example_API_Delete($cache_client, $cache_name);

        example_API_DeleteCache($cache_client, $cache_name);
    } finally {
        teardown($cache_client, $cache_name);
    }
}

main();
