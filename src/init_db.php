<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Config\Database;
use App\UserManager;

try {
    $db = new Database();
    $pdo = $db->getConnection();

    echo "Creating users table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            username VARCHAR(255) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            role VARCHAR(50) DEFAULT 'user',
            must_change_password BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    try {
        $pdo->exec("ALTER TABLE users ADD COLUMN must_change_password BOOLEAN DEFAULT TRUE");
    } catch (Exception $e) {
        // Ignora se a coluna já existe
    }

    echo "Creating shared_files table...\n";
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS shared_files (
            uuid UUID PRIMARY KEY,
            user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
            filename TEXT NOT NULL,
            expires_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $userManager = new UserManager($db);
    
    // Check if admin exists
    $admin = $userManager->getUserByUsername('admin');
    if (!$admin) {
        echo "Creating default admin user...\n";
        $userManager->addUser('admin', 'admin', 'admin');
        echo "Default admin created (admin:admin). Change password immediately!\n";
    } else {
        echo "Admin user already exists.\n";
    }

    echo "Database initialized successfully.\n";

} catch (Exception $e) {
    die("Initialization failed: " . $e->getMessage() . "\n");
}
