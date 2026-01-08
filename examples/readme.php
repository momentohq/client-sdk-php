<?php
require "vendor/autoload.php";
use Momento\Auth\CredentialProvider;
use Momento\Cache\CacheClient;
use Momento\Config\Configurations\Laptop;

$client = new CacheClient(
    Laptop::latest(), CredentialProvider::fromEnvironmentVariablesV2(), 60
);
$client->createCache("cache");
$client->set("cache", "myKey", "myValue");
$response = $client->get("cache", "myKey");
if ($hit = $response->asHit()) {
    print("Got value: {$hit->valueString()}\n");
}
