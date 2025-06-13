<?php
if (!file_exists('config.php')) {
    header('Location: install.php');
    exit();
}

session_start();
include 'db.php';

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
    <title>Achados e Perdidos - Página Inicial</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <!-- Botão de Login fora do container principal -->
    <a href="login.php" class="login-button">Login</a>
    <div class="container">
        <h1>Bem-vindo ao Sistema de Achados e Perdidos</h1>
        
        <?php if (isset($_SESSION['user_id'])): ?>
            <p>Olá, usuário! Use os links abaixo para navegar pelo sistema:</p>
            <div class="actions">
                <a href="dashboard.php">Painel de Controle</a>
                <a href="categorias/list_categories.php">Gerenciar Categorias</a>
                <a href="logout.php">Sair</a>
            </div>
        <?php else: ?>
            <p>Bem-vindo! Aqui você pode visualizar os itens encontrados sem necessidade de login.</p>
        <?php endif; ?>

        <h2>Itens Encontrados</h2>
        
        <!-- Formulário de busca e filtro -->
        <form method="GET" action="index.php">
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

        <!-- Tabela de itens encontrados -->
        <table>
            <tr>
                <th>Nome</th>
                <th>Descrição</th>
                <th>Categoria</th>
                <th>Imagem</th>
                <th>Data de Cadastro</th>
            </tr>
            <?php foreach ($itens as $item): ?>
            <tr>
                <td><?php echo htmlspecialchars($item['nome']); ?></td>
                <td><?php echo htmlspecialchars($item['descricao']); ?></td>
                <td><?php echo htmlspecialchars($item['categoria_nome']); ?></td>
                <td><img src="uploads/<?php echo htmlspecialchars($item['foto']) ? htmlspecialchars($item['foto']) : 'default.png'; ?>" alt="Imagem do Item" width="50" onclick="openModal(this.src)" style="cursor: pointer;"/></td>
                <td><?php echo date("d/m/Y", strtotime($item['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    <?php include 'utils/image_modal.php'; ?>
</body>
</html>
