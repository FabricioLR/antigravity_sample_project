<?php

namespace App\Config;

use PDO;
use PDOException;

class Database {
    private ?PDO $pdo = null;

    public function getConnection(): PDO {
        if ($this->pdo === null) {
            $host = getenv('DB_HOST') ?: 'localhost';
            $port = getenv('DB_PORT') ?: '5432';
            $dbName = getenv('DB_DATABASE') ?: 'web_storage';
            $username = getenv('DB_USERNAME') ?: 'admin';
            $password = getenv('DB_PASSWORD') ?: 'secret';

            $dsn = "pgsql:host=$host;port=$port;dbname=$dbName";
            try {
                $this->pdo = new PDO($dsn, $username, $password);
                $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
                $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            } catch (PDOException $e) {
                die("Database connection failed: " . $e->getMessage());
            }
        }
        return $this->pdo;
    }
}
