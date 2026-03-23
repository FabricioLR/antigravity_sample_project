#!/bin/bash
set -e

echo "Building test containers (Isso pode demorar alguns minutos e ser silencioso pois o C++ está compilando os drivers do PHP 8.4 pela primeira vez)..."
docker compose -f docker-compose.test.yml build -q

echo "Starting test database..."
docker compose -f docker-compose.test.yml up -d db_test

echo "Waiting for database to be ready..."
sleep 5

echo "Installing composer dependencies..."
docker compose -f docker-compose.test.yml run --rm app composer install -q

echo "Running PHPUnit tests..."
docker compose -f docker-compose.test.yml run --rm app vendor/bin/phpunit --colors=always

echo "Limpando Containers de Teste..."
docker compose -f docker-compose.test.yml down -v
