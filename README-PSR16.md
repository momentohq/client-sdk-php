# Momento PSR-16 Client Library

The Momento PSR-16 client library, `Psr16SimpleCache`, implements the [PHP PSR-16 common interface for caching
libraries](https://www.php-fig.org/psr/psr-16/) for the Momento Serverless Cache. Note that the client library
is under development and may be subject to backward-incompatible changes.

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

Check out the
full [PSR-16 library example](https://github.com/momentohq/client-sdk-php/blob/main/examples/psr16-example.php) in the
[examples directory](https://github.com/momentohq/client-sdk-php/blob/main/examples) of this repository!

### Implementation Notes

- Please note that this library is under active development and may be subject to backward-incompatible changes.
- The `clear()` function defined in the PSR-16 specification is currently unimplemented in this client
  library and throws a `Momento\Cache\Errors\NotImplementedException` if called. It is coming soon, though, so stay
  tuned!
- The `getMultiple()`, `setMultiple()`, and `deleteMultiple()` functionality is currently implemented client-side and
  may exhibit slower performance than expected. These methods will be replaced with calls to server-side implementations
  in the near future.
