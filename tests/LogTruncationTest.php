<?php
use PHPUnit\Framework\TestCase;

require_once __DIR__ . '/../utils/log_utils.php';

class LogTruncationTest extends TestCase
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

    /**
     * Testa se valores longos são truncados automaticamente para campos com limitações de tamanho
     */
    public function testLogActionTruncatesLongValues(): void 
    {
        $longIp = str_repeat('192.168.100.', 20); // IP muito longo (> 45 caracteres)
        $longUserAgent = str_repeat('Mozilla/5.0 (Windows NT 10.0; Win64; x64) ', 30); // User agent muito longo (> 255 caracteres)
        
        $id = logAction($this->pdo, [
            'user_id' => 1,
            'entity_id' => 1,
            'entity_type' => 'item',
            'action' => 'test_truncate',
            'reason' => 'Teste de truncamento de valores longos',
            'ip_address' => $longIp,
            'user_agent' => $longUserAgent
        ]);
        
        $stmt = $this->pdo->query("SELECT * FROM logs WHERE id = $id");
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotEmpty($log);
        $this->assertLessThanOrEqual(45, strlen($log['ip_address']));
        $this->assertLessThanOrEqual(255, strlen($log['user_agent']));
        
        // Verifica se os valores foram realmente truncados quando necessário
        $this->assertTrue(strlen($longIp) > 45, "O IP original deve ser maior que 45 caracteres para testar o truncamento");
        $this->assertTrue(strlen($longUserAgent) > 255, "O User Agent original deve ser maior que 255 caracteres para testar o truncamento");
        
        // Verifica se os valores foram truncados corretamente
        $this->assertEquals(substr($longIp, 0, 45), $log['ip_address'], "O IP deve ser truncado exatamente nos primeiros 45 caracteres");
        $this->assertEquals(substr($longUserAgent, 0, 255), $log['user_agent'], "O User Agent deve ser truncado exatamente nos primeiros 255 caracteres");
    }
    
    /**
     * Testa se os valores são truncados também na função registerLog
     */
    public function testRegisterLogTruncatesLongValues(): void 
    {
        $longIp = str_repeat('192.168.100.', 20); // IP muito longo (> 45 caracteres)
        $longUserAgent = str_repeat('Mozilla/5.0 (Windows NT 10.0; Win64; x64) ', 30); // User agent muito longo (> 255 caracteres)
        
        $id = registerLog(
            $this->pdo,
            1,
            1,
            'item',
            'test_truncate',
            'Teste de truncamento direto',
            null,
            'success',
            $longIp,
            $longUserAgent
        );
        
        $stmt = $this->pdo->query("SELECT * FROM logs WHERE id = $id");
        $log = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $this->assertNotEmpty($log);
        $this->assertLessThanOrEqual(45, strlen($log['ip_address']));
        $this->assertLessThanOrEqual(255, strlen($log['user_agent']));
    }
}
