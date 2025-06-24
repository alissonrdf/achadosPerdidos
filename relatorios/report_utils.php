<?php
/**
 * Utilitários para geração de relatórios em diferentes formatos
 */

/**
 * Formata texto de alterações para um formato mais legível
 * 
 * @param string $changesJson JSON das alterações
 * @return string Texto formatado das alterações
 */
function formatChangesText($changesJson) {
    if (empty($changesJson)) {
        return 'N/A';
    }
    
    $changes = json_decode($changesJson, true);
    if (json_last_error() !== JSON_ERROR_NONE || !is_array($changes)) {
        return $changesJson;
    }
    
    $changesText = '';
    foreach ($changes as $field => $change) {
        if (is_array($change) && isset($change['de']) && isset($change['para'])) {
            $changesText .= "$field: {$change['de']} → {$change['para']}\n";
        } else {
            $changesText .= "$field: $change\n";
        }
    }
    
    return $changesText;
}

/**
 * Formata um array de logs para exibição amigável em relatórios
 * 
 * @param array $logs Registros de log
 * @return array Logs formatados com descrições amigáveis
 */
function formatLogsForReport($logs) {
    $formattedLogs = [];
    
    // Mapeia ações para descrições mais amigáveis
    $actionLabels = [
        'login' => 'Login no sistema',
        'logout' => 'Logout do sistema',
        'create_user' => 'Criação de usuário',
        'edit_user' => 'Edição de usuário',
        'delete_user' => 'Exclusão de usuário',
        'create_category' => 'Criação de categoria',
        'edit_category' => 'Edição de categoria',
        'delete_category' => 'Exclusão de categoria',
        'create_item' => 'Criação de item',
        'edit_item' => 'Edição de item',
        'delete_item' => 'Exclusão de item'
    ];
    
    foreach ($logs as $log) {
        $formattedLog = $log;
        
        // Substitui códigos de ação por descrições amigáveis
        if (isset($log['action']) && isset($actionLabels[$log['action']])) {
            $formattedLog['action_label'] = $actionLabels[$log['action']];
        } else {
            $formattedLog['action_label'] = $log['action'] ?? 'Ação desconhecida';
        }
        
        // Formata status para exibição
        $formattedLog['status_label'] = ucfirst($log['status'] ?? 'desconhecido');
        
        // Formata timestamp
        if (isset($log['created_at'])) {
            $date = new DateTime($log['created_at']);
            $formattedLog['formatted_date'] = $date->format('d/m/Y');
            $formattedLog['formatted_time'] = $date->format('H:i:s');
        }
        
        $formattedLogs[] = $formattedLog;
    }
    
    return $formattedLogs;
}

/**
 * Exporta logs para um arquivo CSV
 * 
 * @param array $logs Registros de log a serem exportados
 * @param string $viewMode Modo de visualização ('normal' ou 'friendly')
 * @return void Envia o arquivo para download diretamente
 */
function exportLogsToCSV($logs, $viewMode = 'normal') {    
    // Configuração do cabeçalho para download do CSV
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=logs_' . ($viewMode === 'friendly' ? 'friendly_' : 'technical_') . date('Y-m-d') . '.csv');
    
    // Criação do arquivo CSV
    $output = fopen('php://output', 'w');
    
    // Define o caractere de separação de campo para UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Cabeçalho do CSV - diferente conforme o modo
    if ($viewMode === 'friendly') {
        fputcsv($output, [
            'ID', 'Data', 'Hora', 'Usuário', 'Ação', 
            'Descrição', 'Alterações', 'Status'
        ]);
        
        // Linhas de dados no formato amigável
        foreach ($logs as $row) {            
            $changes = !empty($row['changes']) ? formatChangesText($row['changes']) : 'N/A';
            
            fputcsv($output, [
                $row['id'],
                $row['formatted_date'] ?? (isset($row['created_at']) ? date('d/m/Y', strtotime($row['created_at'])) : 'N/A'),
                $row['formatted_time'] ?? (isset($row['created_at']) ? date('H:i:s', strtotime($row['created_at'])) : 'N/A'),
                $row['username'] ?? 'Desconhecido',
                $row['action_label'] ?? ($row['action'] ?? 'N/A'),
                $row['reason'] ?? '',
                $changes,
                $row['status_label'] ?? ($row['status'] ?? 'N/A')
            ]);
        }
    } else {
        // Cabeçalho do CSV para modo normal
        fputcsv($output, [
            'ID', 'Data/Hora', 'Usuário', 'Tipo de Entidade', 'ID da Entidade',
            'Ação', 'Descrição', 'Alterações', 'Status', 'Endereço IP'
        ]);
        
        // Linhas de dados no formato normal
        foreach ($logs as $row) {
            // Formata o texto de alterações para ser mais legível
            $changesText = !empty($row['changes']) ? formatChangesText($row['changes']) : 'N/A';
            
            fputcsv($output, [
                $row['id'],
                $row['created_at'],
                $row['username'] ?? 'Desconhecido',
                $row['entity_type'] ?? 'N/A',
                $row['entity_id'] ?? 'N/A',
                $row['action'] ?? 'N/A',
                $row['reason'] ?? '',
                $changesText,
                $row['status'] ?? 'N/A',
                $row['ip_address'] ?? 'N/A'
            ]);
        }
    }
    
    fclose($output);
}

/**
 * Gera um relatório de logs em formato PDF
 * 
 * @param array $logs Registros de log a serem incluídos no relatório
 * @param array $filters Filtros aplicados ao relatório
 * @return string Caminho do arquivo PDF gerado
 */
function generateLogPDF($logs, $filters = []) {
    require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
    
    // Cria uma nova instância de TCPDF
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    
    // Define informações do documento
    $pdf->SetCreator('Sistema Achados e Perdidos');
    $pdf->SetAuthor('Administrador');
    $pdf->SetTitle('Relatório de Logs');
    $pdf->SetSubject('Relatório de Logs do Sistema');
    $pdf->SetKeywords('logs, relatório, sistema, achados, perdidos');
    
    // Remove cabeçalho e rodapé padrão
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Define margens
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(true, 10);
    
    // Adiciona uma página
    $pdf->AddPage();
    
    // Define a fonte
    $pdf->SetFont('helvetica', 'B', 16);
    
    // Título do relatório
    $pdf->Cell(0, 10, 'Relatório de Logs do Sistema', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Data de geração: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
    
    // Adiciona filtros aplicados
    if (!empty($filters)) {
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 5, 'Filtros aplicados:', 0, 1);
        $pdf->SetFont('helvetica', '', 9);
        
        $filterLabels = [
            'entity_type' => 'Tipo de Entidade',
            'action' => 'Ação',
            'status' => 'Status',
            'user_id' => 'ID do Usuário',
            'date_from' => 'Data Inicial',
            'date_to' => 'Data Final'
        ];
        
        foreach ($filters as $key => $value) {
            if (!empty($value) && isset($filterLabels[$key])) {
                $pdf->Cell(0, 5, $filterLabels[$key] . ': ' . $value, 0, 1);
            }
        }
    }
    
    $pdf->Ln(10);
    
    // Definir as larguras das colunas
    $colWidths = [
        10,  // ID
        25,  // Data/Hora
        25,  // Usuário
        20,  // Tipo
        15,  // ID Entidade
        25,  // Ação
        40,  // Descrição
        40,  // Alterações
        20,  // Status
        25   // IP
    ];
    
    // Cabeçalho da tabela
    $pdf->SetFont('helvetica', 'B', 8);
    $pdf->SetFillColor(240, 240, 240);
    
    $pdf->Cell($colWidths[0], 7, 'ID', 1, 0, 'C', true);
    $pdf->Cell($colWidths[1], 7, 'Data/Hora', 1, 0, 'C', true);
    $pdf->Cell($colWidths[2], 7, 'Usuário', 1, 0, 'C', true);
    $pdf->Cell($colWidths[3], 7, 'Tipo', 1, 0, 'C', true);
    $pdf->Cell($colWidths[4], 7, 'ID Entidade', 1, 0, 'C', true);
    $pdf->Cell($colWidths[5], 7, 'Ação', 1, 0, 'C', true);
    $pdf->Cell($colWidths[6], 7, 'Descrição', 1, 0, 'C', true);
    $pdf->Cell($colWidths[7], 7, 'Alterações', 1, 0, 'C', true);
    $pdf->Cell($colWidths[8], 7, 'Status', 1, 0, 'C', true);
    $pdf->Cell($colWidths[9], 7, 'IP', 1, 1, 'C', true);
    
    // Dados da tabela
    $pdf->SetFont('helvetica', '', 7);
    $fill = false;
    
    foreach ($logs as $log) {
        // Prepara o texto das alterações
        $changesText = formatChangesText($log['changes'] ?? '');
        
        // Limita o tamanho dos textos para evitar problemas no PDF
        $username = !empty($log['username']) ? $log['username'] : 'Desconhecido';
        $entityType = !empty($log['entity_type']) ? $log['entity_type'] : 'N/A';
        $entityId = !empty($log['entity_id']) ? $log['entity_id'] : 'N/A';
        $action = !empty($log['action']) ? $log['action'] : 'N/A';
        $reason = !empty($log['reason']) ? $log['reason'] : '';
        $status = !empty($log['status']) ? $log['status'] : 'N/A';
        $ip = !empty($log['ip_address']) ? $log['ip_address'] : 'N/A';
        
        // Configura cor para status
        $textColor = strtolower($status) === 'error' ? [255, 0, 0] : [0, 128, 0];
        
        // Posição inicial para calcular altura necessária
        $startY = $pdf->GetY();
        $startPage = $pdf->getPage();
        
        // Calcular altura necessária para cada célula com conteúdo multilinha
        $maxHeight = 0;
        
        // Descrição
        $descHeight = $pdf->getStringHeight($colWidths[6], $reason);
        $maxHeight = max($maxHeight, $descHeight);
        
        // Alterações
        $changesHeight = $pdf->getStringHeight($colWidths[7], $changesText);
        $maxHeight = max($maxHeight, $changesHeight);
        
        // Garantir altura mínima
        $maxHeight = max($maxHeight, 7);
        
        // Verificar se temos espaço suficiente na página atual
        if ($startY + $maxHeight > $pdf->getPageHeight() - $pdf->getBreakMargin()) {
            $pdf->AddPage();
            $startY = $pdf->GetY();
            $startPage = $pdf->getPage();
        }
        
        // Desenhar todas as células com a mesma altura
        $pdf->setPage($startPage);
        $pdf->SetY($startY);
        
        // Células de conteúdo fixo
        $pdf->Cell($colWidths[0], $maxHeight, $log['id'], 1, 0, 'C', $fill);
        $pdf->Cell($colWidths[1], $maxHeight, $log['created_at'], 1, 0, 'C', $fill);
        $pdf->Cell($colWidths[2], $maxHeight, $username, 1, 0, 'L', $fill);
        $pdf->Cell($colWidths[3], $maxHeight, $entityType, 1, 0, 'C', $fill);
        $pdf->Cell($colWidths[4], $maxHeight, $entityId, 1, 0, 'C', $fill);
        $pdf->Cell($colWidths[5], $maxHeight, $action, 1, 0, 'L', $fill);
        
        // Descrição (MultiCell)
        $currentX = $pdf->GetX();
        $currentY = $pdf->GetY();
        $pdf->MultiCell($colWidths[6], $maxHeight, $reason, 1, 'L', $fill, 0);
        $pdf->SetXY($currentX + $colWidths[6], $currentY);
        
        // Alterações (MultiCell)
        $currentX = $pdf->GetX();
        $currentY = $pdf->GetY();
        $pdf->MultiCell($colWidths[7], $maxHeight, $changesText, 1, 'L', $fill, 0);
        $pdf->SetXY($currentX + $colWidths[7], $currentY);
        
        // Status com cor
        $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
        $pdf->Cell($colWidths[8], $maxHeight, $status, 1, 0, 'C', $fill);
        $pdf->SetTextColor(0, 0, 0); // Restaura para preto
        
        // IP
        $pdf->Cell($colWidths[9], $maxHeight, $ip, 1, 1, 'C', $fill);
        
        $fill = !$fill; // Alterna o preenchimento
    }
    
    // Gera o nome do arquivo baseado na data
    $filename = 'logs_technical_' . date('Y-m-d_His') . '.pdf';
    $filepath = __DIR__ . '/../logs/' . $filename;
    
    // Certifica-se de que o diretório existe
    if (!is_dir(__DIR__ . '/../logs')) {
        mkdir(__DIR__ . '/../logs', 0755, true);
    }
    
    // Salva o PDF
    $pdf->Output('logs_' . date('Y-m-d_His') . '.pdf', 'I'); // ou 'D' para forçar download
    exit;
}

/**
 * Gera um relatório de logs em formato PDF no modo amigável
 * 
 * @param array $logs Registros de log a serem incluídos no relatório
 * @param array $filters Filtros aplicados ao relatório
 * @return string Caminho do arquivo PDF gerado
 */
function generateFriendlyLogPDF($logs, $filters = []) {
    require_once __DIR__ . '/../vendor/tecnickcom/tcpdf/tcpdf.php';
    
    // Formata os logs para visualização amigável se ainda não foram formatados
    if (!isset($logs[0]['action_label'])) {
        $logs = formatLogsForReport($logs);
    }
    
    // Cria uma nova instância de TCPDF
    $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
    
    // Define informações do documento
    $pdf->SetCreator('Sistema Achados e Perdidos');
    $pdf->SetAuthor('Administrador');
    $pdf->SetTitle('Relatório de Logs - Visão Amigável');
    $pdf->SetSubject('Relatório de Logs do Sistema');
    $pdf->SetKeywords('logs, relatório, sistema, achados, perdidos');
    
    // Remove cabeçalho e rodapé padrão
    $pdf->setPrintHeader(false);
    $pdf->setPrintFooter(false);
    
    // Define margens
    $pdf->SetMargins(10, 10, 10);
    $pdf->SetAutoPageBreak(true, 10);
    
    // Adiciona uma página
    $pdf->AddPage();
    
    // Define a fonte
    $pdf->SetFont('helvetica', 'B', 16);
    
    // Título do relatório
    $pdf->Cell(0, 10, 'Relatório de Logs do Sistema - Visão Amigável', 0, 1, 'C');
    $pdf->SetFont('helvetica', '', 10);
    $pdf->Cell(0, 5, 'Data de geração: ' . date('d/m/Y H:i:s'), 0, 1, 'C');
    
    // Adiciona filtros aplicados
    if (!empty($filters)) {
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 10);
        $pdf->Cell(0, 5, 'Filtros aplicados:', 0, 1);
        $pdf->SetFont('helvetica', '', 9);
        
        $filterLabels = [
            'entity_type' => 'Tipo de Entidade',
            'action' => 'Ação',
            'status' => 'Status',
            'user_id' => 'ID do Usuário',
            'date_from' => 'Data Inicial',
            'date_to' => 'Data Final'
        ];
        
        foreach ($filters as $key => $value) {
            if (!empty($value) && isset($filterLabels[$key])) {
                $pdf->Cell(0, 5, $filterLabels[$key] . ': ' . $value, 0, 1);
            }
        }
    }
    
    // Adiciona resumo estatístico se disponível
    if (!empty($logs)) {
        $pdf->Ln(5);
        $pdf->SetFont('helvetica', 'B', 12);
        $pdf->Cell(0, 7, 'Resumo Estatístico', 0, 1);
        $pdf->SetFont('helvetica', '', 10);
        
        // Calcula estatísticas
        $successLogs = 0;
        $errorLogs = 0;
        $actionCounts = [];
        
        foreach ($logs as $log) {
            if (isset($log['status']) && strtolower($log['status']) === 'success') {
                $successLogs++;
            } elseif (isset($log['status']) && strtolower($log['status']) === 'error') {
                $errorLogs++;
            }
            
            $action = $log['action'] ?? 'unknown';
            if (!isset($actionCounts[$action])) {
                $actionCounts[$action] = 0;
            }
            $actionCounts[$action]++;
        }
        
        // Mostrar estatísticas
        $pdf->Cell(0, 6, 'Total de registros: ' . count($logs), 0, 1);
        $pdf->Cell(0, 6, 'Operações com sucesso: ' . $successLogs, 0, 1);
        $pdf->Cell(0, 6, 'Operações com erro: ' . $errorLogs, 0, 1);
        
        if (!empty($actionCounts)) {
            $pdf->Ln(2);
            $pdf->SetFont('helvetica', 'B', 10);
            $pdf->Cell(0, 6, 'Ações mais comuns:', 0, 1);
            $pdf->SetFont('helvetica', '', 9);
            
            // Ordena as ações por contagem
            arsort($actionCounts);
            $topActions = array_slice($actionCounts, 0, 5, true);
            
            foreach ($topActions as $action => $count) {
                $actionLabel = $action;
                // Mapeia ações para descrições mais amigáveis
                $actionLabels = [
                    'login' => 'Login no sistema',
                    'logout' => 'Logout do sistema',
                    'create_user' => 'Criação de usuário',
                    'edit_user' => 'Edição de usuário',
                    'delete_user' => 'Exclusão de usuário',
                    'create_category' => 'Criação de categoria',
                    'edit_category' => 'Edição de categoria',
                    'delete_category' => 'Exclusão de categoria',
                    'create_item' => 'Criação de item',
                    'edit_item' => 'Edição de item',
                    'delete_item' => 'Exclusão de item'
                ];
                
                if (isset($actionLabels[$action])) {
                    $actionLabel = $actionLabels[$action];
                }
                
                $pdf->Cell(0, 5, $actionLabel . ': ' . $count, 0, 1);
            }
        }
    }
    
    $pdf->Ln(10);
      // Definir as larguras das colunas
    $colWidths = [
        15,  // ID
        30,  // Data/Hora (Aumentado de 25 para 30)
        30,  // Usuário
        40,  // Ação
        55,  // Descrição (Reduzido para compensar)
        55,  // Alterações (Reduzido para compensar)
        25   // Status
    ];
    
    // Cabeçalho da tabela
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(240, 240, 240);
    
    $pdf->Cell($colWidths[0], 7, 'ID', 1, 0, 'C', true);
    $pdf->Cell($colWidths[1], 7, 'Data/Hora', 1, 0, 'C', true);
    $pdf->Cell($colWidths[2], 7, 'Usuário', 1, 0, 'C', true);
    $pdf->Cell($colWidths[3], 7, 'Ação', 1, 0, 'C', true);
    $pdf->Cell($colWidths[4], 7, 'Descrição', 1, 0, 'C', true);
    $pdf->Cell($colWidths[5], 7, 'Alterações', 1, 0, 'C', true);
    $pdf->Cell($colWidths[6], 7, 'Status', 1, 1, 'C', true);
    
    // Dados da tabela
    $pdf->SetFont('helvetica', '', 8);
    $fill = false;
    
    foreach ($logs as $log) {
        // Prepara o texto das alterações
        $changesText = formatChangesText($log['changes'] ?? '');
          // Formata a data/hora
        $dateTime = '';
        if (isset($log['formatted_date']) && isset($log['formatted_time'])) {
            $dateTime = $log['formatted_date'] . "\n" . $log['formatted_time'];
        } else {
            $dateTime = $log['created_at'] ?? 'N/A';
        }
        
        // Limita o tamanho dos textos para evitar problemas no PDF
        $username = !empty($log['username']) ? $log['username'] : 'Desconhecido';
        $action = !empty($log['action_label']) ? $log['action_label'] : ($log['action'] ?? 'N/A');
        $reason = !empty($log['reason']) ? $log['reason'] : '';
        $status = !empty($log['status_label']) ? $log['status_label'] : ($log['status'] ?? 'N/A');
        
        // Configura cor para status
        $textColor = strtolower($log['status'] ?? '') === 'error' ? [255, 0, 0] : [0, 128, 0];
        
        // Posição inicial para calcular altura necessária
        $startY = $pdf->GetY();
        $startPage = $pdf->getPage();
        
        // Calcular altura necessária para cada célula com conteúdo multilinha
        $maxHeight = 0;
        
        // Descrição
        $descHeight = $pdf->getStringHeight($colWidths[4], $reason);
        $maxHeight = max($maxHeight, $descHeight);
        
        // Alterações
        $changesHeight = $pdf->getStringHeight($colWidths[5], $changesText);
        $maxHeight = max($maxHeight, $changesHeight);
        
        // Garantir altura mínima
        $maxHeight = max($maxHeight, 8);
        
        // Verificar se temos espaço suficiente na página atual
        if ($startY + $maxHeight > $pdf->getPageHeight() - $pdf->getBreakMargin()) {
            $pdf->AddPage();
            $startY = $pdf->GetY();
            $startPage = $pdf->getPage();
        }
        
        // Desenhar todas as células com a mesma altura
        $pdf->setPage($startPage);
        $pdf->SetY($startY);
        
        // ID
        $pdf->Cell($colWidths[0], $maxHeight, $log['id'], 1, 0, 'C', $fill);
        
        // Data/Hora
        $pdf->Cell($colWidths[1], $maxHeight, $dateTime, 1, 0, 'C', $fill);
        
        // Usuário
        $pdf->Cell($colWidths[2], $maxHeight, $username, 1, 0, 'L', $fill);
        
        // Ação
        $pdf->Cell($colWidths[3], $maxHeight, $action, 1, 0, 'L', $fill);
        
        // Descrição (MultiCell)
        $currentX = $pdf->GetX();
        $currentY = $pdf->GetY();
        $pdf->MultiCell($colWidths[4], $maxHeight, $reason, 1, 'L', $fill, 0);
        $pdf->SetXY($currentX + $colWidths[4], $currentY);
        
        // Alterações (MultiCell)
        $currentX = $pdf->GetX();
        $currentY = $pdf->GetY();
        $pdf->MultiCell($colWidths[5], $maxHeight, $changesText, 1, 'L', $fill, 0);
        $pdf->SetXY($currentX + $colWidths[5], $currentY);
        
        // Status (com cor)
        $pdf->SetTextColor($textColor[0], $textColor[1], $textColor[2]);
        $pdf->Cell($colWidths[6], $maxHeight, $status, 1, 1, 'C', $fill);
        $pdf->SetTextColor(0, 0, 0); // Restaura para preto
        
        $fill = !$fill; // Alterna o preenchimento
    }
    
    // Gera o nome do arquivo baseado na data
    $filename = 'logs_friendly_' . date('Y-m-d_His') . '.pdf';
    $filepath = __DIR__ . '/../logs/' . $filename;
    
    // Certifica-se de que o diretório existe
    if (!is_dir(__DIR__ . '/../logs')) {
        mkdir(__DIR__ . '/../logs', 0755, true);
    }
    
    // Salva o PDF
    $pdf->Output('logs_' . date('Y-m-d_His') . '.pdf', 'I'); // ou 'D' para forçar download
    exit;
}
