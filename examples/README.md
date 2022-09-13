# SDK examples

## Requirements

- A Momento Auth Token is required, you can generate one using the [Momento CLI](https://github.com/momentohq/momento-cli)
- At least PHP 7
- The grpc PHP extension. See the [gRPC docs](https://github.com/grpc/grpc/blob/v1.46.3/src/php/README.md) section on installing the extension.
- [Composer](https://getcomposer.org/doc/00-intro.md)

## Running the example

Run `composer update` to install the prerequisites.

`MOMENTO_AUTH_TOKEN=<YOUR AUTH TOKEN> php example.php`

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
