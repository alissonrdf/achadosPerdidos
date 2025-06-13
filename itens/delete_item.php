<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../db.php';
require_once '../utils/log_utils.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');
    $confirmed = $_POST['confirmed'] ?? '';
    if ($id && $reason !== '' && $confirmed === '1') {
        $deleted_by = $_SESSION['user_id'];
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE itens SET deleted_at = NOW(), deleted_by = ?, is_deleted = TRUE WHERE id = ?");
            $stmt->execute([$deleted_by, $id]);
            logAction($pdo, [
                'user_id'     => $deleted_by,
                'entity_id'   => $id,
                'entity_type' => 'item',
                'action'      => 'delete_item',
                'reason'      => $reason,
                'changes'     => null,
                'status'      => 'success',
                'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            $pdo->commit();
            header("Location: list_items.php");
            exit();
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            // Log detalhado do erro
            logAction($pdo, [
                'user_id'     => $deleted_by,
                'entity_id'   => $id,
                'entity_type' => 'item',
                'action'      => 'delete_item_error',
                'reason'      => 'Ocorreu um erro ao excluir o item.',
                'changes'     => null,
                'status'      => 'error',
                'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
                'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
            // Exibe mensagem genérica para o usuário e interrompe execução
            $error = 'Ocorreu um erro ao tentar excluir o item. Por favor, tente novamente mais tarde.';
            echo '<p style="color:red;">' . htmlspecialchars($error) . '</p>';
            exit();
        }
    } else {
        $error = 'Confirmação e motivo da exclusão são obrigatórios.';
    }
} else {
    $id = (int)($_GET['id'] ?? 0);
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Confirmar Exclusão</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
<div class="container">
    <h1>Confirmar Exclusão</h1>
    <form method="POST" action="delete_item.php" id="deleteForm">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($id); ?>">
        <input type="hidden" name="confirmed" id="confirmed" value="0">
        <label for="reason">Motivo da exclusão:</label>
        <textarea name="reason" id="reason" required></textarea>
        <?php if ($error): ?>
            <p style="color:red;"><?php echo htmlspecialchars($error); ?></p>
        <?php endif; ?>
        <div class="button-container">
            <button type="submit" class="save-button">Excluir</button>
            <a href="list_items.php" class="cancel-button">Cancelar</a>
        </div>
    </form>
</div>
<script>
document.getElementById('deleteForm').addEventListener('submit', function(e) {
    if (!confirm('Tem certeza que deseja excluir este item?')) {
        e.preventDefault();
    } else {
        document.getElementById('confirmed').value = '1';
    }
});
</script>
</body>
</html>
