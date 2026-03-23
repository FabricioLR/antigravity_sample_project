#!/bin/bash
set -e

echo "Building test containers..."
docker compose -f docker-compose.test.yml build 

echo "Starting test"
docker compose -f docker-compose.test.yml up -d

echo "Running PHPUnit tests..."
docker exec web_storage_app_test vendor/bin/phpunit --colors=always

echo "Limpando Containers de Teste..."
docker compose -f docker-compose.test.yml down -v
