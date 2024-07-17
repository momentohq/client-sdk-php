<?php
require "vendor/autoload.php";
use Momento\Auth\CredentialProvider;
use Momento\Config\Configurations\Storage\Laptop;
use Momento\Storage\PreviewStorageClient;

$storageClient = new PreviewStorageClient(
    Laptop::latest(),
    CredentialProvider::fromEnvironmentVariable("MOMENTO_API_KEY")
);
