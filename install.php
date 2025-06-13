<?php
// Verifica se o sistema já foi instalado ao verificar a existência do config.php
if (file_exists('config.php')) {
    header('Location: index.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['install'])) {
    $dbHost = $_POST['db_host'];
    $dbName = $_POST['db_name'];
    $dbUser = $_POST['db_user'];
    $dbPass = $_POST['db_pass'];
    $adminUser = $_POST['admin_user'];
    $adminEmail = $_POST['admin_email'];
    $adminPass = $_POST['admin_pass'];

    // Configura o DSN para MySQL
    $dsn = "mysql:host=$dbHost";

    // Tenta se conectar ao banco de dados e executar o script SQL
    try {
        // Conexão com o banco de dados
        $pdo = new PDO($dsn, $dbUser, $dbPass);
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Criação do banco de dados
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `$dbName`");
        $pdo->exec("USE `$dbName`");

        // Ler o conteúdo do arquivo SQL
        $sqlFile = 'sql/database_setup.sql';
        $sqlContent = file_get_contents($sqlFile);
        
         // Executar as instruções SQL no banco de dados
        $pdo->exec($sqlContent);

        // Inserir o usuário administrador
        $passwordHash = password_hash($adminPass, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO usuarios (username, email, password_hash, role) VALUES (?, ?, ?, 'admin')");
        $stmt->execute([$adminUser, $adminEmail, $passwordHash]);

        // Inserir algumas categorias iniciais com imagens padrão
        $pdo->exec( "INSERT INTO categorias (nome, imagem_categoria, created_by) VALUES 
        ('Documentos', 'documentos.webp', 1),
        ('Eletrônicos', 'eletronicos.webp', 1),
        ('Acessórios', 'acessorios.webp', 1),
        ('Outros', 'outros.webp', 1);");

        // Gera o arquivo de configuração `config.php`
        $configContent = 
"<?php
    // Configuração do banco de dados
    define('DB_HOST', '$dbHost');
    define('DB_NAME', '$dbName');
    define('DB_USER', '$dbUser');
    define('DB_PASS', '$dbPass');

    // Caminho padrão para imagens de upload
    define('UPLOAD_DIR', 'uploads/');
    define('DEFAULT_IMAGE', 'default.webp');
?>";

        file_put_contents('config.php', $configContent);

        echo "<p>Instalação concluída com sucesso. <a href='index.php'>Ir para o sistema</a></p>";
        exit();
    } catch (PDOException $e) {
        echo "<p>Erro ao instalar: " . $e->getMessage() . "</p>";
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Instalação do Sistema de Achados e Perdidos</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <div class="container">
        <h1>Instalação do Sistema de Achados e Perdidos</h1>
        <form method="POST" action="install.php">
            <h2>Configuração do Banco de Dados</h2>
            <label for="db_host">Servidor do Banco de Dados:</label>
            <input type="text" name="db_host" id="db_host" required value="localhost">

            <label for="db_name">Nome do Banco de Dados:</label>
            <input type="text" name="db_name" id="db_name" required>

            <label for="db_user">Usuário do Banco de Dados:</label>
            <input type="text" name="db_user" id="db_user" required>

            <label for="db_pass">Senha do Banco de Dados:</label>
            <input type="password" name="db_pass" id="db_pass">

            <h2>Configuração do Usuário Administrador</h2>
            <label for="admin_user">Nome de Usuário do Admin:</label>
            <input type="text" name="admin_user" id="admin_user" required>

            <label for="admin_email">Email do Admin:</label>
            <input type="email" name="admin_email" id="admin_email" required>

            <label for="admin_pass">Senha do Admin:</label>
            <input type="password" name="admin_pass" id="admin_pass" required>  

            <button type="submit" name="install">Instalar</button>
        </form>
    </div>
</body>
</html>
