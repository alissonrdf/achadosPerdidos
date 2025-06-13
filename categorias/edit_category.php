<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../db.php';
include '../utils/image_utils.php'; // Inclui a função de processamento de imagem

$id = $_GET['id'];
$sql = "SELECT * FROM categorias WHERE id = ? AND is_deleted = FALSE";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$categoria = $stmt->fetch();

if (!$categoria) {
    header("Location: list_categories.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $updated_by = $_SESSION['user_id'];
    $image = $categoria['imagem_categoria']; // Manter a imagem atual por padrão

    // Verificar se o usuário enviou uma nova imagem
    if (!empty($_FILES['image']['name'])) {
       // Gerar um nome seguro e único para a imagem usando o nome do item
       $imageName = generateSafeImageName($name);
       $targetPath = "../uploads/" . $imageName;

        // Processar a nova imagem
        if (processImage($_FILES['image'], $targetPath)) {
            $image = $imageName; // Atualiza o nome da imagem no banco de dados
        } else {
            echo "Erro ao processar a imagem.";
        }
    }

    // Atualizar a categoria no banco de dados
    $sql = "UPDATE categorias SET nome = ?, imagem_categoria = ?, updated_at = NOW(), updated_by = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $image, $updated_by, $id]);

    header("Location: list_categories.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Achados e Perdidos - Editar Categoria</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>Editar Categoria</h1>

        <form method="POST" enctype="multipart/form-data">
            <label for="name">Nome:</label>
            <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($categoria['nome']); ?>" required>

            <!-- Exibir imagem atual -->
            <label>Imagem Atual:</label>
            <img src="../uploads/<?php echo htmlspecialchars($categoria['imagem_categoria']); ?>" alt="Imagem Atual" width="150">

            <label for="image">Imagem (deixe em branco para manter a atual):</label>
            <input type="file" name="image" id="image" accept="image/*">
            <small>Tipos permitidos: JPG, PNG, GIF, WEBP, BMP. Tamanho máximo: 10MB.</small>

            <div class="button-container">
                <button type="submit" class="save-button">Salvar Alterações</button>
                 <a href="list_categories.php" class="cancel-button">Cancelar</a>
            </div>
        </form>

    </div>
</body>
</html>
