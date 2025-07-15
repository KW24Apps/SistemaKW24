<?php
/**
 * Funções auxiliares para o sistema de logs
 */

/**
 * Gera cores consistentes para cada arquivo de log
 */
function getFileColor($filename) {
    $colors = [
        '#3498db', '#2ecc71', '#e74c3c', '#9b59b6', '#f39c12', 
        '#1abc9c', '#d35400', '#34495e', '#16a085', '#27ae60',
        '#8e44ad', '#f1c40f', '#e67e22', '#c0392b', '#7f8c8d'
    ];
    
    // Usar o nome do arquivo para gerar um índice consistente
    $hash = crc32($filename);
    $index = abs($hash) % count($colors);
    
    return $colors[$index];
}

/**
 * Extrai a parte final do TRACE ID (após o último underscore)
 */
function getShortTraceId($traceId) {
    if (strpos($traceId, '_') !== false) {
        $parts = explode('_', $traceId);
        return end($parts);
    }
    return $traceId;
}

/**
 * Formata o tamanho do arquivo
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes > 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

/**
 * Verificar se o usuário está autenticado
 */
function checkAuthentication() {
    if (!isset($_SESSION['logviewer_auth']) || $_SESSION['logviewer_auth'] !== true) {
        return false;
    }
    return true;
}

/**
 * Redirecionar para login se não autenticado
 */
function requireAuthentication() {
    if (!checkAuthentication()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Sanitizar entrada de dados
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Gerar URL com parâmetros preservados
 */
function buildUrl($baseUrl, $newParams = []) {
    $currentParams = $_GET;
    $params = array_merge($currentParams, $newParams);
    
    // Remover parâmetros vazios
    $params = array_filter($params, function($value) {
        return $value !== '' && $value !== null;
    });
    
    if (empty($params)) {
        return $baseUrl;
    }
    
    return $baseUrl . '?' . http_build_query($params);
}

/**
 * Analisa uma linha de log para extrair informações.
 */
function parseLogLine($line, $sourceFile) {
    if (preg_match('/\[([\d-]+)\s([\d:]+)\]\s+\[(.*?)\]/', $line, $matches)) {
        return [
            'date' => $matches[1],
            'time' => $matches[2],
            'timestamp' => strtotime($matches[1] . ' ' . $matches[2]),
            'traceId' => $matches[3],
            'content' => $line,
            'sourceFile' => $sourceFile
        ];
    }
    return null;
}

/**
 * Formata uma entrada de log para exibição na tabela.
 */
function formatLogTableRow($entry) {
    $sourceFile = basename($entry['sourceFile']);
    $shortTrace = getShortTraceId($entry['traceId']);
    $fileColor = getFileColor($sourceFile);
    
    $content = $entry['content'];
    $content = preg_replace('/\[\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}\]\s+\[[^\]]+\]/', '', $content);
    $content = htmlspecialchars(trim($content));
    
    $rowClass = "";
    if (stripos($content, '[erro]') !== false || stripos($content, 'error') !== false) {
        $rowClass = "error-row";
    }
    
    return "
    <tr class=\"{$rowClass}\">
        <td><span class=\"file-tag\" style=\"background-color:{$fileColor}\">{$sourceFile}</span></td>
        <td>{$entry['date']} {$entry['time']}</td>
        <td><span class=\"trace-id\">{$shortTrace}</span></td>
        <td>{$content}</td>
    </tr>";
}
