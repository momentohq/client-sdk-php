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

DOCKER_IMAGE_NAME=momento-php-dev

DOCKER_BASE_COMMAND="docker run -it -v$(pwd):/app -w=/app"
$DOCKER_BASE_COMMAND $DOCKER_IMAGE_NAME composer install
$DOCKER_BASE_COMMAND -e TEST_AUTH_TOKEN="$TEST_AUTH_TOKEN" \
	$DOCKER_IMAGE_NAME php vendor/phpunit/phpunit/phpunit --configuration phpunit.xml
