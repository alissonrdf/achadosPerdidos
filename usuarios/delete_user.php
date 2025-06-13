<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../db.php';
require_once '../utils/log_utils.php'; // Para registrar logs

$id = $_GET['id'];
$deleted_by = $_SESSION['user_id'];

// Exclusão lógica do usuário
$sql = "UPDATE usuarios SET deleted_at = NOW(), deleted_by = ?, is_deleted = TRUE WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$deleted_by, $id]);

// Log de exclusão de usuário
registerLog($pdo, $deleted_by, $id, 'delete_user', 'Usuário excluído');

header("Location: list_users.php");
exit();
?>