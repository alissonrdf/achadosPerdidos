<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../utils/log_utils.php';

class ItemCrudTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->exec("CREATE TABLE itens (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT,
            descricao TEXT,
            categoria_id INT,
            foto TEXT,
            created_by INT,
            updated_by INT,
            deleted_by INT,
            created_at TIMESTAMP,
            updated_at TIMESTAMP,
            deleted_at TIMESTAMP,
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

    public function testAddItem(): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO itens (nome, descricao, categoria_id, foto, created_by) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute(['Item1', 'Desc', 1, 'default.webp', 1]);
        $itemId = $this->pdo->lastInsertId();
        logAction($this->pdo, [
            'user_id' => 99,
            'entity_id' => $itemId,
            'entity_type' => 'item',
            'action' => 'create_item',
            'reason' => 'Item criado',
            'changes' => json_encode(['nome' => ['de' => null, 'para' => 'Item1']]),
            'status' => 'success',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit'
        ]);
        $stmt = $this->pdo->query("SELECT * FROM logs WHERE entity_id = $itemId AND action = 'create_item'");
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($log);
        $this->assertEquals('success', $log['status']);
        $this->assertEquals('item', $log['entity_type']);
    }

    public function testEditItem(): void
    {
        $this->pdo->exec("INSERT INTO itens (nome, descricao, categoria_id, foto, created_by) VALUES ('Item1', 'Desc', 1, 'default.webp', 1)");
        $stmt = $this->pdo->prepare("UPDATE itens SET nome = ? WHERE id = ?");
        $stmt->execute(['NovoNome', 1]);
        logAction($this->pdo, [
            'user_id' => 99,
            'entity_id' => 1,
            'entity_type' => 'item',
            'action' => 'edit_item',
            'reason' => 'Item editado',
            'changes' => json_encode(['nome' => ['de' => 'Item1', 'para' => 'NovoNome']]),
            'status' => 'success',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit'
        ]);
        $stmt = $this->pdo->query("SELECT * FROM logs WHERE entity_id = 1 AND action = 'edit_item'");
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($log);
        $this->assertEquals('success', $log['status']);
        $this->assertEquals('item', $log['entity_type']);
    }

    public function testDeleteItem(): void
    {
        $this->pdo->exec("INSERT INTO itens (nome, descricao, categoria_id, foto, created_by) VALUES ('Item1', 'Desc', 1, 'default.webp', 1)");
        $stmt = $this->pdo->prepare("UPDATE itens SET is_deleted = 1, deleted_by = ? WHERE id = ?");
        $stmt->execute([2, 1]);
        logAction($this->pdo, [
            'user_id' => 2,
            'entity_id' => 1,
            'entity_type' => 'item',
            'action' => 'delete_item',
            'reason' => 'Item excluído',
            'changes' => null,
            'status' => 'success',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit'
        ]);
        $stmt = $this->pdo->query("SELECT * FROM logs WHERE entity_id = 1 AND action = 'delete_item'");
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($log);
        $this->assertEquals('success', $log['status']);
        $this->assertEquals('item', $log['entity_type']);
    }
}
