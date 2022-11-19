<img src="https://docs.momentohq.com/img/logo.svg" alt="logo" width="400"/>

# Welcome to client-sdk-php contributing guide :wave:

Thank you for taking your time to contribute to our PHP SDK!
<br/>
This guide will provide you information to start your own development and testing.
<br/>
Happy coding :dancer:
<br/>

## Requirements

Check out our SDK [requirements](https://github.com/momentohq/client-sdk-php#requirements)!

## Build Docker Image

You will likely need to run this only once:

```bash
./dev-docker-build.sh
```

## Run Integration Test

```bash
export TEST_AUTH_TOKEN=<YOUR_AUTH_TOKEN>
export TEXT_CACHE_NAME=<YOUR_CACHE_NAME>
./dev-run-integration-tests.sh
```
