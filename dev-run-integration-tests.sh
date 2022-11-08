#!/usr/bin/env bash

set -e
set -x
set -o pipefail

if [ "$TEST_AUTH_TOKEN" == "" ]
then
  echo "Missing required env var TEST_AUTH_TOKEN"
  exit 1
else
    export TEST_AUTH_TOKEN=$TEST_AUTH_TOKEN
fi

if [ "$TEST_CACHE_NAME" == "" ]
then
  echo "Missing required env var TEST_CACHE_NAME"
  exit 1
else
    export TEST_CACHE_NAME=$TEST_CACHE_NAME
fi

export DOCKER_COMMAND="php vendor/phpunit/phpunit/phpunit --configuration phpunit.xml"
docker run -d -e TEST_AUTH_TOKEN="$TEST_AUTH_TOKEN" -e TEST_CACHE_NAME="$TEST_CACHE_NAME" php-test bash -c "$DOCKER_COMMAND"