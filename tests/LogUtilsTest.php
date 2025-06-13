<?php
use PHPUnit\Framework\TestCase;

class LogUtilsTest extends TestCase
{
    private PDO $pdo;

    protected function setUp(): void
    {
        $this->pdo = new PDO('sqlite::memory:');
        $this->pdo->exec("CREATE TABLE logs (id INTEGER PRIMARY KEY AUTOINCREMENT, user_id INT, item_id INT, action TEXT, reason TEXT, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP)");
    }

    public function testRegisterLogInsertsRow(): void
    {
        $id = registerLog($this->pdo, 1, 2, 'delete_item', 'duplicate');
        $stmt = $this->pdo->query('SELECT * FROM logs WHERE id = ' . $id);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('delete_item', $row['action']);
        $this->assertSame('duplicate', $row['reason']);
        $this->assertEquals(1, $row['user_id']);
        $this->assertEquals(2, $row['item_id']);
    }
}
