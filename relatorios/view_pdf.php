<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Verifica se um arquivo PDF foi especificado
if (!isset($_GET['file']) || empty($_GET['file'])) {
    echo 'Arquivo não especificado.';
    exit();
}

$filename = basename($_GET['file']);
$filepath = __DIR__ . '/../logs/' . $filename;

// Valida o nome do arquivo para segurança
if (!preg_match('/^logs_(friendly_|technical_)?[0-9]{4}-[0-9]{2}-[0-9]{2}_[0-9]{6}\.pdf$/', $filename)) {
    echo 'Nome de arquivo inválido.';
    exit();
}

// Verifica se o arquivo existe
if (!file_exists($filepath)) {
    echo 'Arquivo não encontrado.';
    exit();
}

// Define o tipo de conteúdo e cabeçalhos para forçar o download do PDF
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . filesize($filepath));

// Lê e envia o arquivo para o usuário
readfile($filepath);

// Apaga o arquivo após o envio
unlink($filepath);

exit;
?>
