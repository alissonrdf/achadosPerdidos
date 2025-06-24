<?php
require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';

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
    
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    $pdf->SetCreator('Sistema de Achados e Perdidos');
    $pdf->SetAuthor('Sistema de Achados e Perdidos');
    $pdf->SetTitle('Relatório de Logs do Sistema');
    $pdf->SetMargins(10, 15, 10);
    $pdf->AddPage();
    $pdf->SetFont('helvetica', '', 10);

    $html = '<h2 style="text-align:center;">Relatório de Logs do Sistema</h2>';
    $html .= '<p style="text-align:center;">Gerado em: ' . date('d/m/Y H:i:s') . '</p>';
    $html .= '<table border="1" cellpadding="4" cellspacing="0"><thead><tr>';
    $html .= '<th>ID</th><th>Data</th><th>Hora</th><th>Usuário</th><th>Ação</th><th>Entidade</th><th>Motivo</th><th>Status</th><th>Alterações</th></tr></thead><tbody>';
    foreach ($logs as $log) {
        $date = date('d/m/Y', strtotime($log['created_at']));
        $time = date('H:i:s', strtotime($log['created_at']));
        $changes = '';
        if (!empty($log['changes'])) {
            $decoded = json_decode($log['changes'], true);
            if (is_array($decoded)) {
                foreach ($decoded as $field => $change) {
                    if (is_array($change) && isset($change['de']) && isset($change['para'])) {
                        $changes .= "$field: {$change['de']} → {$change['para']}<br>";
                    } else {
                        $changes .= "$field: $change<br>";
                    }
                }
            } else {
                $changes = htmlspecialchars($log['changes']);
            }
        } else {
            $changes = 'N/A';
        }
        $html .= '<tr>';
        $html .= '<td>' . $log['id'] . '</td>';
        $html .= '<td>' . $date . '</td>';
        $html .= '<td>' . $time . '</td>';
        $html .= '<td>' . htmlspecialchars($log['username']) . '</td>';
        $html .= '<td>' . htmlspecialchars($log['action']) . '</td>';
        $html .= '<td>' . htmlspecialchars($log['entity_type']) . '</td>';
        $html .= '<td>' . htmlspecialchars($log['reason']) . '</td>';
        $html .= '<td>' . htmlspecialchars(ucfirst($log['status'])) . '</td>';
        $html .= '<td>' . $changes . '</td>';
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';

    $pdf->writeHTML($html, true, false, true, false, '');

    $filename = 'logs_' . (isset($filters['view_mode']) && $filters['view_mode'] === 'friendly' ? 'friendly_' : 'technical_') . date('Y-m-d_His') . '.pdf';
    $filePath = __DIR__ . '/../temp/' . $filename;
    $pdf->Output($filePath, 'F');
    return $filePath;
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
