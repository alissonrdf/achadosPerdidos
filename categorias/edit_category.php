<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit();
}

include '../db.php';
include '../utils/image_utils.php'; // Inclui a função de processamento de imagem
require_once '../utils/log_utils.php'; // Para registrar logs

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
    $permite_foto = isset($_POST['permite_foto']) ? 1 : 0;
    $changes = [];

    // Verificar alterações
    if ($name !== $categoria['nome']) {
        $changes['nome'] = ['de' => $categoria['nome'], 'para' => $name];
    }

    // Verificar se o usuário enviou uma nova imagem
    if (!empty($_FILES['image']['name'])) {
        if (!isImageSizeAllowed($_FILES['image'])) {
            echo "O arquivo excede o tamanho máximo permitido de 10MB.";
            exit();
        }
        // Gerar um nome seguro e único para a imagem usando o nome do item
        $imageName = generateSafeImageName($name);
        $targetPath = "../uploads/" . $imageName;

        // Processar a nova imagem
        if (processImage($_FILES['image'], $targetPath)) {
            $changes['imagem_categoria'] = ['de' => $categoria['imagem_categoria'], 'para' => $imageName];
            $image = $imageName; // Atualiza o nome da imagem no banco de dados
        } else {
            echo "Erro ao processar a imagem.";
        }
    }

    // Verificar se a opção de permitir fotos foi alterada
    if ($permite_foto != $categoria['permite_foto']) {
        $changes['permite_foto'] = ['de' => $categoria['permite_foto'], 'para' => $permite_foto];
    }

    // Atualizar a categoria no banco de dados
    $sql = "UPDATE categorias SET nome = ?, imagem_categoria = ?, permite_foto = ?, updated_at = NOW(), updated_by = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $image, $permite_foto, $updated_by, $id]);

    // Log de edição de categoria
    logAction($pdo, [
        'user_id'     => $updated_by,
        'entity_id'   => $id,
        'entity_type' => 'categoria',
        'action'      => 'edit_category',
        'reason'      => 'Categoria editada: ' . $name,
        'changes'     => !empty($changes) ? json_encode($changes, JSON_UNESCAPED_UNICODE) : null,
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

            <label for="permite_foto">
                <input type="checkbox" name="permite_foto" id="permite_foto" value="1" <?php echo ($categoria['permite_foto'] ? 'checked' : ''); ?>>
                Permitir cadastro de fotos para itens desta categoria
            </label>

            <div class="button-container">
                <button type="submit" class="save-button">Salvar Alterações</button>
                 <a href="list_categories.php" class="cancel-button">Cancelar</a>
            </div>
        </form>

    </div>
</body>
</html>
