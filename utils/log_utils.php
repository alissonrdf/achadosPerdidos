<?php
function registerLog(PDO $pdo, int $userId, int $itemId, string $action, string $reason): int {
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, item_id, action, reason) VALUES (?, ?, ?, ?)");
    $stmt->execute([$userId, $itemId, $action, $reason]);
    return (int)$pdo->lastInsertId();
}
