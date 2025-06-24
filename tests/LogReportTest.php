<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../utils/log_utils.php';

class LogReportTest extends TestCase
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
        
        $this->pdo->exec("CREATE TABLE usuarios (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            username TEXT,
            email TEXT,
            password_hash TEXT,
            role TEXT,
            is_deleted BOOLEAN DEFAULT 0
        )");
        
        // Inserir usuários para teste
        $this->pdo->exec("INSERT INTO usuarios (id, username, role, is_deleted) VALUES 
            (1, 'admin', 'admin', 0),
            (2, 'user1', 'user', 0),
            (3, 'deleted_user', 'user', 1)");
            
        // Inserir logs para teste
        $this->insertTestLogs();
    }
    
    private function insertTestLogs(): void
    {
        // Registro de login
        logAction($this->pdo, [
            'user_id' => 1,
            'entity_id' => null,
            'entity_type' => null,
            'action' => 'login',
            'reason' => 'Login realizado',
            'status' => 'success',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit'
        ]);
        
        // Registro de criação de item
        logAction($this->pdo, [
            'user_id' => 1,
            'entity_id' => 10,
            'entity_type' => 'item',
            'action' => 'create_item',
            'reason' => 'Item criado: Carteira',
            'changes' => json_encode(['nome' => ['de' => null, 'para' => 'Carteira']]),
            'status' => 'success',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit'
        ]);
        
        // Registro de edição de categoria
        logAction($this->pdo, [
            'user_id' => 2,
            'entity_id' => 5,
            'entity_type' => 'categoria',
            'action' => 'edit_category',
            'reason' => 'Categoria editada: Eletrônicos',
            'changes' => json_encode(['nome' => ['de' => 'Eletrônico', 'para' => 'Eletrônicos']]),
            'status' => 'success',
            'ip_address' => '192.168.1.1',
            'user_agent' => 'PHPUnit'
        ]);
        
        // Registro de erro
        logAction($this->pdo, [
            'user_id' => 1,
            'entity_id' => 15,
            'entity_type' => 'item',
            'action' => 'delete_item',
            'reason' => 'Erro ao excluir item',
            'changes' => null,
            'status' => 'error',
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit'
        ]);
    }

    public function testCanFilterLogsByEntityType(): void
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM logs WHERE entity_type = ?");
        $stmt->execute(['item']);
        $itemCount = $stmt->fetchColumn();
        
        $this->assertEquals(2, $itemCount, "Deve haver 2 logs relacionados a itens");
        
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM logs WHERE entity_type = ?");
        $stmt->execute(['categoria']);
        $categoryCount = $stmt->fetchColumn();
        
        $this->assertEquals(1, $categoryCount, "Deve haver 1 log relacionado a categorias");
    }
    
    public function testCanFilterLogsByAction(): void
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM logs WHERE action = ?");
        $stmt->execute(['login']);
        $loginCount = $stmt->fetchColumn();
        
        $this->assertEquals(1, $loginCount, "Deve haver 1 log de login");
        
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM logs WHERE action = ?");
        $stmt->execute(['delete_item']);
        $deleteCount = $stmt->fetchColumn();
        
        $this->assertEquals(1, $deleteCount, "Deve haver 1 log de exclusão de item");
    }
    
    public function testCanFilterLogsByStatus(): void
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM logs WHERE status = ?");
        $stmt->execute(['success']);
        $successCount = $stmt->fetchColumn();
        
        $this->assertEquals(3, $successCount, "Deve haver 3 logs com status de sucesso");
        
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM logs WHERE status = ?");
        $stmt->execute(['error']);
        $errorCount = $stmt->fetchColumn();
        
        $this->assertEquals(1, $errorCount, "Deve haver 1 log com status de erro");
    }
    
    public function testCanFilterLogsByUser(): void
    {
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM logs WHERE user_id = ?");
        $stmt->execute([1]);
        $adminCount = $stmt->fetchColumn();
        
        $this->assertEquals(3, $adminCount, "Deve haver 3 logs do usuário admin");
        
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM logs WHERE user_id = ?");
        $stmt->execute([2]);
        $userCount = $stmt->fetchColumn();
        
        $this->assertEquals(1, $userCount, "Deve haver 1 log do usuário comum");
    }
    
    public function testCanJoinLogsWithUsernames(): void
    {
        $sql = "SELECT l.*, u.username 
                FROM logs l 
                LEFT JOIN usuarios u ON l.user_id = u.id 
                WHERE l.user_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([1]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $this->assertCount(3, $rows, "Deve retornar 3 logs para o usuário admin");
        $this->assertEquals('admin', $rows[0]['username'], "O nome de usuário deve ser 'admin'");
    }
}
