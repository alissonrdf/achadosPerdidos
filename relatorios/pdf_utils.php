<?php
/**
 * Funções utilitárias para geração de arquivos PDF
 */

/**
 * Gera um PDF a partir dos logs do sistema com os filtros aplicados
 * 
 * @param PDO $pdo Conexão com o banco de dados
 * @param array $filters Filtros aplicados aos logs
 * @return string Caminho do arquivo PDF gerado
 */
function generateLogReportPDF(PDO $pdo, array $filters = []) {
    // Essa função usa classes básicas para não depender de bibliotecas externas
    
    // Inicia o buffer de saída para capturar o conteúdo HTML
    ob_start();
    
    // Constrói a consulta SQL com base nos filtros
    $whereClause = [];
    $params = [];
    
    // Filtro por tipo de entidade
    if (!empty($filters['entity_type'])) {
        $whereClause[] = "entity_type = ?";
        $params[] = $filters['entity_type'];
    }

    // Filtro por ação
    if (!empty($filters['action'])) {
        $whereClause[] = "action = ?";
        $params[] = $filters['action'];
    }

    // Filtro por status
    if (!empty($filters['status'])) {
        $whereClause[] = "status = ?";
        $params[] = $filters['status'];
    }

    // Filtro por usuário
    if (!empty($filters['user_id'])) {
        $whereClause[] = "user_id = ?";
        $params[] = $filters['user_id'];
    }

    // Filtro por data inicial
    if (!empty($filters['date_from'])) {
        $whereClause[] = "created_at >= ?";
        $params[] = $filters['date_from'] . ' 00:00:00';
    }

    // Filtro por data final
    if (!empty($filters['date_to'])) {
        $whereClause[] = "created_at <= ?";
        $params[] = $filters['date_to'] . ' 23:59:59';
    }

    // Construção da cláusula WHERE
    $where = '';
    if (!empty($whereClause)) {
        $where = ' WHERE ' . implode(' AND ', $whereClause);
    }

    // Consulta SQL para obter os logs
    $sql = "SELECT l.*, u.username FROM logs l 
            LEFT JOIN usuarios u ON l.user_id = u.id" . 
            $where . 
            " ORDER BY l.created_at DESC LIMIT 1000"; // Limitando a 1000 registros para evitar PDFs muito grandes
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Cabeçalho HTML para o PDF
    echo '<!DOCTYPE html>
    <html>
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Logs do Sistema</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 20px; }
            h1 { text-align: center; color: #333; }
            .filters { background-color: #f5f5f5; padding: 10px; border-radius: 5px; margin-bottom: 20px; }
            table { width: 100%; border-collapse: collapse; }
            th, td { border: 1px solid #ddd; padding: 8px; font-size: 12px; }
            th { background-color: #f2f2f2; text-align: left; }
            tr:nth-child(even) { background-color: #f9f9f9; }
            .success { color: green; }
            .error { color: red; }
            .footer { text-align: center; font-size: 12px; margin-top: 20px; }
        </style>
    </head>
    <body>';
    
    // Título e data do relatório
    echo '<h1>Relatório de Logs do Sistema</h1>';
    echo '<p style="text-align: center;">Gerado em: ' . date('d/m/Y H:i:s') . '</p>';
    
    // Filtros aplicados
    echo '<div class="filters"><h3>Filtros Aplicados:</h3><ul>';
    if (!empty($filters['entity_type'])) echo '<li>Tipo de Entidade: ' . htmlspecialchars($filters['entity_type']) . '</li>';
    if (!empty($filters['action'])) echo '<li>Ação: ' . htmlspecialchars($filters['action']) . '</li>';
    if (!empty($filters['status'])) echo '<li>Status: ' . htmlspecialchars($filters['status']) . '</li>';
    if (!empty($filters['user_id'])) {
        // Obter nome do usuário
        $userStmt = $pdo->prepare("SELECT username FROM usuarios WHERE id = ?");
        $userStmt->execute([$filters['user_id']]);
        $userName = $userStmt->fetchColumn();
        echo '<li>Usuário: ' . htmlspecialchars($userName) . '</li>';
    }
    if (!empty($filters['date_from'])) echo '<li>Data inicial: ' . htmlspecialchars($filters['date_from']) . '</li>';
    if (!empty($filters['date_to'])) echo '<li>Data final: ' . htmlspecialchars($filters['date_to']) . '</li>';
    if (empty($filters)) echo '<li>Nenhum filtro aplicado</li>';
    echo '</ul></div>';
    
    // Tabela de logs
    if (count($logs) > 0) {
        echo '<table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data/Hora</th>
                    <th>Usuário</th>
                    <th>Tipo</th>
                    <th>ID Entidade</th>
                    <th>Ação</th>
                    <th>Descrição</th>
                    <th>Alterações</th>
                    <th>Status</th>
                    <th>IP</th>
                </tr>
            </thead>
            <tbody>';
        
        foreach ($logs as $log) {
            $changes = '';
            if (!empty($log['changes'])) {
                $changesData = json_decode($log['changes'], true);
                if (json_last_error() === JSON_ERROR_NONE && is_array($changesData)) {
                    foreach ($changesData as $field => $change) {
                        if (is_array($change) && isset($change['de']) && isset($change['para'])) {
                            $changes .= "$field: {$change['de']} → {$change['para']}\n";
                        } else {
                            $changes .= "$field: $change\n";
                        }
                    }
                } else {
                    $changes = $log['changes'];
                }
            }
            
            echo '<tr>
                <td>' . $log['id'] . '</td>
                <td>' . $log['created_at'] . '</td>
                <td>' . htmlspecialchars($log['username'] ?? 'Desconhecido') . '</td>
                <td>' . htmlspecialchars($log['entity_type'] ?? 'N/A') . '</td>
                <td>' . ($log['entity_id'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($log['action'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($log['reason'] ?? '') . '</td>
                <td>' . nl2br(htmlspecialchars($changes)) . '</td>
                <td class="' . $log['status'] . '">' . htmlspecialchars($log['status'] ?? 'N/A') . '</td>
                <td>' . htmlspecialchars($log['ip_address'] ?? 'N/A') . '</td>
            </tr>';
        }
        
        echo '</tbody></table>';
    } else {
        echo '<p>Nenhum registro encontrado com os filtros aplicados.</p>';
    }
    
    echo '<div class="footer">Sistema de Achados e Perdidos - Página 1</div>';
    echo '</body></html>';
    
    // Captura o conteúdo HTML
    $html = ob_get_clean();
      // Gera um nome único para o arquivo PDF
    $filename = 'logs_' . date('Y-m-d_His') . '.pdf';
    $output = __DIR__ . '/../logs/' . $filename;
    
    // Verifica se o diretório logs existe, caso contrário cria
    if (!file_exists(__DIR__ . '/../logs')) {
        mkdir(__DIR__ . '/../logs', 0755, true);
    }    // Método simplificado para gerar PDF
    // Salva o HTML em um arquivo e deixa o navegador/usuário lidar com a visualização
    file_put_contents($output, $html);
    
    // Em um ambiente de produção com as bibliotecas corretas, você poderia usar:
    /*
    // Essa parte requer que o TCPDF seja instalado via Composer
    if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
        require_once __DIR__ . '/../vendor/autoload.php';
        if (class_exists('\\TCPDF')) {
            $pdf = new \TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
            $pdf->SetCreator('Sistema de Achados e Perdidos');
            $pdf->SetAuthor('Administrador do Sistema');
            $pdf->SetTitle('Relatório de Logs');
            $pdf->SetHeaderData('', 0, 'Relatório de Logs do Sistema', '');
            $pdf->setHeaderFont(Array('helvetica', '', 12));
            $pdf->setFooterFont(Array('helvetica', '', 10));
            $pdf->SetDefaultMonospacedFont('courier');
            $pdf->SetMargins(15, 15, 15);
            $pdf->SetHeaderMargin(10);
            $pdf->SetFooterMargin(10);
            $pdf->SetAutoPageBreak(TRUE, 15);
            $pdf->AddPage();
            $pdf->writeHTML($html, true, false, true, false, '');
            $pdf->Output($output, 'F');
            return $filename;
        }
    }
    */
    
    return $filename;
}

/**
 * Converte uma string JSON de alterações em texto formatado para exibição
 * 
 * @param string|null $changesJson String JSON com as alterações
 * @return string Texto formatado das alterações
 */
function formatChanges($changesJson) {
    if (empty($changesJson)) {
        return 'N/A';
    }
    
    $changes = json_decode($changesJson, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
        return htmlspecialchars($changesJson);
    }
    
    $result = '';
    foreach ($changes as $field => $change) {
        if (is_array($change)) {
            if (isset($change['de']) && isset($change['para'])) {
                $result .= "<b>$field</b>: <span class='change'>{$change['de']} → {$change['para']}</span><br>";
            } else {
                $result .= "<b>$field</b>: " . json_encode($change) . "<br>";
            }
        } else {
            $result .= "<b>$field</b>: $change<br>";
        }
    }
    
    return $result;
}
