<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../db.php';

// Consulta para obter todos os usuários ativos
$sql = "SELECT id, username, email, created_at, role FROM usuarios WHERE is_deleted = FALSE";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$usuarios = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Lista de Usuários</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>Usuários</h1>
        <a href="add_user.php" class="button-link">Adicionar Novo Usuário</a>
        
        <table>
            <tr>
                <th>Nome de Usuário</th>
                <th>Email</th>
                <th>Data de Criação</th>
                <th>Papel</th>
                <th>Ações</th>
            </tr>
            <?php foreach ($usuarios as $usuario): ?>
            <tr>
                <td><?php echo htmlspecialchars($usuario['username']); ?></td>
                <td><?php echo htmlspecialchars($usuario['email']); ?></td>
                <td><?php echo date("d/m/Y", strtotime($usuario['created_at'])); ?></td>
                <td><?php echo ($usuario['role'] == 'admin') ? 'Administrador' : 'Usuário Comum'; ?></td>
                <td>
                    <a href="edit_user.php?id=<?php echo $usuario['id']; ?>">Editar</a> |
                    <a href="delete_user.php?id=<?php echo $usuario['id']; ?>" onclick="return confirm('Tem certeza que deseja excluir este usuário?');">Excluir</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <div class="actions">
            <a href="../dashboard.php" class="button-link">Voltar ao Painel de Controle</a>
        </div>
    </div>
</body>
</html>
