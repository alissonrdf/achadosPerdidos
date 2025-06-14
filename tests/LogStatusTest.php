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

    /**
     * Testa se status inválidos são convertidos para 'success'
     * 
     * A função logAction deve tratar valores de status inválidos e convertê-los para 'success'.
     * Este comportamento é importante para garantir consistência no banco de dados e
     * evitar falhas ao registrar logs quando um valor inválido é fornecido acidentalmente.
     * 
     * Casos em que isso é útil:
     * - Integração com sistemas de terceiros que possam enviar valores inesperados
     * - Erros de digitação ao chamar a função manualmente
     * - Valores vindos de variáveis que podem estar vazias ou não inicializadas
     */
    public function testLogActionWithInvalidStatus(): void
    {
        // Teste com status inválido
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
        $this->assertSame('success', $stmt->fetchColumn(), 'Status inválido deve ser convertido para "success"');
        
        // Teste com status vazio
        $id2 = logAction($this->pdo, [
            'user_id' => 1,
            'action' => 'view_item',
            'reason' => 'Item visualizado',
            'status' => '',
        ]);
        $stmt2 = $this->pdo->query('SELECT status FROM logs WHERE id = ' . $id2);
        $this->assertSame('success', $stmt2->fetchColumn(), 'Status vazio deve ser convertido para "success"');
        
        // Teste sem fornecer status (valor padrão)
        $id3 = logAction($this->pdo, [
            'user_id' => 1,
            'action' => 'list_items',
            'reason' => 'Listagem de itens',
        ]);
        $stmt3 = $this->pdo->query('SELECT status FROM logs WHERE id = ' . $id3);
        $this->assertSame('success', $stmt3->fetchColumn(), 'Ausência de status deve resultar em "success"');
    }
}
