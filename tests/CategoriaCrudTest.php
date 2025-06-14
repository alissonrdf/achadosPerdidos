<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../utils/log_utils.php';

class CategoriaCrudTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->exec("CREATE TABLE categorias (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            nome TEXT,
            imagem_categoria TEXT,
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

    public function testAddCategoria(): void
    {
        $stmt = $this->pdo->prepare("INSERT INTO categorias (nome, imagem_categoria, created_by) VALUES (?, ?, ?)");
        $stmt->execute(['Teste', 'default.webp', 1]);
        $categoriaId = $this->pdo->lastInsertId();
        logAction($this->pdo, [
            'user_id' => 99,
            'entity_id' => $categoriaId,
            'entity_type' => 'categoria',
            'action' => 'create_category',
            'reason' => 'Categoria criada',
            'changes' => json_encode(['nome' => ['de' => null, 'para' => 'Teste']]),
            'status' => 'success',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit'
        ]);
        $stmt = $this->pdo->query("SELECT * FROM logs WHERE entity_id = $categoriaId AND action = 'create_category'");
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($log);
        $this->assertEquals('success', $log['status']);
        $this->assertEquals('categoria', $log['entity_type']);
    }

    public function testEditCategoria(): void
    {
        $this->pdo->exec("INSERT INTO categorias (nome, imagem_categoria, created_by) VALUES ('Teste', 'default.webp', 1)");
        $stmt = $this->pdo->prepare("UPDATE categorias SET nome = ? WHERE id = ?");
        $stmt->execute(['NovoNome', 1]);
        logAction($this->pdo, [
            'user_id' => 99,
            'entity_id' => 1,
            'entity_type' => 'categoria',
            'action' => 'edit_category',
            'reason' => 'Categoria editada',
            'changes' => json_encode(['nome' => ['de' => 'Teste', 'para' => 'NovoNome']]),
            'status' => 'success',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit'
        ]);
        $stmt = $this->pdo->query("SELECT * FROM logs WHERE entity_id = 1 AND action = 'edit_category'");
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($log);
        $this->assertEquals('success', $log['status']);
        $this->assertEquals('categoria', $log['entity_type']);
    }

    public function testDeleteCategoria(): void
    {
        $this->pdo->exec("INSERT INTO categorias (nome, imagem_categoria, created_by) VALUES ('Teste', 'default.webp', 1)");
        $stmt = $this->pdo->prepare("UPDATE categorias SET is_deleted = 1, deleted_by = ? WHERE id = ?");
        $stmt->execute([2, 1]);
        logAction($this->pdo, [
            'user_id' => 2,
            'entity_id' => 1,
            'entity_type' => 'categoria',
            'action' => 'delete_category',
            'reason' => 'Categoria excluída',
            'changes' => null,
            'status' => 'success',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit'
        ]);
        $stmt = $this->pdo->query("SELECT * FROM logs WHERE entity_id = 1 AND action = 'delete_category'");
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($log);
        $this->assertEquals('success', $log['status']);
        $this->assertEquals('categoria', $log['entity_type']);
    }
}
