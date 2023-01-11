#!/usr/bin/env bash

set -e
set -x
set -o pipefail

DOCKER_IMAGE_NAME=momento-php-dev

DOCKER_BASE_COMMAND="docker run -it -v$(pwd):/examples -w=/examples"
$DOCKER_BASE_COMMAND $DOCKER_IMAGE_NAME composer install
$DOCKER_BASE_COMMAND $DOCKER_IMAGE_NAME bash

