<?php

namespace App;

use App\Config\Database;
use Exception;
use PDO;

class UserManager {
    private PDO $db;

    public function __construct(Database $database) {
        $this->db = $database->getConnection();
    }

    public function addUser(string $username, string $password, string $role = 'user'): int {
        if ($this->getUserByUsername($username)) {
            throw new Exception("Username already exists.");
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, $hash, $role]);
        return (int) $this->db->lastInsertId();
    }

    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool {
        $stmt = $this->db->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user) {
            throw new Exception("User not found.");
        }

        if (!password_verify($currentPassword, $user['password'])) {
            throw new Exception("Senha atual incorreta.");
        }

        $hash = password_hash($newPassword, PASSWORD_DEFAULT);
        $updateStmt = $this->db->prepare("UPDATE users SET password = ? WHERE id = ?");
        return $updateStmt->execute([$hash, $userId]);
    }

    public function removeUser(int $id): bool {
        // Find user first to prevent removing the last admin (logic simplified for now)
        $stmt = $this->db->prepare("DELETE FROM users WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function listUsers(): array {
        $stmt = $this->db->query("SELECT id, username, role, created_at FROM users ORDER BY id ASC");
        return $stmt->fetchAll();
    }

    public function getUserByUsername(string $username): ?array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
    
    public function getUserById(int $id): ?array {
        $stmt = $this->db->prepare("SELECT id, username, role, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();
        return $user ?: null;
    }
}
