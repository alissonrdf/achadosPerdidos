<?php
session_start();
include 'db.php';
require_once 'utils/log_utils.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Consulta o usuário ativo no banco de dados
    $stmt = $pdo->prepare("SELECT * FROM usuarios WHERE username = ? AND is_deleted = FALSE");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['password_hash'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_role'] = $user['role']; // Salva o papel do usuário na sessão
        // Log de login
        registerLog($pdo, $user['id'], 0, 'login', 'Login realizado');
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Usuário ou senha incorretos.";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Achados e Perdidos - Login</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Login</h1>

        <!-- Formulário de Login -->
        <form method="POST" action="login.php">
            <label for="username">Usuário:</label>
            <input type="text" name="username" id="username" required>

            <label for="password">Senha:</label>
            <input type="password" name="password" id="password" required>

            <button type="submit" class="button-link">Entrar</button>
        </form>

        <!-- Exibir mensagem de erro, se houver -->
        <?php if ($error): ?>
            <p style="color: red;"><?php echo $error; ?></p>
        <?php endif; ?>

        <div class="actions">
            <a href="index.php">Voltar para a Página Inicial</a>
        </div>
    </div>
</body>
</html>
