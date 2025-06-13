<?php
/**
 * Centraliza o registro de logs para facilitar manutenção e evitar erros.
 * Use logAction em vez de registerLog diretamente nos CRUDs.
 */
function logAction(PDO $pdo, array $data): int {
    $userId     = $data['user_id']     ?? null;
    $entityId   = $data['entity_id']   ?? null;
    $entityType = $data['entity_type'] ?? null;
    $action     = $data['action']      ?? '';
    $reason     = $data['reason']      ?? '';
    $changes    = $data['changes']     ?? null;
    $status     = ($data['status'] ?? 'success') === 'error' ? 'error' : 'success';
    $ip         = isset($data['ip_address']) ? substr($data['ip_address'], 0, 45) : null;
    $userAgent  = isset($data['user_agent']) ? substr($data['user_agent'], 0, 255) : null;
    return registerLog(
        $pdo,
        $userId,
        $entityId,
        $entityType,
        $action,
        $reason,
        $changes,
        $status,
        $ip,
        $userAgent
    );
}

/**
 * Registra uma ação no log e retorna o ID do log inserido.
 * Não use diretamente nos CRUDs, use logAction.
 */
function registerLog(
    PDO $pdo,
    int $userId,
    ?int $entityId,
    ?string $entityType,
    string $action,
    string $reason,
    ?string $changes = null,
    string $status = 'success',
    ?string $ipAddress = null,
    ?string $userAgent = null
): int {
    // Garante que status seja 'success' ou 'error'
    $status = ($status === 'error') ? 'error' : 'success';
    $ipAddress = $ipAddress ? substr($ipAddress, 0, 45) : null;
    $userAgent = $userAgent ? substr($userAgent, 0, 255) : null;
    $stmt = $pdo->prepare("INSERT INTO logs (user_id, entity_id, entity_type, action, reason, changes, status, ip_address, user_agent) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $userId,
        $entityId,
        $entityType,
        $action,
        $reason,
        $changes,
        $status,
        $ipAddress,
        $userAgent
    ]);
    return (int)$pdo->lastInsertId();
}
