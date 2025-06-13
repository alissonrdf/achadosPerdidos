<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../utils/log_utils.php';

class LogUtilsTest extends TestCase
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
            status TEXT,
            ip_address TEXT,
            user_agent TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            changes TEXT
        )");
    }

    public function testLogActionInsertsRow(): void
    {
        $id = logAction($this->pdo, [
            'user_id' => 1,
            'entity_id' => 2,
            'entity_type' => 'item',
            'action' => 'delete_item',
            'reason' => 'duplicate',
            'status' => 'success',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test-agent'
        ]);
        $stmt = $this->pdo->query('SELECT * FROM logs WHERE id = ' . $id);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('delete_item', $row['action']);
        $this->assertSame('duplicate', $row['reason']);
        $this->assertEquals(1, $row['user_id']);
        $this->assertEquals(2, $row['entity_id']);
        $this->assertEquals('item', $row['entity_type']);
        $this->assertSame('success', $row['status']);
        $this->assertSame('127.0.0.1', $row['ip_address']);
        $this->assertSame('test-agent', $row['user_agent']);
    }

    public function testLogActionWithNullEntityIdAndType(): void
    {
        $id = logAction($this->pdo, [
            'user_id' => 1,
            'entity_id' => null,
            'entity_type' => null,
            'action' => 'login',
            'reason' => 'Login realizado',
            'status' => 'success',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test-agent'
        ]);
        $stmt = $this->pdo->query('SELECT * FROM logs WHERE id = ' . $id);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('login', $row['action']);
        $this->assertSame('Login realizado', $row['reason']);
        $this->assertEquals(1, $row['user_id']);
        $this->assertNull($row['entity_id']);
        $this->assertNull($row['entity_type']);
    }

    public function testLogActionWithEntityIdAndType(): void
    {
        $id = logAction($this->pdo, [
            'user_id' => 2,
            'entity_id' => 10,
            'entity_type' => 'item',
            'action' => 'edit_item',
            'reason' => 'Item editado',
            'changes' => '{"nome":{"de":"A","para":"B"}}',
            'status' => 'success',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test-agent'
        ]);
        $stmt = $this->pdo->query('SELECT * FROM logs WHERE id = ' . $id);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('edit_item', $row['action']);
        $this->assertSame('Item editado', $row['reason']);
        $this->assertEquals(2, $row['user_id']);
        $this->assertEquals(10, $row['entity_id']);
        $this->assertSame('item', $row['entity_type']);
        $this->assertSame('{"nome":{"de":"A","para":"B"}}', $row['changes']);
    }

    public function testLogActionWithErrorStatus(): void
    {
        $id = logAction($this->pdo, [
            'user_id' => 3,
            'entity_id' => 5,
            'entity_type' => 'usuario',
            'action' => 'delete_user',
            'reason' => 'Erro ao excluir',
            'status' => 'error',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'test-agent'
        ]);
        $stmt = $this->pdo->query('SELECT * FROM logs WHERE id = ' . $id);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('error', $row['status']);
    }
}
