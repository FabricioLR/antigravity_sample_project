<?php

// Load Composer's autoloader
require_once dirname(__DIR__) . '/vendor/autoload.php';

// Mock session for tests if needed or set test DB env variables
putenv('DB_HOST=db_test');
putenv('DB_PORT=5432');
putenv('DB_DATABASE=web_storage_test');
putenv('DB_USERNAME=test_user');
putenv('DB_PASSWORD=test_pass');
