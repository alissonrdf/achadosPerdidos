<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../db.php';

$id = $_GET['id'];
$sql = "SELECT * FROM usuarios WHERE id = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$id]);
$usuario = $stmt->fetch();

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $updated_at = date('Y-m-d H:i:s');
    $password_hash = $usuario['password_hash'];
    $role = $_POST['role']; // Captura o papel do usuário

    if (!empty($_POST['password'])) {
        $password_hash = password_hash($_POST['password'], PASSWORD_BCRYPT);
    }

    // Atualizar usuário
    $sql = "UPDATE usuarios SET username = ?, email = ?, password_hash = ?, role = ?, updated_at = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username, $email, $password_hash, $role, $updated_at, $id]);

    header("Location: list_users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Achados e Perdidos - Editar Usuário</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>Editar Usuário</h1>

        <form method="POST">
            <label for="username">Nome de Usuário:</label>
            <input type="text" name="username" id="username" value="<?php echo htmlspecialchars($usuario['username']); ?>" required>

            <label for="email">Email:</label>
            <input type="email" name="email" id="email" value="<?php echo htmlspecialchars($usuario['email']); ?>" required>

            <label for="password">Senha (deixe em branco para manter a atual):</label>
            <input type="password" name="password" id="password">

            <label for="role">Papel:</label>
            <select name="role" id="role" required>
                <option value="user" <?php if ($usuario['role'] === 'user') echo 'selected'; ?>>Usuário Comum</option>
                <option value="admin" <?php if ($usuario['role'] === 'admin') echo 'selected'; ?>>Administrador</option>
            </select>

            <div class="button-container">
                <button type="submit" class="save-button">Salvar Alterações</button>
                 <a href="list_users.php" class="cancel-button">Cancelar</a>
            </div>
        </form>

    </div>
</body>
</html>
