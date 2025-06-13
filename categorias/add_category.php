<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../db.php';
include '../utils/image_utils.php'; // Inclui a função de processamento de imagem
require_once '../utils/log_utils.php'; // Para registrar logs

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $created_by = $_SESSION['user_id'];
    $image = 'default.webp';

    if (!empty($_FILES['image']['name'])) {
       // Gerar um nome seguro e único para a imagem usando o nome do item
       $imageName = generateSafeImageName($name);
       $targetPath = "../uploads/" . $imageName;

        // Processar e salvar a imagem em WebP
        if (processImage($_FILES['image'], $targetPath)) {
            $image = $imageName;
        } else {
            echo "Erro ao processar a imagem.";
        }
    }

    $sql = "INSERT INTO categorias (nome, imagem_categoria, created_by) VALUES (?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $image, $created_by]);

    // Log de criação de categoria
    logAction($pdo, [
        'user_id'     => $created_by,
        'entity_id'   => null,
        'entity_type' => 'categoria',
        'action'      => 'create_category',
        'reason'      => 'Categoria criada: ' . $name,
        'changes'     => null,
        'status'      => 'success',
        'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);

    header("Location: list_categories.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Achados e Perdidos - Adicionar Categoria</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>Adicionar Nova Categoria</h1>
        
        <form method="POST" enctype="multipart/form-data">
            <label for="name">Nome:</label>
            <input type="text" name="name" id="name" required>

            <label for="image">Imagem Padrão:</label>
            <input type="file" name="image" id="image" accept="image/*">
            <small>Tipos permitidos: JPG, PNG, GIF, WEBP, BMP. Tamanho máximo: 2MB.</small>

            <div class="button-container">
                <button type="submit" class="save-button">Cadastrar Categoria</button>
                 <a href="list_categories.php" class="cancel-button">Cancelar</a>
            </div>
        </form>

    </div>
</body>
</html>
