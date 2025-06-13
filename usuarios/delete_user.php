<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../db.php';
require_once '../utils/log_utils.php'; // Corrigido para garantir o uso de logAction

$id = $_GET['id'];
$deleted_by = $_SESSION['user_id'];

// Exclusão lógica do usuário
$sql = "UPDATE usuarios SET deleted_at = NOW(), deleted_by = ?, is_deleted = TRUE WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$deleted_by, $id]);

// Log de exclusão de usuário
logAction($pdo, [
    'user_id'     => $deleted_by,
    'entity_id'   => $id,
    'entity_type' => 'usuario',
    'action'      => 'delete_user',
    'reason'      => 'Usuário excluído',
    'changes'     => null,
    'status'      => 'success',
    'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
    'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? null
]);

header("Location: list_users.php");
exit();
?>