<?php

namespace Tests;

use App\Config\Database;
use App\UserManager;
use Exception;
use PHPUnit\Framework\TestCase;

class UserManagerTest extends TestCase {
    private Database $db;
    private UserManager $userManager;

    protected function setUp(): void {
        $this->db = new Database();
        
        // Rebuild tables before each test
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
    }

    public function testAddUser() {
        $id = $this->userManager->addUser('testuser', 'password123');
        $this->assertGreaterThan(0, $id);

        $user = $this->userManager->getUserById($id);
        $this->assertEquals('testuser', $user['username']);
        $this->assertEquals('user', $user['role']);
    }

    public function testAddDuplicateUserThrowsException() {
        $this->userManager->addUser('testuser', 'password123');
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Username already exists.");
        $this->userManager->addUser('testuser', 'anotherpass');
    }

    public function testRemoveUser() {
        $id = $this->userManager->addUser('toremove', 'pass');
        $this->assertTrue($this->userManager->removeUser($id));
        
        $user = $this->userManager->getUserById($id);
        $this->assertNull($user);
    }

    public function testListUsers() {
        $this->userManager->addUser('user1', 'pass');
        $this->userManager->addUser('user2', 'pass');
        
        $users = $this->userManager->listUsers();
        $this->assertCount(2, $users);
    }

    public function testChangePassword() {
        $id = $this->userManager->addUser('passwduser', 'oldpassword');
        
        $this->assertTrue($this->userManager->changePassword($id, 'oldpassword', 'newpassword'));
        
        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Senha atual incorreta.");
        $this->userManager->changePassword($id, 'oldpassword', 'evennewerpassword');
    }
}
