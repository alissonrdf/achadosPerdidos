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
    $description = $_POST['description'];
    $category_id = $_POST['category_id'];
    $created_by = $_SESSION['user_id'];

    // Buscar se a categoria permite foto
    $catStmt = $pdo->prepare("SELECT imagem_categoria, permite_foto FROM categorias WHERE id = ?");
    $catStmt->execute([$category_id]);
    $category = $catStmt->fetch();
    $permite_foto = $category ? $category['permite_foto'] : 1;

    if ($permite_foto && !empty($_FILES['image']['name'])) {
        // Validação do tipo de arquivo (apenas imagens)
        $allowedTypes = [
            'image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/bmp'
        ];
        $fileType = mime_content_type($_FILES['image']['tmp_name']);
        if (!in_array($fileType, $allowedTypes)) {
            echo "Tipo de arquivo não permitido. Envie apenas imagens (jpg, png, gif, webp, bmp).";
            exit();
        }
        // Validação do tamanho do arquivo (máx. 10MB)
        $maxFileSize = 10 * 1024 * 1024; // 10MB
        if ($_FILES['image']['size'] > $maxFileSize) {
            echo "O arquivo excede o tamanho máximo permitido de 10MB.";
            exit();
        }
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
        // Caso não haja imagem ou não permita foto, buscar a imagem padrão da categoria
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

    // Log de criação de item
    $item_id = $pdo->lastInsertId();
    logAction($pdo, [
        'user_id'     => $created_by,
        'entity_id'   => $item_id,
        'entity_type' => 'item',
        'action'      => 'create_item',
        'reason'      => 'Item criado: ' . $name,
        'changes'     => null,
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
            <input type="file" name="image" id="image" accept="image/*" />
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
            <div id="aviso-permite-foto" style="color: red;"></div>
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const categorySelect = document.getElementById('category_id');
                    const imageInput = document.getElementById('image');
                    const avisoDiv = document.getElementById('aviso-permite-foto');

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
                <button type="submit" class="save-button">Cadastrar Item</button>
                 <a href="list_items.php" class="cancel-button">Cancelar</a>
            </div>
        </form>

    </div>
</body>
</html>
