<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../db.php';
require_once '../utils/log_utils.php'; // Para registrar logs

// Corrige bug: Falta de verificação se o parâmetro 'id' está presente e é numérico
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: list_categories.php");
    exit();
}
$id = (int)$_GET['id'];
$deleted_by = $_SESSION['user_id'];

$sql = "UPDATE categorias SET deleted_at = NOW(), deleted_by = ?, is_deleted = TRUE WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$deleted_by, $id]);

// Log de exclusão de categoria
logAction($pdo, [
    'user_id'     => $deleted_by,
    'entity_id'   => $id, // Corrigido conforme review: deve registrar o ID da categoria excluída
    'entity_type' => 'categoria',
    'action'      => 'delete_category',
    'reason'      => 'Categoria excluída',
    'changes'     => null,
    'status'      => 'success',
    'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
    'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? null
]);

header("Location: list_categories.php");
exit();
?>
