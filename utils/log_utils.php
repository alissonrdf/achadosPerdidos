<?php
/**
 * Registra uma ação no log e retorna o ID do log inserido.
 *
 * @param PDO $pdo Conexão PDO
 * @param int $userId ID do usuário
 * @param int $itemId ID do item
 * @param string $action Ação realizada
 * @param string $reason Motivo da ação
 * @return int ID do log inserido
 */
function registerLog(PDO $pdo, int $userId, int $itemId, string $action, string $reason): int {
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, item_id, action, reason) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $itemId, $action, $reason]);
    return (int)$pdo->lastInsertId();
}
