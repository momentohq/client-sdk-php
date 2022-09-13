# client-sdk-php

:warning: Experimental SDK :warning:

PHP SDK for Momento is experimental and under active development. There could be non-backward compatible changes or
removal in the future. Please be aware that you may need to update your source code with the current version of the SDK
when its version gets upgraded.
---

<br />
PHP SDK for Momento, a serverless cache that automatically scales without any of the operational overhead required by
traditional caching solutions.

<br/>

# Getting Started :running:

## Requirements

- A Momento Auth Token is required, you can generate one using the [Momento CLI](https://github.com/momentohq/momento-cli)
- At least PHP 7
- The grpc PHP extension. See the [gRPC docs](https://github.com/grpc/grpc/blob/v1.46.3/src/php/README.md) section on installing the extension.

## Using Momento

Check out full working code in [the examples directory](examples/) of this repository!

### Import into your project

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



