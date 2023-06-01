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
        CredentialProvider::fromEnvironmentVariable("MOMENTO_AUTH_TOKEN"),
        60
    );
}

function example_API_CredentialProviderFromEnvVar()
{
    CredentialProvider::fromEnvironmentVariable("MOMENTO_AUTH_TOKEN");
}

function example_API_CreateCache(CacheClient $cache_client)
{
    $create_cache_response = $cache_client->createCache("test-cache");
    if ($create_cache_response->asSuccess()) {
        print("Cache 'test-cache' created\n");
    } elseif ($create_cache_response->asAlreadyExists()) {
        print("Cache 'test-cache' already exists\n");
    } elseif ($err = $create_cache_response->asError()) {
        print("An error occurred while attempting to create 'test-cache': {$err->errorCode()} - {$err->message()}\n");
    }
}

function example_API_DeleteCache(CacheClient $cache_client)
{
    $delete_cache_response = $cache_client->deleteCache('test-cache');
    if ($err = $delete_cache_response->asError()) {
        print("An error occurred while attempting to delete 'test-cache': {$err->errorCode()} - {$err->message()}\n");
    } else {
        print("Cache 'test-cache' deleted\n");
    }
}

function example_API_ListCaches(CacheClient $cache_client)
{
    $list_caches_response = $cache_client->listCaches();
    if ($caches = $list_caches_response->asSuccess()) {
        print("Found caches:\n");
        foreach ($caches as $cache) {
            $cache_name = $cache->name();
            print("- $cache_name\n");
        }
    } elseif ($err = $list_caches_response->asError()) {
        print("An error occurred while attempting to list caches: {$err->errorCode()} - {$err->message()}\n");
    }
}

function example_API_Set(CacheClient $cache_client) {
    $set_response = $cache_client->set("test-cache", "test-key", "test-value");
    if ($set_response->asSuccess()) {
        print("Key 'test-key' stored successfully\n");
    } elseif ($err = $set_response->asError()) {
        print("An error occurred while attempting to store 'test-key': {$err->errorCode()} - {$err->message()}\n");
    }
}

function example_API_Get(CacheClient $cache_client)
{
    $get_response = $cache_client->get("test-cache", "test-key");
    if ($hit = $get_response->asHit()) {
        print("Retrieved value for key 'test-key': {$hit->valueString()}\n");
    } elseif ($get_response->asMiss()) {
        print("Key 'test-key' was not found in cache 'test-cache'\n");
    } elseif ($err = $get_response->asError()) {
        print("An error occurred while attempting to get key 'test-key' from cache 'test-cache': {$err->errorCode()} - {$err->message()}\n");
    }
}

function example_API_Delete(CacheClient $cache_client) {
    $delete_response = $cache_client->delete("test-cache", "test-key");
    if ($delete_response->asSuccess()) {
        print("Key 'test-key' deleted successfully\n");
    } elseif ($err = $delete_response->asError()) {
        print("An error occurred while attempting to delete key 'test-key' from cache 'test-cache': {$err->errorCode()} - {$err->message()}\n");
    }
}

function main() {
    example_API_CredentialProviderFromEnvVar();

    example_API_InstantiateCacheClient();
    $cache_client = new CacheClient(
        Laptop::latest(),
        CredentialProvider::fromEnvironmentVariable("MOMENTO_AUTH_TOKEN"),
        60
    );
    example_API_CreateCache($cache_client);
    example_API_DeleteCache($cache_client);
    example_API_CreateCache($cache_client);
    example_API_ListCaches($cache_client);

    example_API_Set($cache_client);
    example_API_Get($cache_client);
    example_API_Delete($cache_client);

    example_API_DeleteCache($cache_client);
}

main();
