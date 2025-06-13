<?php
session_start();
include '../db.php';

// Variáveis de busca e filtro
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';
$date_start = $_GET['date_start'] ?? '';
$date_end = $_GET['date_end'] ?? '';

// Consulta para obter categorias para o filtro
$category_stmt = $pdo->prepare("SELECT * FROM categorias");
$category_stmt->execute();
$categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);

// Consulta para listar itens com busca e filtro
$sql = "SELECT itens.*, categorias.nome AS categoria_nome 
        FROM itens 
        LEFT JOIN categorias ON itens.categoria_id = categorias.id 
        WHERE itens.is_deleted = FALSE";
$params = [];

if ($search) {
    $sql .= " AND (itens.nome LIKE ? OR itens.descricao LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

if ($category_filter) {
    $sql .= " AND itens.categoria_id = ?";
    $params[] = $category_filter;
}

if ($date_start) {
    $sql .= " AND itens.created_at >= ?";
    $params[] = $date_start;
}

if ($date_end) {
    $sql .= " AND itens.created_at <= ?";
    $params[] = $date_end;
}

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$itens = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Achados e Perdidos - Itens Encontrados</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>Itens Encontrados</h1>
        
        <!-- Formulário de busca e filtro -->
        <form method="GET" action="list_items.php">
            <input type="search" name="search" placeholder="Buscar por nome ou descrição..." value="<?php echo htmlspecialchars($search); ?>">
            
            <select name="category">
                <option value="">Todas as Categorias</option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo ($category_filter == $category['id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <label for="date_start">Data Início:</label>
            <input type="date" name="date_start" id="date_start" value="<?php echo htmlspecialchars($date_start); ?>">

            <label for="date_end">Data Fim:</label>
            <input type="date" name="date_end" id="date_end" value="<?php echo htmlspecialchars($date_end); ?>">

            <button type="submit">Filtrar</button>
        </form>
        <div class="actions">
            <a href="add_item.php">Adicionar Novo Item</a>
        </div>

        <!-- Tabela de itens encontrados -->
        <table>
            <tr>
                <th>Nome</th>
                <th>Descrição</th>
                <th>Categoria</th>
                <th>Imagem</th>
                <th>Data de Cadastro</th>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <th>Ações</th>
                <?php endif; ?>
            </tr>
            <?php foreach ($itens as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['nome']); ?></td>
                <td><?php echo htmlspecialchars($item['descricao']); ?></td>
                <td><?php echo htmlspecialchars($item['categoria_nome']); ?></td>
                <td><img src="../uploads/<?php echo htmlspecialchars($item['foto']) ? htmlspecialchars($item['foto']) : 'default.png'; ?>" alt="Imagem do Item" width="50" onclick="openModal(this.src)" style="cursor: pointer;" /></td>
                <td><?php echo date("d/m/Y", strtotime($item['created_at'])); ?></td>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <td>
                        <a href="edit_item.php?id=<?php echo $item['id']; ?>">Editar</a> |
                        <a href="delete_item.php?id=<?php echo $item['id']; ?>" onclick="return confirm('Tem certeza que deseja excluir este item?');">Excluir</a>
                    </td>
                <?php endif; ?>
            </tr>
            <?php endforeach; ?>
        </table>

        <!-- Botão de voltar ao dashboard -->
        <div class="actions">
            <a href="../dashboard.php" class="button-link">Voltar ao Painel de Controle</a>
        </div>
    </div>
    <?php include '../utils/image_modal.php'; ?>
</body>
</html>
