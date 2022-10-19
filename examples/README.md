# SDK examples

## Requirements

- A Momento Auth Token is required, you can generate one using
  the [Momento CLI](https://github.com/momentohq/momento-cli)
- At least PHP 7
- The grpc PHP extension. See the [gRPC docs](https://github.com/grpc/grpc/blob/v1.46.3/src/php/README.md) section on
  installing the extension.
- [Composer](https://getcomposer.org/doc/00-intro.md)

## Running the example

Run `composer update` to install the prerequisites.

Set required environment variables:

```bash
export MOMENTO_AUTH_TOKEN=<YOUR_AUTH_TOKEN>
export CACHE_NAME=<YOUR_CACHE_NAME>
```

To run the simple ste/get example

```bash
php exmaple.php
```

To run the list example

```bash
php list-exmaple.php
```

To run the dictionary example

```bash
php dictionary-exmaple.php
```

## Using the SDK in your project

Add the repository and dependency to your project's `composer.json`:

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
