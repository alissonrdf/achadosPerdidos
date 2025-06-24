<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Achados e Perdidos - Painel de Controle</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Botão de logout fora do container principal -->
    <a href="logout.php" class="login-button">Sair</a>
    <div class="container">
        <h1>Painel de Controle</h1>
        <p>Bem-vindo! Use os links abaixo para gerenciar o sistema.</p>
        
        <div class="actions">
            <a href="itens/list_items.php">Lista de Itens</a>
            <a href="itens/add_item.php">Adicionar Novo Item</a>
            <a href="categorias/list_categories.php">Lista de Categorias</a>
            <a href="categorias/add_category.php">Adicionar Nova Categoria</a>
            <?php if ($_SESSION['user_role'] === 'admin'): ?>
                <a href="usuarios/list_users.php">Gerenciar Usuários</a>
                <a href="relatorios/log_report.php">Relatório de Logs</a>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
