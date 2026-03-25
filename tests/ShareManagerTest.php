<?php

namespace Tests;

use App\Config\Database;
use App\ShareManager;
use PHPUnit\Framework\TestCase;

class ShareManagerTest extends TestCase {
    private Database $db;
    private ShareManager $shareManager;

    protected function setUp(): void {
        $this->db = new Database();
        $this->shareManager = new ShareManager($this->db);
        
        $pdo = $this->db->getConnection();
        
        // Rebuild tables to ensure isolation
        $pdo->exec("DROP TABLE IF EXISTS shared_files CASCADE;");
        $pdo->exec("DROP TABLE IF EXISTS users CASCADE;");
        
        $pdo->exec("
            CREATE TABLE users (
                id SERIAL PRIMARY KEY,
                username VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) DEFAULT 'user',
                must_change_password BOOLEAN DEFAULT TRUE,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");
        
        $pdo->exec("
            CREATE TABLE shared_files (
                uuid UUID PRIMARY KEY,
                user_id INTEGER REFERENCES users(id) ON DELETE CASCADE,
                filename TEXT NOT NULL,
                expires_at TIMESTAMP NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        // Create a default user for tests
        $pdo->exec("INSERT INTO users (id, username, password) VALUES (1, 'admin', 'admin')");
    }

    public function testCreateShareForever() {
        $uuid = $this->shareManager->createShare(1, 'test_forever.txt', 'forever');
        // UUID v4 regex
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);

        $share = $this->shareManager->getShare($uuid);
        $this->assertNotNull($share);
        $this->assertEquals('test_forever.txt', $share['filename']);
        $this->assertNull($share['expires_at']);
    }

    public function testCreateShareWithExpiry() {
        $uuid = $this->shareManager->createShare(1, 'test_1h.txt', '1h');
        $share = $this->shareManager->getShare($uuid);
        
        $this->assertNotNull($share);
        $this->assertNotNull($share['expires_at']);
        
        // Check if expires_at is roughly 1 hour from now
        $expiryTime = strtotime($share['expires_at']);
        $expectedTime = strtotime('+1 hour');
        $this->assertLessThan(5, abs($expiryTime - $expectedTime));
    }

    public function testGetExpiredShareReturnsNull() {
        $uuid = $this->shareManager->createShare(1, 'expired.txt', '1h');
        
        // Manually set expiry to the past
        $stmt = $this->db->getConnection()->prepare("UPDATE shared_files SET expires_at = '2000-01-01 00:00:00' WHERE uuid = ?");
        $stmt->execute([$uuid]);

        $share = $this->shareManager->getShare($uuid);
        $this->assertNull($share);
    }

    public function testCleanupExpiredShares() {
        // Create one active and one expired share
        $uuidActive = $this->shareManager->createShare(1, 'active.txt', 'forever');
        $uuidExpired = $this->shareManager->createShare(1, 'to_cleanup.txt', '1h');
        
        $stmt = $this->db->getConnection()->prepare("UPDATE shared_files SET expires_at = '2000-01-01 00:00:00' WHERE uuid = ?");
        $stmt->execute([$uuidExpired]);

        $this->shareManager->cleanupExpiredShares();

        // Check database directly
        $stmt = $this->db->getConnection()->prepare("SELECT COUNT(*) FROM shared_files WHERE uuid = ?");
        
        $stmt->execute([$uuidActive]);
        $this->assertEquals(1, $stmt->fetchColumn());

        $stmt->execute([$uuidExpired]);
        $this->assertEquals(0, $stmt->fetchColumn());
    }

    public function testDeleteUserDeletesShares() {
        // This tests the ON DELETE CASCADE constraint
        $pdo = $this->db->getConnection();
        // Ensure user 999 exists for test
        $pdo->exec("INSERT INTO users (id, username, password) VALUES (999, 'testuser_delete', 'pass') ON CONFLICT (id) DO UPDATE SET username = EXCLUDED.username");
        
        $uuid = $this->shareManager->createShare(999, 'cascadetest.txt', 'forever');
        $this->assertNotNull($this->shareManager->getShare($uuid));

        $pdo->exec("DELETE FROM users WHERE id = 999");
        
        // The share should be gone
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM shared_files WHERE uuid = ?");
        $stmt->execute([$uuid]);
        $this->assertEquals(0, $stmt->fetchColumn());
    }

    public function testListShares() {
        $this->shareManager->createShare(1, 'file1.txt', 'forever');
        $this->shareManager->createShare(1, 'file2.txt', '1h');
        
        $shares = $this->shareManager->listShares(1);
        $this->assertCount(2, $shares);
        // Should be ordered by created_at DESC, so file2.txt is likely first if created after
        $this->assertEquals('file2.txt', $shares[0]['filename']);
        $this->assertEquals('file1.txt', $shares[1]['filename']);
    }

    public function testDeleteShare() {
        $uuid = $this->shareManager->createShare(1, 'todelete.txt', 'forever');
        $this->assertNotNull($this->shareManager->getShare($uuid));

        $result = $this->shareManager->deleteShare($uuid, 1);
        $this->assertTrue($result);
        $this->assertNull($this->shareManager->getShare($uuid));
    }

    public function testDeleteShareInvalidUser() {
        $uuid = $this->shareManager->createShare(1, 'notmine.txt', 'forever');
        
        // Try to delete with user 2 (which doesn't exist but we want to check user_id check)
        $result = $this->shareManager->deleteShare($uuid, 2);
        $this->assertFalse($result);
        $this->assertNotNull($this->shareManager->getShare($uuid));
    }
}
