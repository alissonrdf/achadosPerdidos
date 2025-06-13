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
        $this->assertEquals(1, $this->pdo->lastInsertId());
    }

    public function testEditCategoria(): void
    {
        $this->pdo->exec("INSERT INTO categorias (nome, imagem_categoria, created_by) VALUES ('Teste', 'default.webp', 1)");
        $stmt = $this->pdo->prepare("UPDATE categorias SET nome = ? WHERE id = ?");
        $stmt->execute(['NovoNome', 1]);
        $stmt = $this->pdo->query("SELECT nome FROM categorias WHERE id = 1");
        $this->assertEquals('NovoNome', $stmt->fetchColumn());
    }

    public function testDeleteCategoria(): void
    {
        $this->pdo->exec("INSERT INTO categorias (nome, imagem_categoria, created_by) VALUES ('Teste', 'default.webp', 1)");
        $stmt = $this->pdo->prepare("UPDATE categorias SET is_deleted = 1, deleted_by = ? WHERE id = ?");
        $stmt->execute([2, 1]);
        $stmt = $this->pdo->query("SELECT is_deleted FROM categorias WHERE id = 1");
        $this->assertEquals(1, $stmt->fetchColumn());
    }
}
