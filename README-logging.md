<head>
  <meta name="Momento PHP Client Library Documentation" content="PHP client software development kit for Momento Serverless Cache">
</head>
<img src="https://docs.momentohq.com/img/logo.svg" alt="logo" width="400"/>

# Momento PHP SDK Logging facilities

The PHP SDK uses the [PSR-3](https://www.php-fig.org/psr/psr-3/) standard to allow the use of any compatible logging
implementation. This means you can either use your own favorite (PSR-3) logging package for the Momento client's logging
needs or you can use one provided by the SDK.

## SDK-provided logging

The Momento PHP SDK provides a few bare-bones logger factories to provide logging facilities for the client. The
built-in logging factories include:

* Momento\Logging\StderrLoggerFactory - provides a logger that prints all incoming messages to stderr
* Momento\Logging\NullLoggerFactory - provides a logger that swallows all incoming messages
* Momento\Logging\PassthroughLoggerFactory - returns the logger that it is constructed with

Each of these factories provides a `getLogger(?string $name)` method that returns a PSR-3 `LoggerInterface` type:

```php
$loggerFactory = new Momento\Logging\StderrLoggerFactory();
$logger = $loggerFactory->getLogger("main");
$logger->debug("An important message");
```

Note that the `$name` parameter is optional, as not all logging implementations make use of it. The `StderrEchoLogger`
provided by the factory in the example above prefixes each message with the provided name in square brackets.

The `PassthroughLoggerFactory` is a simple way to stash a logging object that is returned anytime `getLogger()` is
called:

```php
$myLogger = new My\Favorite\Logger("channel_name");
$loggerFactory = new \Momento\Logging\PassthroughLoggerFactory($myLogger);
$authProvider = new EnvMomentoTokenProvider("MOMENTO_AUTH_TOKEN");
// All logging internal to the Momento client will use this factory to gain
// access to a reference to $myLogger.
$configuration = Laptop::latest()->withLoggerFactory($loggerFactory);
$client = new SimpleCacheClient($configuration, $authProvider, 600);
```

If you want to construct and return a new logger instance every time `getLogger()` is called, you can provide
your own logging factory implementation. For example, we can implement a `getLogger()` method that configures and
returns a `Monolog` logging instance:

```php
class MonologFactory implements \Momento\Logging\ILoggerFactory
{
    public function getLogger(?string $name): LoggerInterface
    {
        $logger = new Monolog\Logger($name);
        $streamHandler = new Monolog\Handler\StreamHandler("php://stderr", Monolog\Logger::WARNING);
        $formatter = new Monolog\Formatter\LineFormatter("[%channel%] %message%\n");
        $streamHandler->setFormatter($formatter);
        $logger->pushHandler($streamHandler, Monolog\Level::Warning);
        return $logger;
    }
}

$loggerFactory = new MonologFactory();
$log = $loggerFactory->getLogger("channel_name");
```

If desired, your implementation could be extended to allow runtime configuration of stream handlers, formats, and the
other bells and whistles that `Monolog` and other more fully-featured logging solutions offer.

----------------------------------------------------------------------------------------
For more info, visit our website at [https://gomomento.com](https://gomomento.com)!
