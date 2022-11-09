# SDK examples

## Requirements

- A Momento Auth Token is required, you can generate one using
  the [Momento CLI](https://github.com/momentohq/momento-cli)
- At least PHP 8.0
  The gRPC PHP extension. See the [gRPC docs](https://github.com/grpc/grpc/blob/v1.46.3/src/php/README.md) section on
- [Composer](https://getcomposer.org/doc/00-intro.md)

## Running the examples

The Docker way:

```bash
# You can reference php commands to run various examples below.
export DOCKER_COMMAND="php example.php"
docker build --tag php-example --build-arg token=<YOUR_AUTH_TOKEN> --build-arg cache_name=<YOUR_CACHE_NAME> ../
docker run -d -w /app/examples -it php-test bash -c $DOCKER_COMMAND
```

Run `composer update` to install the prerequisites.

Set required environment variables:

```bash
export MOMENTO_AUTH_TOKEN=<YOUR_AUTH_TOKEN>
export CACHE_NAME=<YOUR_CACHE_NAME>
```

To run the simple set/get example:

```bash
php example.php
```

To run the list example:

```bash
php list-example.php
```

To run the dictionary example:

```bash
php dictionary-example.php
```

To run the PSR-16 example:

```bash
php psr16-example.php
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
    "momentohq/client-sdk-php": "0.2.0"
  }
}
```
