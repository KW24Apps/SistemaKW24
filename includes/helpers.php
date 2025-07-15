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
 * Retorna uma cor específica para cada app
 */
function getAppColor($app) {
    $colors = [
        'Bitrix' => '#2980b9',     // Azul escuro
        'ClickSign' => '#27ae60',  // Verde escuro
        'Deal' => '#8e44ad',       // Roxo
        'Company' => '#d35400',    // Laranja escuro
        'Task' => '#c0392b',       // Vermelho escuro
        'MediaHora' => '#16a085',  // Verde água
        'Extenso' => '#f39c12',    // Laranja claro
        'Outros' => '#7f8c8d'      // Cinza
    ];
    
    return $colors[$app] ?? '#7f8c8d';
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
    
    // Extrair nome da função, se disponível
    $functionName = extractFunctionName($content);
    
    // Identificar o app
    $app = identifyAppFromLog($content, $functionName);
    
    // Limpar mensagem de log removendo a parte da função
    if ($functionName) {
        $content = cleanLogMessage($content);
    }
    
    $content = htmlspecialchars(trim($content));
    
    $rowClass = "";
    if (stripos($content, '[erro]') !== false || stripos($content, 'error') !== false) {
        $rowClass = "error-row";
    }
    
    // Criar um link para o trace que atualiza o filtro
    $traceLink = "<a href='?mode=filter&trace={$entry['traceId']}' class='trace-link' title='Filtrar por este Trace ID'>{$shortTrace}</a>";
    
    // Estilo de tag para origem, com fundo suave e arredondado
    $originTag = "<span class='origin-tag' style='background-color:{$fileColor}20; border: 1px solid {$fileColor}80; color:{$fileColor}'>{$sourceFile}</span>";
    
    // Estilo para o app, com cores diferentes
    $appColor = getAppColor($app);
    $appTag = "<span class='app-tag' style='background-color:{$appColor}20; border: 1px solid {$appColor}80; color:{$appColor}'>{$app}</span>";
    
    return "
    <tr class=\"{$rowClass}\">
        <td class='col-origin'>{$originTag}</td>
        <td class='col-app'>{$appTag}</td>
        <td class='col-datetime'>{$entry['date']} {$entry['time']}</td>
        <td class='col-trace'>{$traceLink}</td>
        <td class='col-function'>" . ($functionName ? htmlspecialchars($functionName) : "-") . "</td>
        <td class='col-message'>{$content}</td>
    </tr>";
}

/**
 * Extrai o nome da função de uma mensagem de log.
 * Exemplo: [BitrixHelper::chamarApi] Log content --> retorna "BitrixHelper::chamarApi"
 */
function extractFunctionName($content) {
    // Padrão para encontrar texto entre colchetes que parece ser uma função/método
    if (preg_match('/\[([a-zA-Z0-9_\\\\:]+::[a-zA-Z0-9_]+)\]/', $content, $matches)) {
        return $matches[1];
    }
    
    // Padrão alternativo para nomes de classe/função sem método
    if (preg_match('/\[([a-zA-Z0-9_\\\\:]+)\]/', $content, $matches)) {
        return $matches[1];
    }
    
    return null;
}

/**
 * Identifica o app a partir do nome da função ou do conteúdo do log
 */
function identifyAppFromLog($content, $functionName = null) {
    // Primeiro verificamos pelo nome da função (se disponível)
    if ($functionName) {
        // Verificar pelo prefixo da função/classe
        if (stripos($functionName, 'Bitrix') !== false) {
            return 'Bitrix';
        }
        if (stripos($functionName, 'ClickSign') !== false) {
            return 'ClickSign';
        }
        if (stripos($functionName, 'Deal') !== false) {
            return 'Deal';
        }
        if (stripos($functionName, 'Company') !== false) {
            return 'Company';
        }
        if (stripos($functionName, 'Task') !== false) {
            return 'Task';
        }
        if (stripos($functionName, 'MediaHora') !== false) {
            return 'MediaHora';
        }
        if (stripos($functionName, 'Extenso') !== false) {
            return 'Extenso';
        }
    }
    
    // Se não encontrou pelo nome da função, procurar no conteúdo
    $content = strtolower($content);
    if (stripos($content, 'bitrix') !== false) {
        return 'Bitrix';
    }
    if (stripos($content, 'clicksign') !== false) {
        return 'ClickSign';
    }
    if (stripos($content, 'deal') !== false) {
        return 'Deal';
    }
    if (stripos($content, 'company') !== false) {
        return 'Company';
    }
    if (stripos($content, 'task') !== false) {
        return 'Task';
    }
    if (stripos($content, 'mediahora') !== false || stripos($content, 'media hora') !== false) {
        return 'MediaHora';
    }
    if (stripos($content, 'extenso') !== false) {
        return 'Extenso';
    }
    
    // Se não conseguiu identificar
    return 'Outros';
}

/**
 * Extrai a mensagem de log sem o nome da função
 */
function cleanLogMessage($content) {
    // Remove o nome da função entre colchetes do início da mensagem
    return preg_replace('/\[[a-zA-Z0-9_\\\\:]+::[a-zA-Z0-9_]+\]\s*/', '', $content);
}

/**
 * Verifica se o usuário está autenticado
 */
