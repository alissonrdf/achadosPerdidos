<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../utils/log_utils.php';

class EditUserLogIntegrationTest extends TestCase
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
            updated_at TEXT
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
        // Cria usuário para edição
        $this->pdo->exec("INSERT INTO usuarios (username, email, password_hash, role, updated_at) VALUES ('teste', 'teste@ex.com', 'hash', 'user', '2024-01-01 00:00:00')");
    }

    public function testEditUserRegistersLog()
    {
        $userId = 1;
        $adminId = 99;
        $changes = [
            'username' => ['de' => 'teste', 'para' => 'novo_teste'],
            'role' => ['de' => 'user', 'para' => 'admin']
        ];
        // Simula edição
        $stmt = $this->pdo->prepare("UPDATE usuarios SET username = ?, role = ?, updated_at = ? WHERE id = ?");
        $stmt->execute(['novo_teste', 'admin', date('Y-m-d H:i:s'), $userId]);
        // Loga
        logAction($this->pdo, [
            'user_id'     => $adminId,
            'entity_id'   => $userId,
            'entity_type' => 'usuario',
            'action'      => 'edit_user',
            'reason'      => 'Usuário editado: novo_teste',
            'changes'     => json_encode($changes, JSON_UNESCAPED_UNICODE),
            'status'      => 'success',
            'ip_address'  => '127.0.0.1',
            'user_agent'  => 'test-agent'
        ]);
        $stmt = $this->pdo->query('SELECT * FROM logs WHERE entity_id = 1 AND action = "edit_user"');
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($row);
        $this->assertSame('edit_user', $row['action']);
        $this->assertSame('usuario', $row['entity_type']);
        $this->assertStringContainsString('novo_teste', $row['changes']);
    }
}
