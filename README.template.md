{{ ossHeader }}

Japanese: [日本語](README.ja.md)

## Getting Started :running:

### Requirements

- A Momento Auth Token is required, you can generate one using
  the [Momento CLI](https://github.com/momentohq/momento-cli)
- At least PHP 8.0
- The grpc PHP extension. See the [gRPC docs](https://github.com/grpc/grpc/blob/v1.46.3/src/php/README.md) section on
  installing the extension.

**IDE Notes**: You'll most likely want to use an IDE that supports PHP development, such
as [PhpStorm](https://www.jetbrains.com/phpstorm/) or [Microsoft Visual Studio Code](https://code.visualstudio.com/).

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
    "momentohq/client-sdk-php": "0.2.0"
  }
}
```

Run `composer update` to install the necessary prerequisites.

### Usage

Check out full working code in [the examples directory](examples/) of this repository!

Here is an example to get you started:

```php
{{ usageExampleCode }}
```

### Error Handling

Coming soon!

### Tuning

Coming soon!

### Running Tests

```bash
export DOCKER_COMMAND="php vendor/phpunit/phpunit/phpunit --configuration phpunit.xml"
docker build --tag php-test --build-arg token=<YOUR_AUTH_TOKEN> .
docker run -d -it php-test bash -c $DOCKER_COMMAND
```

{{ ossFooter }}
