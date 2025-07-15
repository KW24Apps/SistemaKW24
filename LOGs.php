<?php
// viewer_logs.php - Coloque este arquivo na raiz do seu projeto ou em uma pasta admin/logs

// Verificação de autenticação com usuário e senha
session_start();
$usuario_correto = "KW24";
$senha_correta = "159Qwaszx753";

// Processar logout
if (isset($_GET['logout'])) {
    session_unset();
    session_destroy();
    header('Location: ' . $_SERVER['PHP_SELF']);
    exit;
}

// Verificar login ou processar tentativa de login
$loginError = false;
if (isset($_POST['usuario']) && isset($_POST['senha'])) {
    if (strtolower($_POST['usuario']) === strtolower($usuario_correto) && $_POST['senha'] === $senha_correta) {
        $_SESSION['logviewer_auth'] = true;
        $_SESSION['logviewer_user'] = $usuario_correto; // Sempre usar a versão correta do nome
    } else {
        $loginError = true;
    }
}

// Se não estiver autenticado, mostrar tela de login
if (!isset($_SESSION['logviewer_auth']) || $_SESSION['logviewer_auth'] !== true) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Log Viewer - Login</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
        
        <link rel="stylesheet" href="assets/css/login.css">

    </head>
    <body class="<?= $sidebarState === 'collapsed' ? 'sidebar-collapsed' : '' ?>">
        <?php if ($loginError): ?>
        <div class="alert">
            <i class="fas fa-exclamation-circle"></i> Usuário ou senha inválidos
        </div>
        <?php endif; ?>
        <div class="login-container">
            <div class="login-header">
                <img src="https://gabriel.kw24.com.br/06_KW24_TAGLINE_%20POSITIVO.png" alt="KW24 Logo">
            </div>
            <h1>Log Viewer</h1>
            <form method="post">
                <div class="input-group">
                    <span class="input-icon">👤</span>
                    <input type="text" name="usuario" placeholder="Email ID" required>
                </div>
                <div class="input-group">
                    <span class="input-icon">🔒</span>
                    <input type="password" name="senha" placeholder="Password" required>
                </div>
                <div class="remember-me">
                    <input type="checkbox" id="remember" name="remember">
                    <label for="remember">Remember me</label>
                </div>
                <button type="submit">LOGIN</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Autenticado - código do visualizador de logs
$logDir = '/home/kw24co49/apis.kw24.com.br/Apps/logs/';
$logFiles = glob($logDir . '*.log');
$date = $_GET['date'] ?? date('Y-m-d');
$traceId = $_GET['trace'] ?? '';
$allLogEntries = [];

// Funções auxiliares
function parseLogLine($line, $sourceFile) {
    // Extrai timestamp, trace ID e conteúdo
    if (preg_match('/\[([\d-]+)\s([\d:]+)\]\s+\[(.*?)\]/', $line, $matches)) {
        return [
            'date' => $matches[1],
            'time' => $matches[2],
            'timestamp' => strtotime($matches[1] . ' ' . $matches[2]),
            'traceId' => $matches[3],
            'content' => $line,
            'sourceFile' => $sourceFile // Armazena o arquivo fonte
        ];
    }
    return null;
}

function formatLogLine($entry) {
    $line = htmlspecialchars($entry['content']);
    $sourceFile = basename($entry['sourceFile']);
    $fileColor = getFileColor($sourceFile);
    
    // Tag com nome do arquivo
    $fileTag = "<span class=\"file-tag\" style=\"background-color:{$fileColor}\">{$sourceFile}</span>";
    
    // Destacar o trace ID
    $traceId = htmlspecialchars($entry['traceId']);
    $line = str_replace(
        "[$traceId]", 
        "<span class=\"trace-id\">[{$traceId}]</span>", 
        $line
    );
    
    // Destacar erros em vermelho
    $lineClass = "log-line";
    if (stripos($line, '[erro]') !== false || 
        stripos($line, 'error') !== false || 
        stripos($line, 'exceção') !== false ||
        stripos($line, 'exception') !== false) {
        $lineClass .= " error";
    }
    
    return "<div class=\"{$lineClass}\">{$fileTag} {$line}</div>";
}

// Gera cores consistentes para cada arquivo de log
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

// Processa todos os arquivos de log
foreach ($logFiles as $logFile) {
    $content = file_get_contents($logFile);
    $lines = explode("\n", $content);
    
    foreach ($lines as $line) {
        if (empty(trim($line))) continue;
        
        $parsed = parseLogLine($line, $logFile);
        if ($parsed) {
            // Filtrar por data
            if ($date && $parsed['date'] !== $date) {
                continue;
            }
            
            // Filtrar por trace ID
            if ($traceId && $parsed['traceId'] !== $traceId) {
                continue;
            }
            
            // Adicionar entrada à lista
            $allLogEntries[] = $parsed;
        }
    }
}

// Ordenar entradas por timestamp
usort($allLogEntries, function($a, $b) {
    // Primeiro compara por timestamp
    $timestampCompare = $a['timestamp'] <=> $b['timestamp'];
    if ($timestampCompare !== 0) {
        return $timestampCompare;
    }
    
    // Se o timestamp for igual, ordena pelo nome do arquivo
    return strcmp($a['sourceFile'], $b['sourceFile']);
});

// Obter lista de traces únicos para o filtro dropdown
$uniqueTraces = [];
$uniqueDates = [];

foreach ($logFiles as $logFile) {
    $content = file_get_contents($logFile);
    $lines = explode("\n", $content);
    
    foreach ($lines as $line) {
        $parsed = parseLogLine($line, $logFile);
        if ($parsed) {
            if (!isset($uniqueDates[$parsed['date']])) {
                $uniqueDates[$parsed['date']] = true;
            }
            
            if (!isset($uniqueTraces[$parsed['traceId']])) {
                $uniqueTraces[$parsed['traceId']] = true;
            }
        }
    }
}

// Ordenar datas e traces
$uniqueDates = array_keys($uniqueDates);
rsort($uniqueDates); // Mais recentes primeiro
$uniqueTraces = array_keys($uniqueTraces);
sort($uniqueTraces);

// Verifica se estamos no modo de download
$downloadMode = isset($_GET['mode']) && $_GET['mode'] === 'download';

// Verifica se há um estado da sidebar na URL
$sidebarState = $_GET['sidebar'] ?? '';

// Para debugging, conta quantos arquivos foram processados
$fileCount = count($logFiles);

// Se estiver no modo de download, exibe a lista de arquivos para download
if ($downloadMode) {
    $fileList = [];
    foreach ($logFiles as $logFile) {
        $fileList[] = [
            'name' => basename($logFile),
            'size' => filesize($logFile),
            'modified' => date('Y-m-d H:i:s', filemtime($logFile)),
            'path' => $logFile
        ];
    }
    // Ordenar por data de modificação (mais recente primeiro)
    usort($fileList, function($a, $b) {
        return strtotime($b['modified']) - strtotime($a['modified']);
    });
}

// Função para extrair a parte final do TRACE ID (após o último underscore)
function getShortTraceId($traceId) {
    if (strpos($traceId, '_') !== false) {
        $parts = explode('_', $traceId);
        return end($parts);
    }
    return $traceId;
}

// Função para formatar o tamanho do arquivo
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB'];
    $i = 0;
    while ($bytes > 1024 && $i < count($units) - 1) {
        $bytes /= 1024;
        $i++;
    }
    return round($bytes, 2) . ' ' . $units[$i];
}

// Função para formatar cada entrada de log na tabela
function formatLogTableRow($entry) {
    $sourceFile = basename($entry['sourceFile']);
    $shortTrace = getShortTraceId($entry['traceId']);
    $fileColor = getFileColor($sourceFile);
    
    // Extrair o conteúdo principal do log (removendo timestamp e trace)
    $content = $entry['content'];
    // Tenta remover a parte inicial padrão do log
    $content = preg_replace('/\[\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}:\d{2}\]\s+\[[^\]]+\]/', '', $content);
    $content = trim($content);
    
    $rowClass = "";
    if (stripos($content, '[erro]') !== false || 
        stripos($content, 'error') !== false || 
        stripos($content, 'exceção') !== false ||
        stripos($content, 'exception') !== false) {
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

?>

<!DOCTYPE html>
<html>
<head>
    <title>Log Viewer - APIs KW24</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">

    <link rel="stylesheet" href="assets/css/logs.css">

    <!-- Font Awesome para ícones -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="<?= $sidebarState === 'collapsed' ? 'sidebar-collapsed' : '' ?>">
    <!-- Menu Lateral -->
    <div class="sidebar <?= $sidebarState === 'collapsed' ? 'collapsed' : '' ?>">
        <button id="sidebarToggle" class="toggle-btn" title="Expandir/Recolher Menu">
            <i class="fas fa-angle-left"></i>
        </button>
        <div class="logo-container">
            <img src="https://gabriel.kw24.com.br/02_KW24_HORIZONTAL_NEGATIVO.png" alt="KW24 Logo">
        </div>
        <div class="sidebar-content">
            <div class="sidebar-menu">            <a href="#" data-mode="filter" class="sidebar-link <?= (!$downloadMode) ? 'active' : '' ?>" title="Filtro">
                <i class="fas fa-filter"></i> <span>Filtro</span>
                <div class="menu-tooltip">Filtro</div>
            </a>
            <a href="#" data-mode="download" class="sidebar-link <?= ($downloadMode) ? 'active' : '' ?>" title="Download">
                <i class="fas fa-download"></i> <span>Download</span>
                <div class="menu-tooltip">Download</div>
            </a>
            </div>
        </div>
        <div class="user-panel">
            <div class="user-info"><?= htmlspecialchars($_SESSION['logviewer_user'] ?? 'Usuário') ?></div>
            <form method="post" action="?logout=1">
                <button type="submit" class="logout-btn" title="Sair">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sair</span>
                </button>
            </form>
        </div>
    </div>
    
    <!-- Conteúdo Principal -->
    <div class="main-content <?= $sidebarState === 'collapsed' ? 'expanded' : '' ?>">
        <div class="top-bar">
            <h1 class="page-title">Log Viewer</h1>
        </div>
        
        <div class="content-area">
            <?php if ($downloadMode): ?>
                <!-- Modo de Download -->
                <div class="stats">
                    <p><?= $fileCount ?> arquivo(s) disponível(is) para download</p>
                </div>
                
                <div class="logs-table-container">
                    <?php if (empty($fileList)): ?>
                        <div class="empty">
                            <p>Nenhum arquivo de log encontrado.</p>
                        </div>
                    <?php else: ?>
                        <ul class="file-list">
                            <?php foreach ($fileList as $file): ?>
                                <li class="file-item">
                                    <div class="file-name"><?= htmlspecialchars($file['name']) ?></div>
                                    <div class="file-meta">
                                        <span class="file-size"><?= formatFileSize($file['size']) ?></span>
                                        <span class="file-date"><?= $file['modified'] ?></span>
                                        <a href="download.php?file=<?= urlencode($file['name']) ?>" class="download-btn">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <!-- Modo de Filtro -->
                <div class="filters">
                    <div class="filter-group">
                        <label for="date">Data:</label>
                        <select name="date" id="date">
                            <option value="">Todas as datas</option>
                            <?php foreach ($uniqueDates as $d): ?>
                                <option value="<?= $d ?>" <?= $d === $date ? 'selected' : '' ?>><?= $d ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="trace">TRACE ID:</label>
                        <select name="trace" id="trace">
                            <option value="">Todos os traces</option>
                            <?php foreach ($uniqueTraces as $t): ?>
                                <option value="<?= $t ?>" <?= $t === $traceId ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="stats">
                    <p>
                        <?= $fileCount ?> arquivo(s) de log processado(s). 
                        <?php 
                        echo count($allLogEntries) . ' registros encontrados';
                        if ($date) echo ' para a data <strong>' . $date . '</strong>';
                        if ($traceId) echo ' com TRACE ID <strong>' . $traceId . '</strong>';
                        ?>
                    </p>
                </div>
                
                <div class="logs-table-container">
                    <?php if (empty($allLogEntries)): ?>
                        <div class="empty">
                            <p>Nenhum registro de log encontrado com os filtros atuais.</p>
                        </div>
                    <?php else: ?>
                        <table class="logs-table">
                            <thead>
                                <tr>
                                    <th width="15%">Origem</th>
                                    <th width="15%">Data</th>
                                    <th width="10%">Trace</th>
                                    <th>Log</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                foreach ($allLogEntries as $entry): 
                                    echo formatLogTableRow($entry);
                                endforeach; 
                                ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="footer">
                <p>Log Viewer v1.0 | APIs KW24 - <?= date('Y') ?></p>
            </div>
        </div>
    </div>
    
    <script src="assets/js/logs.js"></script>
</body>
</html>