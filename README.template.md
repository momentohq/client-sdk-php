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

In addition to the primary Momento `SimpleCacheClient` library used in most of the examples, a PHP PSR-16
implementation and corresponding example are also included in the SDK. See the PSR-16 client [README](README-PSR16.md)
and [example](https://github.com/momentohq/client-sdk-php/blob/psr16-library/examples/psr16-example.php) for more
details.

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

For more details about `SimpleCacheClient` logging, please take a look at [README-logging.md](README-logging.md).

### Error Handling

Errors that occur in calls to `SimpleCacheClient` methods are surfaced to developers as part of the return values of
the calls, as opposed to by throwing exceptions. This makes them more visible, and allows your IDE to be more
helpful in ensuring that you've handled the ones you care about. (For more on our philosophy about this, see our
blog post on why [Exceptions are bugs](https://www.gomomento.com/blog/exceptions-are-bugs). And send us any
feedback you have!)

The preferred way of interpreting the return values from `SimpleCacheClient` methods is
using `as` methods to match and handle the specific response type. Here's a quick example:

```php
$getResponse = $client->get($CACHE_NAME, $KEY);
if ($hitResponse = $getResponse->asHit())
{
    print "Looked up value: {$hitResponse->value()}\n");
} else {
    // you can handle other cases via pattern matching in `else if` blocks, or a default case
    // via the `else` block.  For each return value your IDE should be able to give you code
    // completion indicating the other possible "as" methods; in this case, `$getResponse->asMiss()`
    // and `$getResponse->asError()`.
}
```

Using this approach, you get a type-safe `hitResponse` object in the case of a cache hit. But if the cache read
results in a Miss or an error, you'll also get a type-safe object that you can use to get more info about what happened.

In cases where you get an error response, it will always include an `ErrorCode` that you can use to check
the error type:

```php
$getResponse = $client->get($CACHE_NAME, $KEY);
if ($errorResponse = $getResponse->asError())
{
    if ($errorResponse->errorCode() == MomentoErrorCode::TIMEOUT_ERROR) {
       // this would represent a client-side timeout, and you could fall back to your original data source
    }
}
```

Note that, outside of `SimpleCacheClient` responses, exceptions can occur and should be handled as usual. For example,
trying to instantiate a `SimpleCacheClient` with an invalid authentication token will result in an
`IllegalArgumentException` being thrown.

### Tuning

Coming soon!

{{ ossFooter }}
