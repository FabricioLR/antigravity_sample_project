<?php

namespace Tests;

use App\Auth;
use App\Config\Database;
use App\UserManager;
use PHPUnit\Framework\TestCase;

class AuthTest extends TestCase {
    private Database $db;
    private Auth $auth;
    private UserManager $userManager;

    protected function setUp(): void {
        $this->db = new Database();
        
        // Setup schema
        $pdo = $this->db->getConnection();
        $pdo->exec("DROP TABLE IF EXISTS users CASCADE;");
        $pdo->exec("
            CREATE TABLE users (
                id SERIAL PRIMARY KEY,
                username VARCHAR(255) UNIQUE NOT NULL,
                password VARCHAR(255) NOT NULL,
                role VARCHAR(50) DEFAULT 'user',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        ");

        $this->userManager = new UserManager($this->db);
        $this->auth = new Auth($this->db);

        // Add a test user
        $this->userManager->addUser('admin', 'adminpass', 'admin');
    }

    protected function tearDown(): void {
        if (session_status() === PHP_SESSION_ACTIVE) {
            session_destroy();
        }
        $_SESSION = [];
    }

    public function testValidLogin() {
        $this->assertTrue($this->auth->login('admin', 'adminpass'));
        $this->assertTrue($this->auth->isLoggedIn());
        $this->assertTrue($this->auth->isAdmin());
        $this->assertEquals(1, $this->auth->getCurrentUserId());
    }

    public function testInvalidLogin() {
        $this->assertFalse($this->auth->login('admin', 'wrongpass'));
        // Make sure it doesn't leak from previous test run if sessions were preserved (which shouldn't in CLI)
        $this->assertFalse($this->auth->isLoggedIn());
    }

    public function testLogout() {
        $this->auth->login('admin', 'adminpass');
        $this->assertTrue($this->auth->isLoggedIn());
        
        $this->auth->logout();
        $this->assertFalse($this->auth->isLoggedIn());
    }
}
