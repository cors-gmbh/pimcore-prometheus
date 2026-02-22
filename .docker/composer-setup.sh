#!/bin/bash

export $(grep -E '^(DOCKER_ALPINE_VERSION|DOCKER_PHP_VERSION|DOCKER_BASE_IMAGE)=' .env | sed 's/\r//g' | xargs)

echo "DOCKER_ALPINE_VERSION: $DOCKER_ALPINE_VERSION"
echo "DOCKER_PHP_VERSION: $DOCKER_PHP_VERSION"
echo "DOCKER_BASE_IMAGE: $DOCKER_BASE_IMAGE"

docker run --rm --env COMPOSER_AUTH="$COMPOSER_AUTH" --volume "$(pwd)":/var/www/html:cached ghcr.io/cors-gmbh/pimcore-docker/php-cli:${DOCKER_PHP_VERSION}-alpine${DOCKER_ALPINE_VERSION}-${DOCKER_BASE_IMAGE} composer install --no-scripts --no-interaction
