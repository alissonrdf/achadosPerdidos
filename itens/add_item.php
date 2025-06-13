<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../db.php';
include '../utils/image_utils.php'; // Inclui a função de processamento de imagem

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $created_by = $_SESSION['user_id'];

    // Verificar se uma imagem foi enviada
    if (!empty($_FILES['image']['name'])) {
        // Gerar um nome seguro e único para a imagem usando o nome do item
        $imageName = generateSafeImageName($name);
        $targetPath = "../uploads/" . $imageName;

       // Processar e salvar a imagem em WebP
        if (processImage($_FILES['image'], $targetPath)) {
            $image = $imageName; // Usa a imagem enviada pelo usuário
        } else {
            echo "Erro ao processar a imagem.";
            exit();
        }
    } else {
        // Caso não haja imagem, buscar a imagem padrão da categoria
        $stmt = $pdo->prepare("SELECT imagem_categoria FROM categorias WHERE id = ?");
        $stmt->execute([$category_id]);
        $category = $stmt->fetch();

        if ($category && !empty($category['imagem_categoria'])) {
            $image = $category['imagem_categoria']; // Usa a imagem padrão da categoria
        } else {
            $image = 'default.webp'; // Caso a categoria não tenha uma imagem padrão
        }
    }

    // Inserir o novo item no banco de dados com a imagem correta
    $sql = "INSERT INTO itens (nome, descricao, categoria_id, foto, created_by) VALUES (?, ?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $description, $category_id, $image, $created_by]);

    header("Location: list_items.php");
    exit();
}

// Obter categorias para o campo de seleção
$category_stmt = $pdo->prepare("SELECT * FROM categorias");
$category_stmt->execute();
$categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Achados e Perdidos - Adicionar Item</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>Adicionar Novo Item</h1>

        <form method="POST" enctype="multipart/form-data">
            <label for="name">Nome:</label>
            <input type="text" name="name" id="name" required>

            <label for="description">Descrição:</label>
            <input type="text" name="description" id="description" required>

            <label for="category_id">Categoria:</label>
            <select name="category_id" id="category_id" required>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>"><?php echo htmlspecialchars($category['nome']); ?></option>
                <?php endforeach; ?>
            </select>

            <label for="image">Imagem:</label>
            <input type="file" name="image" id="image">

            <div class="button-container">
                <button type="submit" class="save-button">Cadastrar Item</button>
                 <a href="list_items.php" class="cancel-button">Cancelar</a>
            </div>
        </form>

    </div>
</body>
</html>
