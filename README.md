<head>
  <meta name="Momento Client Library Documentation for PHP" content="Momento client software development kit for PHP">
</head>
<img src="https://docs.momentohq.com/img/momento-logo-forest.svg" alt="logo" width="400"/>

[![project status](https://momentohq.github.io/standards-and-practices/badges/project-status-official.svg)](https://github.com/momentohq/standards-and-practices/blob/main/docs/momento-on-github.md)
[![project stability](https://momentohq.github.io/standards-and-practices/badges/project-stability-stable.svg)](https://github.com/momentohq/standards-and-practices/blob/main/docs/momento-on-github.md)

# Momento Client Library for PHP

Momento Cache is a fast, simple, pay-as-you-go caching solution without any of the operational overhead
required by traditional caching solutions.  This repo contains the source code for the Momento client library for PHP.

To get started with Momento you will need a Momento Auth Token. You can get one from the [Momento Console](https://console.gomomento.com).

* Website: [https://www.gomomento.com/](https://www.gomomento.com/)
* Momento Documentation: [https://docs.momentohq.com/](https://docs.momentohq.com/)
* Getting Started: [https://docs.momentohq.com/getting-started](https://docs.momentohq.com/getting-started)
* Momento SDK Documentation for PHP: [https://docs.momentohq.com/sdks/php](https://docs.momentohq.com/sdks/php)
* Discuss: [Momento Discord](https://discord.gg/3HkAKjUZGq)

Japanese: [日本語](README.ja.md)

## Packages

The Momento SDK for PHP package is available on packagist.org: [client-sdk-php](https://packagist.org/packages/momentohq/client-sdk-php)

## Usage

```php
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

```

## Getting Started and Documentation

To get started with Momento you will need:

- A Momento API key is required, you can generate one using the [Momento Console](https://console.gomomento.com/)
- A Momento service endpoint is required. You can find a [list of them here](https://docs.momentohq.com/platform/regions)

Documentation is available on the [Momento Docs website](https://docs.momentohq.com).

## Examples

Working example projects, with all required build configuration files, are available
[in the examples directory](./examples/).

## Developing

If you are interested in contributing to the SDK, please see the [CONTRIBUTING](./CONTRIBUTING.md) docs.

## Attributions

This product includes PHP software, freely available from <http://www.php.net/software/>

----------------------------------------------------------------------------------------
For more info, visit our website at [https://gomomento.com](https://gomomento.com)!
