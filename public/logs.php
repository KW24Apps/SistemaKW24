<?php
/**
 * Visualizador de Logs - Sistema KW24
 */
session_start();

require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../controllers/LogController.php';

requireAuthentication();

$logController = new LogController();

// Parâmetros de filtro
$selectedDate = sanitizeInput($_GET['date'] ?? '');
$selectedTrace = sanitizeInput($_GET['trace'] ?? '');
$mode = sanitizeInput($_GET['mode'] ?? 'filter');

// Obter dados para a view
$filterOptions = $logController->getFilterOptions();
$uniqueDates = $filterOptions['dates'];
$uniqueTraces = $filterOptions['traces'];

$allLogEntries = ($mode === 'filter') ? $logController->getLogs($selectedDate, $selectedTrace) : [];
$fileList = ($mode === 'download') ? $logController->getDownloadableFiles() : [];

// Configurações da página
$pageTitle = 'Log Viewer - Sistema KW24';
$activeMenu = 'logs';
$sidebarState = $_GET['sidebar'] ?? '';
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= $pageTitle ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/logs.css">
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
            <div class="sidebar-menu">
                <a href="#" data-mode="filter" class="sidebar-link <?= ($mode !== 'download') ? 'active' : '' ?>" title="Filtro">
                    <i class="fas fa-filter"></i> <span>Filtro</span>
                    <div class="menu-tooltip">Filtro</div>
                </a>
                <a href="#" data-mode="download" class="sidebar-link <?= ($mode === 'download') ? 'active' : '' ?>" title="Download">
                    <i class="fas fa-download"></i> <span>Download</span>
                    <div class="menu-tooltip">Download</div>
                </a>
            </div>
        </div>
        <div class="user-panel">
            <div class="user-info"><?= htmlspecialchars($_SESSION['logviewer_user'] ?? 'Usuário') ?></div>
            <a href="logout.php" class="logout-btn" title="Sair">
                <i class="fas fa-sign-out-alt"></i>
                <span>Sair</span>
            </a>
        </div>
    </div>
    
    <!-- Conteúdo Principal -->
    <div class="main-content <?= $sidebarState === 'collapsed' ? 'expanded' : '' ?>">
        <div class="top-bar">
            <h1 class="page-title">Log Viewer</h1>
        </div>
        
        <div class="content-area">
            <?php if ($mode === 'download'): ?>
                <div class="stats">
                    <p><?= count($fileList) ?> arquivo(s) disponível(is) para download.</p>
                </div>
                <div class="logs-table-container">
                    <?php if (empty($fileList)): ?>
                        <div class="empty"><p>Nenhum arquivo de log encontrado.</p></div>
                    <?php else: ?>
                        <ul class="file-list">
                            <?php foreach ($fileList as $file): ?>
                                <li class="file-item">
                                    <div class="file-name"><?= htmlspecialchars($file['name']) ?></div>
                                    <div class="file-meta">
                                        <span class="file-size"><?= formatFileSize($file['size']) ?></span>
                                        <span class="file-date"><?= $file['modified'] ?></span>
                                        <a href="../download.php?file=<?= urlencode($file['name']) ?>" class="download-btn">
                                            <i class="fas fa-download"></i> Download
                                        </a>
                                    </div>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            <?php else: ?>
                <div class="filters">
                    <div class="filter-group">
                        <label for="date">Data:</label>
                        <select name="date" id="date">
                            <option value="">Todas as datas</option>
                            <?php foreach ($uniqueDates as $d): ?>
                                <option value="<?= $d ?>" <?= $d === $selectedDate ? 'selected' : '' ?>><?= $d ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="trace">TRACE ID:</label>
                        <select name="trace" id="trace">
                            <option value="">Todos os traces</option>
                            <?php foreach ($uniqueTraces as $t): ?>
                                <option value="<?= $t ?>" <?= $t === $selectedTrace ? 'selected' : '' ?>><?= $t ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="stats">
                    <p>
                        <?= count($allLogEntries) ?> registros encontrados
                        <?php 
                        if ($selectedDate) echo ' para a data <strong>' . $selectedDate . '</strong>';
                        if ($selectedTrace) echo ' com TRACE ID <strong>' . $selectedTrace . '</strong>';
                        ?>
                    </p>
                </div>
                <div class="logs-table-container">
                    <?php if (empty($allLogEntries)): ?>
                        <div class="empty"><p>Nenhum registro de log encontrado com os filtros atuais.</p></div>
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
                                <?php foreach ($allLogEntries as $entry): 
                                    echo formatLogTableRow($entry);
                                endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div class="footer">
                <p>Log Viewer v2.0 | KW24 - <?= date('Y') ?></p>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/logs.js"></script>
</body>
</html>
