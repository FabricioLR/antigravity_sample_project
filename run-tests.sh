#!/bin/bash
set -e

echo "Building test containers..."
docker compose -f docker-compose.test.yml build

echo "Starting test database..."
docker compose -f docker-compose.test.yml up -d db_test

echo "Waiting for database to be ready..."
sleep 5

echo "Installing composer dependencies..."
docker compose -f docker-compose.test.yml run --rm app composer install

echo "Running PHPUnit tests..."
docker compose -f docker-compose.test.yml run --rm app vendor/bin/phpunit
