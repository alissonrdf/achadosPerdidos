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
        $userId = $this->pdo->lastInsertId();
        logAction($this->pdo, [
            'user_id' => 99,
            'entity_id' => $userId,
            'entity_type' => 'usuario',
            'action' => 'create_user',
            'reason' => 'Usuário criado',
            'changes' => json_encode(['username' => ['de' => null, 'para' => 'user1']]),
            'status' => 'success',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit'
        ]);
        $stmt = $this->pdo->query("SELECT * FROM logs WHERE entity_id = $userId AND action = 'create_user'");
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($log);
        $this->assertEquals('success', $log['status']);
        $this->assertEquals('usuario', $log['entity_type']);
    }

    public function testEditUsuario(): void
    {
        $this->pdo->exec("INSERT INTO usuarios (username, email, password_hash, role) VALUES ('user1', 'user1@email.com', 'hash', 'user')");
        $stmt = $this->pdo->prepare("UPDATE usuarios SET username = ? WHERE id = ?");
        $stmt->execute(['novoUser', 1]);
        logAction($this->pdo, [
            'user_id' => 99,
            'entity_id' => 1,
            'entity_type' => 'usuario',
            'action' => 'edit_user',
            'reason' => 'Usuário editado',
            'changes' => json_encode(['username' => ['de' => 'user1', 'para' => 'novoUser']]),
            'status' => 'success',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit'
        ]);
        $stmt = $this->pdo->query("SELECT * FROM logs WHERE entity_id = 1 AND action = 'edit_user'");
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($log);
        $this->assertEquals('edit_user', $log['action']);
        $this->assertEquals('usuario', $log['entity_type']);
    }

    public function testDeleteUsuario(): void
    {
        $this->pdo->exec("INSERT INTO usuarios (username, email, password_hash, role) VALUES ('user1', 'user1@email.com', 'hash', 'user')");
        $stmt = $this->pdo->prepare("UPDATE usuarios SET is_deleted = 1, deleted_by = ? WHERE id = ?");
        $stmt->execute([2, 1]);
        logAction($this->pdo, [
            'user_id' => 2,
            'entity_id' => 1,
            'entity_type' => 'usuario',
            'action' => 'delete_user',
            'reason' => 'Usuário excluído',
            'changes' => null,
            'status' => 'success',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit'
        ]);
        $stmt = $this->pdo->query("SELECT * FROM logs WHERE entity_id = 1 AND action = 'delete_user'");
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($log);
        $this->assertEquals('delete_user', $log['action']);
        $this->assertEquals('usuario', $log['entity_type']);
    }
}
