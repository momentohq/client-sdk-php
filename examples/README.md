# SDK examples

## Requirements

- A Momento API key is required, you can generate one using the [Momento Console](https://console.gomomento.com/)

### Running via local PHP

You will need:

- At least PHP 8.0
- The gRPC PHP extension. See the [gRPC docs](https://github.com/grpc/grpc/blob/master/src/php/README.md) section on installing the extension.
- The protobuf C extension. See the [protobuf C extension docs](https://developers.google.com/google-ads/api/docs/client-libs/php/protobuf#c_implementation) for installation instructions.
- [Composer](https://getcomposer.org/doc/00-intro.md)

Run `composer update` to install the prerequisites.

### Running via docker

The Docker way:

- Make sure you have run the `dev-docker-build.sh` script in the parent directory, to build the Momento PHP development
  image.
- Run the `./dev-php-docker-shell.sh` script to get a bash shell in a docker container that has all of the PHP dependencies
  necessary to run the examples.  You may then run any of the commands below inside of the shell.

## Running the examples

Set required environment variables:

```bash
export MOMENTO_AUTH_TOKEN=<YOUR_AUTH_TOKEN>
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
  "require": {
    "momentohq/client-sdk-php": "^1.7.1"
  }
}
```

## Running the load generator examples

This repo includes a couple very basic load generators to allow you to experiment
with performance in your environment based on different configurations.

The `loadgen.php` CLI script uses a payload of configurable size to perform a configurable
number of set/get operation pairs. After execution, it outputs raw nanosecond timing
results for each type of operation in `gets.txt` and `sets.txt` output files.

```bash
# Run CLI load generator
MOMENTO_API_KEY=<YOUR API KEY> php loadgen.php
```

The `index-loadgen.php` script is intended to be served by a web server to experiment
with performance in a web environment. For example, you might experiment with different
preforking strategies to determine their effect on connection and throughput performance.
The loadgen index page outputs nanosecond request timing data in `gets.txt` and `sets.txt`
as well as data for the time it takes to instantiate the SimpleCacheClient on each page
request in `startups.txt`. To simulate a typical web session, 5 set/get pairs are executed
on each page load.

```bash
for i in {1..10000}; do echo `curl -s http://localhost/client-sdk-php/examples/index-loadgen.php`; done
```

You will of course need to place the index-loadgen.php file (and its composer dependencies)
where it can be served by your web server. You will also need to make your Momento authentication
token available to PHP as an environment variable using the
[SetEnv](https://httpd.apache.org/docs/2.4/mod/mod_env.html) directive in Apache or your web
server's equivalent.

Since performance will be impacted by network latency, you'll get the best
results if you run on a cloud VM in the same region as your Momento cache.

If you have questions or need help experimenting further, please reach out to us!
