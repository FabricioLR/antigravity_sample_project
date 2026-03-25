<?php

namespace App;

use App\Config\Database;
use PDO;
use Exception;

class ShareManager {
    private PDO $pdo;

    public function __construct(Database $db) {
        $this->pdo = $db->getConnection();
    }

    /**
     * Creates a new share link for a file.
     * 
     * @param int $userId The ID of the user who owns the file.
     * @param string $filename The name of the file to share.
     * @param string $duration The duration of the share ('1h', '1d', or 'forever').
     * @return string The generated UUID for the share link.
     */
    public function createShare(int $userId, string $filename, string $duration): string {
        $uuid = $this->generateUuidV4();
        $expiresAt = null;

        if ($duration === '1h') {
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));
        } elseif ($duration === '1d') {
            $expiresAt = date('Y-m-d H:i:s', strtotime('+1 day'));
        }

        $stmt = $this->pdo->prepare("
            INSERT INTO shared_files (uuid, user_id, filename, expires_at)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$uuid, $userId, $filename, $expiresAt]);

        return $uuid;
    }

    /**
     * Retrieves share information if valid and not expired.
     * 
     * @param string $uuid The UUID of the share.
     * @return array|null The share data or null if not found/expired.
     */
    public function getShare(string $uuid): ?array {
        $stmt = $this->pdo->prepare("
            SELECT * FROM shared_files 
            WHERE uuid = ? AND (expires_at IS NULL OR expires_at > CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$uuid]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Deletes all expired shares from the database.
     */
    public function cleanupExpiredShares(): void {
        $this->pdo->exec("DELETE FROM shared_files WHERE expires_at < CURRENT_TIMESTAMP");
    }

    /**
     * Generates a UUID v4.
     */
    private function generateUuidV4(): string {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40); // version 4
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80); // variant is 10
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}
