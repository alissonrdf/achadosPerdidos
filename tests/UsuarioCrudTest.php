<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../utils/log_utils.php';

class UsuarioCrudTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->exec("CREATE TABLE usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT,
            email TEXT,
            password_hash TEXT,
            role TEXT,
            created_at TIMESTAMP,
            updated_at TIMESTAMP,
            deleted_at TIMESTAMP,
            deleted_by INT,
            is_deleted BOOLEAN DEFAULT 0
        )");
        $this->pdo->exec("CREATE TABLE logs (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INT,
            entity_id INT,
            entity_type TEXT,
            action TEXT,
            reason TEXT,
            changes TEXT,
            status TEXT,
            ip_address TEXT,
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )");
    }

    public function testAddUsuario(): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO usuarios (username, email, password_hash, role) VALUES (?, ?, ?, ?)");
        $stmt->execute(['user1', 'user1@email.com', 'hash', 'user']);
        $this->assertEquals(1, $this->pdo->lastInsertId());
    }

    public function testEditUsuario(): void
    {
        $this->pdo->exec("INSERT INTO usuarios (username, email, password_hash, role) VALUES ('user1', 'user1@email.com', 'hash', 'user')");
        $stmt = $this->pdo->prepare("UPDATE usuarios SET username = ? WHERE id = ?");
        $stmt->execute(['novoUser', 1]);
        $stmt = $this->pdo->query("SELECT username FROM usuarios WHERE id = 1");
        $this->assertEquals('novoUser', $stmt->fetchColumn());
    }

    public function testDeleteUsuario(): void
    {
        $this->pdo->exec("INSERT INTO usuarios (username, email, password_hash, role) VALUES ('user1', 'user1@email.com', 'hash', 'user')");
        $stmt = $this->pdo->prepare("UPDATE usuarios SET is_deleted = 1, deleted_by = ? WHERE id = ?");
        $stmt->execute([2, 1]);
        $stmt = $this->pdo->query("SELECT is_deleted FROM usuarios WHERE id = 1");
        $this->assertEquals(1, $stmt->fetchColumn());
    }
}
