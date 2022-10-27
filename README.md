<head>
  <meta name="Momento PHP Client Library Documentation" content="PHP client software development kit for Momento Serverless Cache">
</head>
<img src="https://docs.momentohq.com/img/logo.svg" alt="logo" width="400"/>

[![project status](https://momentohq.github.io/standards-and-practices/badges/project-status-official.svg)](https://github.com/momentohq/standards-and-practices/blob/main/docs/momento-on-github.md)
[![project stability](https://momentohq.github.io/standards-and-practices/badges/project-stability-alpha.svg)](https://github.com/momentohq/standards-and-practices/blob/main/docs/momento-on-github.md) 

# Momento PHP Client Library


:warning: Alpha SDK :warning:

This is an official Momento SDK, but the API is in an alpha stage and may be subject to backward-incompatible
changes.  For more info, click on the alpha badge above.


PHP client SDK for Momento Serverless Cache: a fast, simple, pay-as-you-go caching solution without
any of the operational overhead required by traditional caching solutions!



Japanese: [日本語](README.ja.md)

## Getting Started :running:

### Requirements

- A Momento Auth Token is required, you can generate one using the [Momento CLI](https://github.com/momentohq/momento-cli)
- At least PHP 8.0
- The grpc PHP extension. See the [gRPC docs](https://github.com/grpc/grpc/blob/v1.46.3/src/php/README.md) section on installing the extension.

**IDE Notes**: You'll most likely want to use an IDE that supports PHP development, such as [PhpStorm](https://www.jetbrains.com/phpstorm/) or [Microsoft Visual Studio Code](https://code.visualstudio.com/).

### Examples

Check out full working code in [the examples directory](examples/) of this repository!

### Installation

Install composer [as described on the composer website](https://getcomposer.org/doc/00-intro.md).

Add our repository to your `composer.json` file and our SDK as a dependency:

```json
{
  "repositories": [
    {
      "type": "vcs",
      "url": "https://github.com/momentohq/client-sdk-php"
    }
  ],
  "require": {
    "momentohq/client-sdk-php": "dev-main"
  }
}
```

Run `composer update` to install the necessary prerequisites.

### Usage

Check out full working code in [the examples directory](examples/) of this repository!

Here is an example to get you started:

```php
<?php

require "vendor/autoload.php";

use Momento\Auth\EnvMomentoTokenProvider;
use Momento\Cache\SimpleCacheClient;

$MOMENTO_AUTH_TOKEN = getenv("MOMENTO_AUTH_TOKEN");
$CACHE_NAME = getenv("CACHE_NAME");
$ITEM_DEFAULT_TTL_SECONDS = 60;
$KEY = "MyKey";
$VALUE = "MyValue";

function printBanner(string $message): void
{
    $line = "******************************************************************";
    print "$line\n$message\n$line\n";
}

printBanner("*                      Momento Example Start                     *");
// Setup
$authProvider = new EnvMomentoTokenProvider("MOMENTO_AUTH_TOKEN");
$client = new SimpleCacheClient($authProvider, $ITEM_DEFAULT_TTL_SECONDS);

// Ensure test cache exists
$response = $client->createCache($CACHE_NAME);
if ($response->asSuccess()) {
    print "Created cache " . $CACHE_NAME . "\n";
} elseif ($response->asError()) {
    print "Error creating cache: " . $response->asError()->message() . "\n";
    exit;
} elseif ($response->asAlreadyExists()) {
    print "Cache " . $CACHE_NAME . " already exists.\n";
}

// List cache
$response = $client->listCaches();
if ($response->asSuccess()) {
    while (true) {
        print "SUCCESS: List caches: \n";
        foreach ($response->asSuccess()->caches() as $cache) {
            $cacheName = $cache->name();
            print "$cacheName\n";
        }
        $nextToken = $response->asSuccess()->nextToken();
        if (!$nextToken) {
            break;
        }
        $response = $client->listCaches($nextToken);
    }
    print "\n";
} elseif ($response->asError()) {
    print "Error listing cache: " . $response->asError()->message() . "\n";
    exit;
}

// Set
print "Setting key: $KEY to value: $VALUE\n";
$response = $client->set($CACHE_NAME, $KEY, $VALUE);
if ($response->asSuccess()) {
    print "SUCCESS: - Set key: " . $KEY . " value: " . $VALUE . " cache: " . $CACHE_NAME . "\n";
} elseif ($response->asError()) {
    print "Error setting key: " . $response->asError()->message() . "\n";
    exit;
}

// Get
print "Getting value for key: $KEY\n";
$response = $client->get($CACHE_NAME, $KEY);
if ($response->asHit()) {
    print "SUCCESS: - Get key: " . $KEY . " value: " . $response->asHit()->value() . " cache: " . $CACHE_NAME . "\n";
} elseif ($response->asMiss()) {
    print "Get operation was a MISS\n";
} elseif ($response->asError()) {
    print "Error getting cache: " . $response->asError()->message() . "\n";
    exit;
}

printBanner("*                       Momento Example End                      *");

```

### Error Handling

Coming soon!

### Tuning

Coming soon!

----------------------------------------------------------------------------------------
For more info, visit our website at [https://gomomento.com](https://gomomento.com)!
