<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../db.php';

$id = $_GET['id'];
$deleted_by = $_SESSION['user_id'];

$sql = "UPDATE categorias SET deleted_at = NOW(), deleted_by = ? WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$deleted_by, $id]);

header("Location: list_categories.php");
exit();
?>
