<?php
session_start();
require_once 'db.php';
require_once 'utils/log_utils.php';

// Log de logout, se houver usuário logado
if (isset($_SESSION['user_id'])) {
    logAction($pdo, [
        'user_id'     => $_SESSION['user_id'],
        'entity_id'   => null,
        'entity_type' => null,
        'action'      => 'logout',
        'reason'      => 'Logout do usuário',
        'changes'     => null,
        'status'      => 'success',
        'ip_address'  => $_SERVER['REMOTE_ADDR'] ?? null,
        'user_agent'  => $_SERVER['HTTP_USER_AGENT'] ?? null
    ]);
}

// Verifica se há uma sessão ativa antes de destruí-la
if (session_status() === PHP_SESSION_ACTIVE) {
    // Limpa todas as variáveis de sessão
    $_SESSION = array();

    // Destrói o cookie da sessão no navegador, se existir
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }

    // Destrói a sessão
    session_destroy();
}

// Redireciona para a página de login
header("Location: login.php");
exit();
?>
