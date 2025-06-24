<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../db.php';

// Consulta para obter todas as categorias ativas
$sql = "SELECT * FROM categorias WHERE is_deleted = FALSE";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$categorias = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Achados e Perdidos - Lista de Categorias</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>Categorias</h1>
        <div class="actions">
            <a href="add_category.php">Adicionar Nova Categoria</a>
        </div>
        <table>
            <tr>
                <th>Nome</th>
                <th>Imagem Padrão</th>
                <th>Permite Foto?</th>
                <th>Ações</th>
            </tr>
            <?php foreach ($categorias as $categoria): ?>
            <tr>
                <td><?php echo htmlspecialchars($categoria['nome']); ?></td>
                <td><img src="../uploads/<?php echo htmlspecialchars($categoria['imagem_categoria'] ?? 'default.webp'); ?>" width="50" /></td>
                <td><?php echo $categoria['permite_foto'] ? 'Sim' : 'Não'; ?></td>
                <td>
                    <a href="edit_category.php?id=<?php echo $categoria['id']; ?>">Editar</a> |
                    <a href="delete_category.php?id=<?php echo $categoria['id']; ?>" onclick="return confirm('Tem certeza que deseja excluir esta categoria?');">Excluir</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <!-- Botão de voltar ao dashboard -->
        <div class="actions">
            <a href="../dashboard.php" class="button-link">Voltar ao Painel de Controle</a>
        </div>
    </div>
</body>
</html>
