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

$pageTitle = 'Log Viewer - Sistema KW24';
$activeMenu = 'logs';

ob_start();
?>
<div class="log-viewer-container">
    <div class="top-bar">
        <h1 class="page-title">Log Viewer</h1>
        <div class="mode-selector">
            <a href="?mode=filter" class="mode-button <?= ($mode !== 'download') ? 'active' : '' ?>">
                <i class="fas fa-filter"></i> Filtrar
            </a>
            <a href="?mode=download" class="mode-button <?= ($mode === 'download') ? 'active' : '' ?>">
                <i class="fas fa-download"></i> Download
            </a>
        </div>
    </div>

    <div class="content-area">
        <?php if ($mode === 'download'): ?>
            <!-- Modo Download -->
            <div class="card">
                <div class="card-header">
                    <h2>Arquivos Disponíveis</h2>
                </div>
                <div class="card-body">
                    <div class="stats">
                        <p><?= count($fileList) ?> arquivo(s) disponível(is) para download.</p>
                    </div>
                    <div class="logs-table-container">
                        <?php if (empty($fileList)): ?>
                            <div class="empty">
                                <i class="fas fa-file-alt fa-3x"></i>
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
                                            <a href="../download.php?file=<?= urlencode($file['name']) ?>" class="download-btn">
                                                <i class="fas fa-download"></i> Download
                                            </a>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Modo Filtro -->
            <div class="filter-card">
                <div class="filters">
                    <div class="filter-group">
                        <label for="date">DATA:</label>
                        <div class="select-wrapper">
                            <select name="date" id="date" class="form-select">
                                <option value="">Todas as datas</option>
                                <?php foreach ($uniqueDates as $d): ?>
                                    <option value="<?= $d ?>" <?= $d === $selectedDate ? 'selected' : '' ?>><?= $d ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                    </div>
                    <div class="filter-group">
                        <label for="trace">TRACE ID:</label>
                        <div class="select-wrapper">
                            <select name="trace" id="trace" class="form-select">
                                <option value="">Todos os traces</option>
                                <?php foreach ($uniqueTraces as $t): ?>
                                    <option value="<?= $t ?>" <?= $t === $selectedTrace ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="stats-bar">
                <?php
                $count = count($allLogEntries); 
                $fileCount = count($filterOptions['files'] ?? []);
                ?>
                <p>
                    <i class="fas fa-file-alt"></i> <?= $fileCount ?> arquivo(s) de log processado(s). 
                    <?= $count ?> registros encontrados
                    <?php 
                    if ($selectedDate) echo ' para a data <strong>' . $selectedDate . '</strong>';
                    if ($selectedTrace) echo ' com TRACE ID <strong>' . $selectedTrace . '</strong>';
                    ?>
                </p>
            </div>

            <div class="logs-card">
                <?php if (empty($allLogEntries)): ?>
                    <div class="empty">
                        <i class="fas fa-inbox fa-3x"></i>
                        <h3>Nenhum log encontrado</h3>
                        <p>Não há registros para os filtros selecionados.</p>
                        <p>Tente ajustar a data ou trace ID.</p>
                    </div>
                <?php else: ?>
                    <table class="logs-table">
                        <thead>
                            <tr>
                                <th class="col-origin">ORIGEM</th>
                                <th class="col-datetime">DATA</th>
                                <th class="col-trace">TRACE</th>
                                <th class="col-message">LOG</th>
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
    </div>
    
    <div class="footer">
        <p>Log Viewer v2.0 | KW24 - <?= date('Y') ?></p>
    </div>
</div>
<?php
$content = ob_get_clean();
include __DIR__ . '/../views/layouts/main.php';
