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
$sql = "SELECT * FROM itens WHERE id = ? AND is_deleted = FALSE";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$item = $stmt->fetch();

// Redireciona se o item não existir ou estiver excluído
if (!$item) {
    header("Location: list_items.php");
    exit();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $updated_by = $_SESSION['user_id'];
    $image = $item['foto']; // Mantém a imagem atual por padrão
    $changes = [];

    // Buscar se a categoria permite foto
    $catStmt = $pdo->prepare("SELECT imagem_categoria, permite_foto FROM categorias WHERE id = ?");
    $catStmt->execute([$category_id]);
    $category = $catStmt->fetch();
    $permite_foto = $category ? $category['permite_foto'] : 1;

    if ($category_id !== $item['categoria_id']) {
        $changes['categoria_id'] = ['de' => $item['categoria_id'], 'para' => $category_id];
        // Se mudou para uma categoria que não permite foto, zera a foto
        if ($permite_foto == 0) {
            $image = $category['imagem_categoria'] ?? 'default.webp';
            $changes['foto'] = ['de' => $item['foto'], 'para' => $image];
        }
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
            $changes['foto'] = ['de' => $item['foto'], 'para' => $imageName];
            $image = $imageName; // Atualiza o nome da imagem no banco de dados
        } else {
            echo "Erro ao processar a imagem.";
        }
    } else if ($permite_foto == 0) {
        // Se a categoria não permite foto, sempre zera a foto
        $image = $category['imagem_categoria'] ?? 'default.webp';
    }

    // Atualizar o item no banco de dados
    $sql = "UPDATE itens SET nome = ?, descricao = ?, categoria_id = ?, foto = ?, updated_at = NOW(), updated_by = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$name, $description, $category_id, $image, $updated_by, $id]);

    // Log de edição de item
    logAction($pdo, [
        'user_id'     => $updated_by,
        'entity_id'   => $id,
        'entity_type' => 'item',
        'action'      => 'edit_item',
        'reason'      => 'Item editado: ' . $name,
        'changes'     => !empty($changes) ? json_encode($changes, JSON_UNESCAPED_UNICODE) : null,
        'status'      => 'success',
        'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);

    header("Location: list_items.php");
    exit();
}

// Obter categorias para o campo de seleção
$category_stmt = $pdo->prepare("SELECT * FROM categorias WHERE is_deleted = FALSE");
$category_stmt->execute();
$categories = $category_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Achados e Perdidos - Editar Item</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>Editar Item</h1>

        <form method="POST" enctype="multipart/form-data">
            <label for="name">Nome:</label>
            <input type="text" name="name" id="name" value="<?php echo htmlspecialchars($item['nome']); ?>" required>

            <label for="description">Descrição:</label>
            <input type="text" name="description" id="description" value="<?php echo htmlspecialchars($item['descricao']); ?>" required>

            <label for="category_id">Categoria:</label>
            <select name="category_id" id="category_id" required>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo $category['id']; ?>" <?php echo ($category['id'] == $item['categoria_id']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($category['nome']); ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <!-- Exibir imagem atual -->
            <label>Imagem Atual:</label>
            <img src="../uploads/<?php echo htmlspecialchars($item['foto']); ?>" alt="Imagem Atual" width="150" onclick="openModal(this.src)" style="cursor: pointer;" />

            <label for="image">Imagem (deixe em branco para manter a atual):</label>
            <input type="file" name="image" id="image" accept="image/*">
            <small>Tipos permitidos: JPG, PNG, GIF, WEBP, BMP. Tamanho máximo: 10MB.</small>
            <script>
            // Desabilita o campo de imagem se a categoria não permitir foto
            const categorySelect = document.getElementById('category_id');
            const imageInput = document.getElementById('image');
            const categories = <?php echo json_encode($categories); ?>;
            function updateImageInput() {
                const selected = categories.find(c => c.id == categorySelect.value);
                if (selected && selected.permite_foto == 0) {
                    imageInput.disabled = true;
                    imageInput.value = '';
                    imageInput.title = 'Esta categoria não permite cadastro de fotos.';
                } else {
                    imageInput.disabled = false;
                    imageInput.title = '';
                }
            }
            categorySelect.addEventListener('change', updateImageInput);
            window.addEventListener('DOMContentLoaded', updateImageInput);
            </script>

            <!-- Adiciona aviso dinâmico se a categoria não permite foto -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const categorySelect = document.getElementById('category_id');
                    const imageInput = document.getElementById('image');
                    const avisoDiv = document.createElement('div');
                    avisoDiv.id = 'aviso-permite-foto';
                    avisoDiv.style.color = 'red';
                    imageInput.parentNode.insertBefore(avisoDiv, imageInput.nextSibling);

                    // Mapeia categorias e se permitem foto
                    const categoriasPermiteFoto = {};
                    <?php foreach ($categories as $cat): ?>
                        categoriasPermiteFoto[<?php echo $cat['id']; ?>] = <?php echo $cat['permite_foto'] ? 'true' : 'false'; ?>;
                    <?php endforeach; ?>

                    function atualizarAviso() {
                        const selected = categorySelect.value;
                        if (categoriasPermiteFoto[selected] === false) {
                            avisoDiv.textContent = 'Atenção: Esta categoria não permite o cadastro de fotos.';
                            imageInput.disabled = true;
                        } else {
                            avisoDiv.textContent = '';
                            imageInput.disabled = false;
                        }
                    }
                    categorySelect.addEventListener('change', atualizarAviso);
                    atualizarAviso();
                });
            </script>

            <div class="button-container">
                <button type="submit" class="save-button">Salvar Alterações</button>
                <a href="list_items.php" class="cancel-button">Cancelar</a>
            </div>
        </form>
    </div>
    <?php include '../utils/image_modal.php'; ?>
</body>
</html>
