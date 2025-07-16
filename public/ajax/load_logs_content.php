<?php
/**
 * AJAX handler for log viewer content updates
 * This file returns only the content portion of logs.php for AJAX requests
 */
session_start();

require_once __DIR__ . '/../../includes/helpers.php';
require_once __DIR__ . '/../../controllers/LogController.php';

// Check if the user is authenticated
requireAuthentication();

$logController = new LogController();

// Get filter parameters
$selectedStartDate = sanitizeInput($_GET['start_date'] ?? '');
$selectedEndDate = sanitizeInput($_GET['end_date'] ?? '');
$selectedTrace = sanitizeInput($_GET['trace'] ?? '');
$selectedApp = sanitizeInput($_GET['app'] ?? '');
$mode = sanitizeInput($_GET['mode'] ?? 'filter');
$currentPage = intval($_GET['page'] ?? 1);
$itemsPerPage = intval($_GET['per_page'] ?? 50); // Default 50 items per page

// Get data for the view
$filterOptions = $logController->getFilterOptions();
$uniqueDates = $filterOptions['dates'];
$uniqueTraces = $filterOptions['traces'];
$uniqueApps = $filterOptions['apps'];

$logsData = ($mode === 'filter') ? $logController->getLogs($selectedStartDate, $selectedEndDate, $selectedTrace, $selectedApp, $currentPage, $itemsPerPage) : ['logs' => [], 'pagination' => []];
$allLogEntries = $logsData['logs'] ?? [];
$pagination = $logsData['pagination'] ?? [];
$fileList = ($mode === 'download') ? $logController->getDownloadableFiles() : [];

// Start capturing output
ob_start();
?>
<div class="content-area">
    <?php if ($mode === 'download'): ?>
        <!-- Download Mode -->
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
        <!-- Filter Mode -->
        <div class="filter-card">
            <form id="filterForm" class="filter-form" method="get" action="">
                <!-- Hidden field for mode -->
                <input type="hidden" name="mode" value="filter">
                
                <div class="filters">
                    <div class="filter-group">
                        <label for="start_date">DATA INICIAL:</label>
                        <div class="select-wrapper date-wrapper" onclick="document.getElementById('start_date').showPicker()">
                            <input type="date" name="start_date" id="start_date" class="form-select" value="<?= $selectedStartDate ?>">
                            <i class="fas fa-calendar-alt select-arrow"></i>
                        </div>
                    </div>
                    <div class="filter-group">
                        <label for="end_date">DATA FINAL:</label>
                        <div class="select-wrapper date-wrapper" onclick="document.getElementById('end_date').showPicker()">
                            <input type="date" name="end_date" id="end_date" class="form-select" value="<?= $selectedEndDate ?>">
                            <i class="fas fa-calendar-alt select-arrow"></i>
                        </div>
                    </div>
                    <div class="filter-group">
                        <label for="app">APLICAÇÃO:</label>
                        <div class="select-wrapper">
                            <select name="app" id="app" class="form-select">
                                <option value="">Todos os apps</option>
                                <?php foreach ($uniqueApps as $app): ?>
                                    <option value="<?= $app ?>" <?= $app === $selectedApp ? 'selected' : '' ?>><?= $app ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-chevron-down select-arrow"></i>
                        </div>
                    </div>
                    <div class="filter-group">
                        <label for="trace">TRACE ID:</label>
                        <div class="select-wrapper searchable-select">
                            <input type="text" id="trace-search" class="form-select trace-search" placeholder="Digite para filtrar...">
                            <select name="trace" id="trace" class="form-select hidden-select">
                                <option value="">Todos os traces</option>
                                <?php foreach ($uniqueTraces as $t): ?>
                                    <option value="<?= $t ?>" <?= $t === $selectedTrace ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                            <i class="fas fa-search select-arrow"></i>
                        </div>
                    </div>
                </div>
            </form>
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
                if ($selectedStartDate && $selectedEndDate) {
                    echo ' entre as datas <strong>' . $selectedStartDate . '</strong> e <strong>' . $selectedEndDate . '</strong>';
                } elseif ($selectedStartDate) {
                    echo ' a partir da data <strong>' . $selectedStartDate . '</strong>';
                } elseif ($selectedEndDate) {
                    echo ' até a data <strong>' . $selectedEndDate . '</strong>';
                }
                if ($selectedTrace) echo ' com TRACE ID <strong>' . $selectedTrace . '</strong>';
                if ($selectedApp) echo ' do aplicativo <strong>' . $selectedApp . '</strong>';
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
                            <th class="col-app">APLICAÇÃO</th>
                            <th class="col-datetime">DATA</th>
                            <th class="col-trace">TRACE</th>
                            <th class="col-function">FUNÇÃO</th>
                            <th class="col-message">LOG</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($allLogEntries as $entry): 
                            echo formatLogTableRow($entry);
                        endforeach; ?>
                    </tbody>
                </table>

                <?php if (!empty($pagination) && $pagination['totalPages'] > 1): ?>
                <div class="pagination-container">
                    <div class="pagination">
                        <?php 
                        $currentPage = $pagination['currentPage'];
                        $totalPages = $pagination['totalPages'];
                        
                        // Build pagination URL base
                        $urlParams = $_GET;
                        
                        // First and Previous buttons
                        if ($currentPage > 1): 
                            $urlParams['page'] = 1;
                            $firstPageUrl = '?' . http_build_query($urlParams);
                            
                            $urlParams['page'] = $currentPage - 1;
                            $prevPageUrl = '?' . http_build_query($urlParams);
                        ?>
                            <a href="<?= $firstPageUrl ?>" class="pagination-link ajax-link" title="Primeira Página">
                                <i class="fas fa-angle-double-left"></i>
                            </a>
                            <a href="<?= $prevPageUrl ?>" class="pagination-link ajax-link" title="Página Anterior">
                                <i class="fas fa-angle-left"></i>
                            </a>
                        <?php else: ?>
                            <span class="pagination-link disabled" title="Primeira Página">
                                <i class="fas fa-angle-double-left"></i>
                            </span>
                            <span class="pagination-link disabled" title="Página Anterior">
                                <i class="fas fa-angle-left"></i>
                            </span>
                        <?php endif; ?>
                        
                        <!-- Page numbers -->
                        <?php 
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        
                        // Ensure we show at least 5 links if possible
                        if ($endPage - $startPage < 4) {
                            if ($startPage == 1) {
                                $endPage = min($totalPages, 5);
                            } else {
                                $startPage = max(1, $totalPages - 4);
                            }
                        }
                        
                        for ($i = $startPage; $i <= $endPage; $i++): 
                            $urlParams['page'] = $i;
                            $pageUrl = '?' . http_build_query($urlParams);
                        ?>
                            <a href="<?= $pageUrl ?>" class="pagination-link ajax-link <?= ($i == $currentPage) ? 'active' : '' ?>">
                                <?= $i ?>
                            </a>
                        <?php endfor; ?>
                        
                        <!-- Next and Last buttons -->
                        <?php if ($currentPage < $totalPages): 
                            $urlParams['page'] = $currentPage + 1;
                            $nextPageUrl = '?' . http_build_query($urlParams);
                            
                            $urlParams['page'] = $totalPages;
                            $lastPageUrl = '?' . http_build_query($urlParams);
                        ?>
                            <a href="<?= $nextPageUrl ?>" class="pagination-link ajax-link" title="Próxima Página">
                                <i class="fas fa-angle-right"></i>
                            </a>
                            <a href="<?= $lastPageUrl ?>" class="pagination-link ajax-link" title="Última Página">
                                <i class="fas fa-angle-double-right"></i>
                            </a>
                        <?php else: ?>
                            <span class="pagination-link disabled" title="Próxima Página">
                                <i class="fas fa-angle-right"></i>
                            </span>
                            <span class="pagination-link disabled" title="Última Página">
                                <i class="fas fa-angle-double-right"></i>
                            </span>
                        <?php endif; ?>
                        
                        <!-- Items per page selector -->
                        <div class="per-page-selector">
                            <span>Por página:</span>
                            <select id="per-page-select">
                                <option value="20" <?= $itemsPerPage == 20 ? 'selected' : '' ?>>20</option>
                                <option value="50" <?= $itemsPerPage == 50 ? 'selected' : '' ?>>50</option>
                                <option value="100" <?= $itemsPerPage == 100 ? 'selected' : '' ?>>100</option>
                            </select>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<div class="footer">
    <p>Log Viewer v2.0 | KW24 - <?= date('Y') ?></p>
</div>
<?php
$content = ob_get_clean();
echo $content;
?>
