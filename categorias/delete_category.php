<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../db.php';

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

header("Location: list_categories.php");
exit();
?>
