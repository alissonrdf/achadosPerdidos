<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../utils/log_utils.php';

class LoginLogIntegrationTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->pdo->exec("CREATE TABLE usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT,
            password_hash TEXT,
            role TEXT,
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
        // Insere usuário de teste
        $passwordHash = password_hash('senha123', PASSWORD_DEFAULT);
        $stmt = $this->pdo->prepare("INSERT INTO usuarios (username, password_hash, role) VALUES (?, ?, ?)");
        $stmt->execute(['usuario_teste', $passwordHash, 'user']);
    }

    public function testLoginRegistersLog()
    {
        // Busca usuário
        $stmt = $this->pdo->prepare("SELECT * FROM usuarios WHERE username = ?");
        $stmt->execute(['usuario_teste']);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Simula chamada ao logAction como seria feito no login.php
        $logId = logAction($this->pdo, [
            'user_id'     => $user['id'],
            'entity_id'   => null,
            'entity_type' => null,
            'action'      => 'login',
            'reason'      => 'Login realizado',
            'status'      => 'success',
            'ip_address'  => '127.0.0.1',
            'user_agent'  => 'PHPUnit'
        ]);

        // Verifica se o log foi criado corretamente
        $stmt = $this->pdo->query("SELECT * FROM logs WHERE id = $logId");
        $log = $stmt->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($log);
        $this->assertEquals($user['id'], $log['user_id']);
        $this->assertEquals('login', $log['action']);
        $this->assertEquals('success', $log['status']);
        $this->assertEquals('127.0.0.1', $log['ip_address']);
        $this->assertEquals('PHPUnit', $log['user_agent']);
    }
}
