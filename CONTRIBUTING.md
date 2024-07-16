# Welcome to the PHP SDK Contributing Guide :wave:

Thank you for taking your time to contribute to our PHP SDK!

This guide will provide you information to start your own development and testing.

Happy coding :dancer:

## Requirements

Check out our PHP SDK documentation for [requirements](https://docs.momentohq.com/sdks/php#requirements)!

## Build Docker Image

You will likely need to run this only once:

```bash
./dev-docker-build.sh
```

## Run Integration Test

```bash
export TEST_AUTH_TOKEN=<YOUR_API_KEY>
./dev-run-integration-tests.sh
```
