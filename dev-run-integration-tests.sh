#!/usr/bin/env bash

set -e
set -x
set -o pipefail

if [ "$MOMENTO_API_KEY" == "" ]
then
  echo "Missing required env var MOMENTO_API_KEY"
  exit 1
else
    export MOMENTO_API_KEY=$MOMENTO_API_KEY
fi

DOCKER_IMAGE_NAME=momento-php-dev

DOCKER_BASE_COMMAND="docker run -it -v$(pwd):/app -w=/app"
$DOCKER_BASE_COMMAND $DOCKER_IMAGE_NAME composer install
$DOCKER_BASE_COMMAND -e MOMENTO_API_KEY="$MOMENTO_API_KEY" \
	$DOCKER_IMAGE_NAME php vendor/phpunit/phpunit/phpunit --configuration phpunit.xml
