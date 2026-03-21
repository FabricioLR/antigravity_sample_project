#!/bin/bash
set -e

DC="docker compose"
if ! docker compose version >/dev/null 2>&1; then
    DC="docker-compose"
fi

echo "Building test containers (Isso pode demorar alguns minutos e ser silencioso pois o C++ está compilando os drivers do PHP 8.4 pela primeira vez)..."
$DC -f docker-compose.test.yml build -q

echo "Starting test database..."
$DC -f docker-compose.test.yml up -d db_test

echo "Waiting for database to be ready..."
sleep 15

echo "Installing composer dependencies..."
$DC -f docker-compose.test.yml run --rm app composer install -q

echo "Running PHPUnit tests..."
$DC -f docker-compose.test.yml run --rm app vendor/bin/phpunit --colors=always

echo "Limpando Containers de Teste..."
$DC -f docker-compose.test.yml down -v
