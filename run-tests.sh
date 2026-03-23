#!/bin/bash
set -e

DC="docker compose"
if ! docker compose version >/dev/null 2>&1; then
    DC="docker-compose"
fi

echo "Building test containers..."
$DC -f docker-compose.test.yml build 

echo "Starting test"
$DC -f docker-compose.test.yml up -d

echo "Running PHPUnit tests..."
docker exec web_storage_app_test vendor/bin/phpunit --colors=always

echo "Limpando Containers de Teste..."
$DC -f docker-compose.test.yml down -v
