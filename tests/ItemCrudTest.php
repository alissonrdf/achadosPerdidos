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
        $this->assertEquals(1, $this->pdo->lastInsertId());
    }

    public function testEditItem(): void
    {
        $this->pdo->exec("INSERT INTO itens (nome, descricao, categoria_id, foto, created_by) VALUES ('Item1', 'Desc', 1, 'default.webp', 1)");
        $stmt = $this->pdo->prepare("UPDATE itens SET nome = ? WHERE id = ?");
        $stmt->execute(['NovoNome', 1]);
        $stmt = $this->pdo->query("SELECT nome FROM itens WHERE id = 1");
        $this->assertEquals('NovoNome', $stmt->fetchColumn());
    }

    public function testDeleteItem(): void
    {
        $this->pdo->exec("INSERT INTO itens (nome, descricao, categoria_id, foto, created_by) VALUES ('Item1', 'Desc', 1, 'default.webp', 1)");
        $stmt = $this->pdo->prepare("UPDATE itens SET is_deleted = 1, deleted_by = ? WHERE id = ?");
        $stmt->execute([2, 1]);
        $stmt = $this->pdo->query("SELECT is_deleted FROM itens WHERE id = 1");
        $this->assertEquals(1, $stmt->fetchColumn());
    }
}
