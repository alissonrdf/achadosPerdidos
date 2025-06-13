<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../db.php';

$id = $_GET['id'];
$deleted_by = $_SESSION['user_id'];

// Exclusão lógica do usuário
$sql = "UPDATE usuarios SET deleted_at = NOW(), deleted_by = ?, is_deleted = TRUE WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$deleted_by, $id]);

header("Location: list_users.php");
exit();
?>