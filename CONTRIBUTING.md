# Welcome to the Momento SDK for PHP Contributing Guide :wave:

Thank you for taking your time to contribute to our SDK!

This guide will provide you information to start your own development and testing.

Happy coding :dancer:

## Requirements

Check out our SDK documentation for [requirements](https://docs.momentohq.com/sdks/php#requirements)!

## Setup
There are two ways to setup your development environment:


## Option 1: Build Docker Image

You will likely need to run this only once:

```bash
./dev-docker-build.sh
```

### Run Integration Tests

```bash
export MOMENTO_API_KEY=<YOUR_API_KEY>
export MOMENTO_ENDPOINT=<endpoint>
./dev-run-integration-tests.sh
```

## Option 2: Running a Dev Container in IntelliJ IDEA
- Open the `.devcontainer/devcontainer.json` file (present in the root of this repository) in IntelliJ IDEA.
- In the left gutter, click and select `Create Dev Container and Mount Sources`.
- This will create and launch a development container with all necessary dependencies pre-installed.

You can now develop and test the SDK in the container.

## Option 3: Running a Dev Container in Visual Studio Code
- Open the `.devcontainer/devcontainer.json` file (present in the root of this repository) in Visual Studio Code.
- Click on the `Reopen in Container` button in the bottom right corner.
- This will create and launch a development container with all necessary dependencies pre-installed.

You can now develop and test the SDK in the container.
