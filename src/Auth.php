<?php

namespace App;

use App\Config\Database;
use PDO;

class Auth {
    private PDO $db;

    public function __construct(Database $database) {
        $this->db = $database->getConnection();
    }

    public function login(string $username, string $password): bool {
        $stmt = $this->db->prepare("SELECT id, username, password, role, must_change_password FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['must_change_password'] = (bool) $user['must_change_password'];
            return true;
        }

        return false;
    }

    public function logout(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        session_unset();
        session_destroy();
    }

    public function isLoggedIn(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['user_id']);
    }

    public function isAdmin(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    public function mustChangePassword(): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['must_change_password']) && $_SESSION['must_change_password'];
    }

    public function getCurrentUserId(): ?int {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return $_SESSION['user_id'] ?? null;
    }
}
