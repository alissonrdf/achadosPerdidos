<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

include '../db.php';
require_once '../utils/log_utils.php'; // Para registrar logs

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role']; // Captura o papel do usuário

    // Hash seguro para a senha
    $password_hash = password_hash($password, PASSWORD_BCRYPT);

    // Inserir o usuário no banco de dados com o papel especificado
    $sql = "INSERT INTO usuarios (username, email, password_hash, role) VALUES (?, ?, ?, ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$username, $email, $password_hash, $role]);

    // Obter o ID do usuário recém-criado
    $newUserId = $pdo->lastInsertId();

    // Log de criação de usuário
    logAction($pdo, [
        'user_id'     => $_SESSION['user_id'],
        'entity_id'   => $newUserId,
        'entity_type' => 'usuario',
        'action'      => 'create_user',
        'reason'      => 'Usuário criado: ' . $username,
        'changes'     => null,
        'status'      => 'success',
        'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);

    header("Location: list_users.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Achados e Perdidos - Adicionar Usuário</title>
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="container">
        <h1>Adicionar Novo Usuário</h1>

        <form method="POST">
            <label for="username">Nome de Usuário:</label>
            <input type="text" name="username" id="username" required>

            <label for="email">Email:</label>
            <input type="email" name="email" id="email" required>

            <label for="password">Senha:</label>
            <input type="password" name="password" id="password" required>

            <label for="role">Papel:</label>
            <select name="role" id="role" required>
                <option value="user">Usuário Comum</option>
                <option value="admin">Administrador</option>
            </select>

            <div class="button-container">
                <button type="submit" class="save-button">Cadastrar Usuário</button>
                 <a href="list_users.php" class="cancel-button">Cancelar</a>
            </div>
        </form>

    </div>
</body>
</html>
