<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../utils/log_utils.php';

class LogStatusTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
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

    public function testLogActionWithValidStatus(): void
    {
        $id = logAction($this->pdo, [
            'user_id' => 1,
            'entity_id' => 1,
            'entity_type' => 'item',
            'action' => 'create_item',
            'reason' => 'Item criado',
            'status' => 'success',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test-agent'
        ]);
        $stmt = $this->pdo->query('SELECT status FROM logs WHERE id = ' . $id);
        $this->assertSame('success', $stmt->fetchColumn());

        $id2 = logAction($this->pdo, [
            'user_id' => 1,
            'entity_id' => 1,
            'entity_type' => 'item',
            'action' => 'delete_item',
            'reason' => 'Item excluído',
            'status' => 'error',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test-agent'
        ]);
        $stmt2 = $this->pdo->query('SELECT status FROM logs WHERE id = ' . $id2);
        $this->assertSame('error', $stmt2->fetchColumn());
    }

    public function testLogActionWithInvalidStatus(): void
    {
        $id = logAction($this->pdo, [
            'user_id' => 1,
            'entity_id' => 1,
            'entity_type' => 'item',
            'action' => 'edit_item',
            'reason' => 'Item editado',
            'status' => 'invalid',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test-agent'
        ]);
        $stmt = $this->pdo->query('SELECT status FROM logs WHERE id = ' . $id);
        $this->assertSame('success', $stmt->fetchColumn()); // status inválido vira success
    }
}
