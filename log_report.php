<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

include 'db.php';
require_once 'utils/log_utils.php';
require_once 'utils/report_utils.php';

// Configuração de paginação
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$recordsPerPage = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = ($page - 1) * $recordsPerPage;

// Modo de visualização (normal ou amigável)
$viewMode = isset($_GET['view_mode']) ? $_GET['view_mode'] : 'normal';

// Parâmetros de filtragem
$filters = [];
$params = [];
$whereClause = [];

// Filtro por tipo de entidade
if (!empty($_GET['entity_type'])) {
    $whereClause[] = "entity_type = ?";
    $params[] = $_GET['entity_type'];
    $filters['entity_type'] = $_GET['entity_type'];
}

// Filtro por ação
if (!empty($_GET['action'])) {
    $whereClause[] = "action = ?";
    $params[] = $_GET['action'];
    $filters['action'] = $_GET['action'];
}

// Filtro por status
if (!empty($_GET['status'])) {
    $whereClause[] = "status = ?";
    $params[] = $_GET['status'];
    $filters['status'] = $_GET['status'];
}

// Filtro por usuário
if (!empty($_GET['user_id'])) {
    $whereClause[] = "user_id = ?";
    $params[] = (int)$_GET['user_id'];
    $filters['user_id'] = (int)$_GET['user_id'];
}

// Filtro por data inicial
if (!empty($_GET['date_from'])) {
    $whereClause[] = "created_at >= ?";
    $params[] = $_GET['date_from'] . ' 00:00:00';
    $filters['date_from'] = $_GET['date_from'];
}

// Filtro por data final
if (!empty($_GET['date_to'])) {
    $whereClause[] = "created_at <= ?";
    $params[] = $_GET['date_to'] . ' 23:59:59';
    $filters['date_to'] = $_GET['date_to'];
}

// Construção da cláusula WHERE
$where = '';
if (!empty($whereClause)) {
    $where = ' WHERE ' . implode(' AND ', $whereClause);
}

// Contagem total de registros para paginação
$countSql = "SELECT COUNT(*) FROM logs" . $where;
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalRecords = $countStmt->fetchColumn();
$totalPages = ceil($totalRecords / $recordsPerPage);

// Obtenção dos registros de log com paginação
$sql = "SELECT l.*, u.username FROM logs l 
        LEFT JOIN usuarios u ON l.user_id = u.id" . 
        $where . 
        " ORDER BY l.created_at DESC LIMIT $offset, $recordsPerPage";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Formatação dos logs para exibição amigável
if ($viewMode === 'friendly') {
    $logs = formatLogsForReport($logs);
}

// Lista de tipos de entidades para o filtro
$entityTypesSql = "SELECT DISTINCT entity_type FROM logs WHERE entity_type IS NOT NULL";
$entityTypesStmt = $pdo->prepare($entityTypesSql);
$entityTypesStmt->execute();
$entityTypes = $entityTypesStmt->fetchAll(PDO::FETCH_COLUMN);

// Lista de ações para o filtro
$actionsSql = "SELECT DISTINCT action FROM logs WHERE action IS NOT NULL";
$actionsStmt = $pdo->prepare($actionsSql);
$actionsStmt->execute();
$actions = $actionsStmt->fetchAll(PDO::FETCH_COLUMN);

// Lista de usuários para o filtro
$usersSql = "SELECT id, username FROM usuarios WHERE is_deleted = FALSE ORDER BY username";
$usersStmt = $pdo->prepare($usersSql);
$usersStmt->execute();
$users = $usersStmt->fetchAll();

// Estatísticas para o relatório amigável
$stats = [];
if ($viewMode === 'friendly') {
    // Total de logs por status
    $successLogsSql = "SELECT COUNT(*) FROM logs WHERE status = 'success'" . (!empty($whereClause) ? ' AND ' . implode(' AND ', $whereClause) : '');
    $errorLogsSql = "SELECT COUNT(*) FROM logs WHERE status = 'error'" . (!empty($whereClause) ? ' AND ' . implode(' AND ', $whereClause) : '');
    
    $successLogsStmt = $pdo->prepare($successLogsSql);
    $errorLogsStmt = $pdo->prepare($errorLogsSql);
    
    $successLogsStmt->execute($params);
    $errorLogsStmt->execute($params);
    
    $stats['success_logs'] = $successLogsStmt->fetchColumn();
    $stats['error_logs'] = $errorLogsStmt->fetchColumn();
    
    // Total de logs por tipo de ação
    $actionStatsSql = "SELECT action, COUNT(*) as count FROM logs" . $where . " GROUP BY action ORDER BY count DESC";
    $actionStatsStmt = $pdo->prepare($actionStatsSql);
    $actionStatsStmt->execute($params);
    $stats['actions'] = $actionStatsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Total de logs por usuário
    $userStatsSql = "SELECT u.username, COUNT(*) as count 
                     FROM logs l 
                     JOIN usuarios u ON l.user_id = u.id" . 
                     $where . 
                     " GROUP BY l.user_id ORDER BY count DESC LIMIT 5";
    $userStatsStmt = $pdo->prepare($userStatsSql);
    $userStatsStmt->execute($params);
    $stats['users'] = $userStatsStmt->fetchAll(PDO::FETCH_KEY_PAIR);
}

// Verifica se é uma solicitação de exportação CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    // Construção da consulta SQL sem limite de paginação
    $exportSql = "SELECT l.*, u.username 
                  FROM logs l 
                  LEFT JOIN usuarios u ON l.user_id = u.id" . $where . 
                 " ORDER BY l.created_at DESC";
    
    $exportStmt = $pdo->prepare($exportSql);
    $exportStmt->execute($params);
    $exportData = $exportStmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Para o modo amigável, formata os logs antes de exportar
    if ($viewMode === 'friendly') {
        $exportData = formatLogsForReport($exportData);
    }
    
    // Usa a função de exportação CSV com o modo de visualização correto
    exportLogsToCSV($exportData, $viewMode);
    exit;
}

// Verifica se é uma solicitação de exportação PDF
if (isset($_GET['export']) && $_GET['export'] === 'pdf') {
    // Obtém todos os logs para o PDF (sem paginação)
    $pdfSql = "SELECT l.*, u.username FROM logs l 
              LEFT JOIN usuarios u ON l.user_id = u.id" . 
              $where . 
              " ORDER BY l.created_at DESC";
    $pdfStmt = $pdo->prepare($pdfSql);
    $pdfStmt->execute($params);
    $pdfLogs = $pdfStmt->fetchAll();
      // Gera o PDF com o modo de visualização correto
    if ($viewMode === 'friendly') {
        $pdfPath = generateFriendlyLogPDF($pdfLogs, $filters);
    } else {
        $pdfPath = generateLogPDF($pdfLogs, $filters);
    }
    
    // Retorna o URL do PDF para ser aberto em uma nova aba
    $pdfUrl = "view_pdf.php?file=" . basename($pdfPath);
    echo json_encode(['url' => $pdfUrl]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Achados e Perdidos - Relatório de Logs</title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/report.css">
</head>
<body>
    <div class="report-container <?= $viewMode === 'friendly' ? 'friendly-view' : '' ?>">
        <div class="report-header">
            <h1 class="report-title">Relatório de Logs do Sistema</h1>
            <div class="report-actions">
                <a href="dashboard.php" class="btn btn-secondary">← Voltar ao Painel</a>
                <a href="?<?= http_build_query(array_merge($filters, ['view_mode' => $viewMode === 'friendly' ? 'normal' : 'friendly'])) ?>" class="btn btn-primary">
                    Alternar para Modo <?= $viewMode === 'friendly' ? 'Normal' : 'Amigável' ?>
                </a>
            </div>
        </div>
        
        <div class="report-filters">
            <h2>Filtros</h2>
            <form method="GET" action="log_report.php">
                <input type="hidden" name="view_mode" value="<?= htmlspecialchars($viewMode) ?>">
                
                <div class="filter-group">
                    <label for="entity_type">Tipo de Entidade:</label>
                    <select name="entity_type" id="entity_type">
                        <option value="">Todos</option>
                        <?php foreach ($entityTypes as $type): ?>
                            <option value="<?= htmlspecialchars($type) ?>" <?= isset($filters['entity_type']) && $filters['entity_type'] === $type ? 'selected' : '' ?>>
                                <?= htmlspecialchars($type) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="action">Ação:</label>
                    <select name="action" id="action">
                        <option value="">Todas</option>
                        <?php foreach ($actions as $action): ?>
                            <option value="<?= htmlspecialchars($action) ?>" <?= isset($filters['action']) && $filters['action'] === $action ? 'selected' : '' ?>>
                                <?= htmlspecialchars($action) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="status">Status:</label>
                    <select name="status" id="status">
                        <option value="">Todos</option>
                        <option value="success" <?= isset($filters['status']) && $filters['status'] === 'success' ? 'selected' : '' ?>>Sucesso</option>
                        <option value="error" <?= isset($filters['status']) && $filters['status'] === 'error' ? 'selected' : '' ?>>Erro</option>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="user_id">Usuário:</label>
                    <select name="user_id" id="user_id">
                        <option value="">Todos</option>
                        <?php foreach ($users as $user): ?>
                            <option value="<?= $user['id'] ?>" <?= isset($filters['user_id']) && $filters['user_id'] === (int)$user['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($user['username']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="filter-group">
                    <label for="date_from">Data inicial:</label>
                    <input type="date" name="date_from" id="date_from" value="<?= $filters['date_from'] ?? '' ?>">
                </div>
                
                <div class="filter-group">
                    <label for="date_to">Data final:</label>
                    <input type="date" name="date_to" id="date_to" value="<?= $filters['date_to'] ?? '' ?>">
                </div>
                
                <div class="filter-group">
                    <label for="limit">Registros por página:</label>
                    <select name="limit" id="limit">
                        <option value="20" <?= $recordsPerPage === 20 ? 'selected' : '' ?>>20</option>
                        <option value="50" <?= $recordsPerPage === 50 ? 'selected' : '' ?>>50</option>
                        <option value="100" <?= $recordsPerPage === 100 ? 'selected' : '' ?>>100</option>
                    </select>
                </div>
                
                <div class="filter-buttons">
                    <button type="submit" class="btn btn-primary">Filtrar</button>
                    <button type="reset" onclick="window.location='log_report.php?view_mode=<?= $viewMode ?>'" class="btn btn-secondary">Limpar</button>
                </div>
            </form>
        </div>
        
        <?php if ($viewMode === 'friendly' && !empty($stats)): ?>
        <div class="summary-box">
            <h2 class="summary-title">Resumo</h2>
            <div class="summary-items">
                <div class="summary-item summary-item-success">
                    <div class="summary-item-label">Operações com Sucesso</div>
                    <div class="summary-item-value"><?= $stats['success_logs'] ?></div>
                </div>
                <div class="summary-item summary-item-error">
                    <div class="summary-item-label">Operações com Erro</div>
                    <div class="summary-item-value"><?= $stats['error_logs'] ?></div>
                </div>
                <div class="summary-item">
                    <div class="summary-item-label">Total de Registros</div>
                    <div class="summary-item-value"><?= $totalRecords ?></div>
                </div>
                <?php if (!empty($stats['actions'])): ?>
                <div class="summary-item">
                    <div class="summary-item-label">Ação Mais Comum</div>
                    <div class="summary-item-value">
                        <?php 
                        $topAction = array_key_first($stats['actions']);
                        echo htmlspecialchars($topAction);
                        ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
          <div class="export-options">
            <a href="<?= $_SERVER['REQUEST_URI'] . (strpos($_SERVER['REQUEST_URI'], '?') ? '&' : '?') ?>export=csv" class="btn btn-success">
                Exportar CSV <?= $viewMode === 'friendly' ? '(Amigável)' : '(Normal)' ?>
            </a>            <a href="#" class="btn btn-danger" onclick="exportPDF(event)">
                Exportar PDF <?= $viewMode === 'friendly' ? '(Amigável)' : '(Normal)' ?>
            </a>
            
            <script>
            function exportPDF(event) {
                event.preventDefault();
                // Obter a URL atual e adicionar o parâmetro export=pdf
                const baseUrl = window.location.href;
                const separator = baseUrl.includes('?') ? '&' : '?';
                const exportUrl = `${baseUrl}${separator}export=pdf`;
                
                // Fazer uma requisição AJAX para obter o URL do PDF
                fetch(exportUrl)
                    .then(response => response.json())
                    .then(data => {
                        // Abrir o PDF em uma nova guia
                        window.open(data.url, '_blank');
                    })
                    .catch(error => {
                        console.error('Erro ao exportar PDF:', error);
                        alert('Ocorreu um erro ao exportar o PDF. Por favor, tente novamente.');
                    });
            }
            </script>
        </div>
        
        <?php if ($totalRecords > 0): ?>
            <p>Exibindo <?= min($recordsPerPage, $totalRecords) ?> de <?= $totalRecords ?> registros encontrados.</p>
            
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1<?= !empty($filters) ? '&' . http_build_query(array_merge($filters, ['limit' => $recordsPerPage, 'view_mode' => $viewMode])) : '' ?>">&laquo; Primeira</a>
                    <a href="?page=<?= $page - 1 ?><?= !empty($filters) ? '&' . http_build_query(array_merge($filters, ['limit' => $recordsPerPage, 'view_mode' => $viewMode])) : '' ?>">&lsaquo; Anterior</a>
                <?php endif; ?>
                
                <?php
                // Mostrar no máximo 5 links de página
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $startPage + 4);
                
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?><?= !empty($filters) ? '&' . http_build_query(array_merge($filters, ['limit' => $recordsPerPage, 'view_mode' => $viewMode])) : '' ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?><?= !empty($filters) ? '&' . http_build_query(array_merge($filters, ['limit' => $recordsPerPage, 'view_mode' => $viewMode])) : '' ?>">Próxima &rsaquo;</a>
                    <a href="?page=<?= $totalPages ?><?= !empty($filters) ? '&' . http_build_query(array_merge($filters, ['limit' => $recordsPerPage, 'view_mode' => $viewMode])) : '' ?>">Última &raquo;</a>
                <?php endif; ?>
            </div>
            
            <table class="report-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data/Hora</th>
                        <th>Usuário</th>
                        <?php if ($viewMode === 'normal'): ?>
                            <th>Tipo</th>
                            <th>ID Entidade</th>
                            <th>Ação</th>
                        <?php else: ?>
                            <th>Ação</th>
                        <?php endif; ?>
                        <th>Descrição</th>
                        <th>Alterações</th>
                        <th>Status</th>
                        <?php if ($viewMode === 'normal'): ?>
                            <th>IP</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?= $log['id'] ?></td>
                        <td>
                            <?php if ($viewMode === 'friendly' && isset($log['formatted_date']) && isset($log['formatted_time'])): ?>
                                <div><?= $log['formatted_date'] ?></div>
                                <div style="font-size: 0.85em; color: #6c757d;"><?= $log['formatted_time'] ?></div>
                            <?php else: ?>
                                <?= $log['created_at'] ?>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($log['username'] ?? 'Desconhecido') ?></td>
                        
                        <?php if ($viewMode === 'normal'): ?>
                            <td><?= htmlspecialchars($log['entity_type'] ?? 'N/A') ?></td>
                            <td><?= $log['entity_id'] ?? 'N/A' ?></td>
                            <td><?= htmlspecialchars($log['action'] ?? 'N/A') ?></td>
                        <?php else: ?>
                            <td class="action-cell">
                                <?= htmlspecialchars($log['action_label'] ?? 'Ação desconhecida') ?>
                            </td>
                        <?php endif; ?>
                        
                        <td><?= htmlspecialchars($log['reason'] ?? '') ?></td>
                        
                        <td class="changes-cell">
                            <?php if (!empty($log['changes'])): ?>
                                <div class="changes-preview">
                                    <?= substr(htmlspecialchars($log['changes']), 0, 30) . (strlen($log['changes']) > 30 ? '...' : '') ?>
                                </div>                                <div class="changes-details">
                                    <?php
                                        echo nl2br(htmlspecialchars(formatChangesText($log['changes'])));
                                    ?>
                                </div>
                            <?php else: ?>
                                <span>N/A</span>
                            <?php endif; ?>
                        </td>
                        
                        <td class="status-<?= $log['status'] ?>">
                            <?php if ($viewMode === 'friendly'): ?>
                                <?= htmlspecialchars($log['status_label'] ?? 'N/A') ?>
                            <?php else: ?>
                                <?= htmlspecialchars($log['status'] ?? 'N/A') ?>
                            <?php endif; ?>
                        </td>
                        
                        <?php if ($viewMode === 'normal'): ?>
                            <td><?= htmlspecialchars($log['ip_address'] ?? 'N/A') ?></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <div class="pagination">
                <?php if ($page > 1): ?>
                    <a href="?page=1<?= !empty($filters) ? '&' . http_build_query(array_merge($filters, ['limit' => $recordsPerPage, 'view_mode' => $viewMode])) : '' ?>">&laquo; Primeira</a>
                    <a href="?page=<?= $page - 1 ?><?= !empty($filters) ? '&' . http_build_query(array_merge($filters, ['limit' => $recordsPerPage, 'view_mode' => $viewMode])) : '' ?>">&lsaquo; Anterior</a>
                <?php endif; ?>
                
                <?php
                // Mostrar no máximo 5 links de página
                $startPage = max(1, $page - 2);
                $endPage = min($totalPages, $startPage + 4);
                
                for ($i = $startPage; $i <= $endPage; $i++):
                ?>
                    <?php if ($i == $page): ?>
                        <span class="current"><?= $i ?></span>
                    <?php else: ?>
                        <a href="?page=<?= $i ?><?= !empty($filters) ? '&' . http_build_query(array_merge($filters, ['limit' => $recordsPerPage, 'view_mode' => $viewMode])) : '' ?>"><?= $i ?></a>
                    <?php endif; ?>
                <?php endfor; ?>
                
                <?php if ($page < $totalPages): ?>
                    <a href="?page=<?= $page + 1 ?><?= !empty($filters) ? '&' . http_build_query(array_merge($filters, ['limit' => $recordsPerPage, 'view_mode' => $viewMode])) : '' ?>">Próxima &rsaquo;</a>
                    <a href="?page=<?= $totalPages ?><?= !empty($filters) ? '&' . http_build_query(array_merge($filters, ['limit' => $recordsPerPage, 'view_mode' => $viewMode])) : '' ?>">Última &raquo;</a>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>Nenhum registro de log encontrado com os filtros aplicados.</p>
        <?php endif; ?>
    </div>
</body>
</html>
