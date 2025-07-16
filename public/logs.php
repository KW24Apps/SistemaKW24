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
$selectedStartDate = sanitizeInput($_GET['start_date'] ?? '');
$selectedEndDate = sanitizeInput($_GET['end_date'] ?? '');
$selectedTrace = sanitizeInput($_GET['trace'] ?? '');
$selectedApp = sanitizeInput($_GET['app'] ?? '');
$mode = sanitizeInput($_GET['mode'] ?? 'filter');
$currentPage = intval($_GET['page'] ?? 1);
$itemsPerPage = intval($_GET['per_page'] ?? 50); // Default 50 items per page

// Obter dados para a view
$filterOptions = $logController->getFilterOptions();
$uniqueDates = $filterOptions['dates'];
$uniqueTraces = $filterOptions['traces'];
$uniqueApps = $filterOptions['apps'];

$logsData = ($mode === 'filter') ? $logController->getLogs($selectedStartDate, $selectedEndDate, $selectedTrace, $selectedApp, $currentPage, $itemsPerPage) : ['logs' => [], 'pagination' => []];
$allLogEntries = $logsData['logs'] ?? [];
$pagination = $logsData['pagination'] ?? [];
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
                <form id="filterForm" class="filter-form" method="get" action="">
                    <!-- Campo oculto para o modo -->
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
                                <select name="app" id="app" class="form-select" onchange="document.getElementById('filterForm').submit();">
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
                                <a href="<?= $firstPageUrl ?>" class="pagination-link" title="Primeira Página">
                                    <i class="fas fa-angle-double-left"></i>
                                </a>
                                <a href="<?= $prevPageUrl ?>" class="pagination-link" title="Página Anterior">
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
                                <a href="<?= $pageUrl ?>" class="pagination-link <?= ($i == $currentPage) ? 'active' : '' ?>">
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
                                <a href="<?= $nextPageUrl ?>" class="pagination-link" title="Próxima Página">
                                    <i class="fas fa-angle-right"></i>
                                </a>
                                <a href="<?= $lastPageUrl ?>" class="pagination-link" title="Última Página">
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
                                <select id="per-page-select" onchange="changeItemsPerPage(this.value)">
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
</div>
<?php
$content = ob_get_clean();

// Adicionar JS específico para logs
$additionalJS = '
<script src="/Apps/assets/js/logs.js"></script>
<script>
// Melhorar a navegação entre páginas para evitar o efeito de piscar
document.addEventListener("DOMContentLoaded", function() {
    // Mostrar overlay imediatamente para evitar FOUC (Flash of Unstyled Content)
    document.documentElement.classList.add("js-loading");
    
    // Aplicar fundo branco globalmente
    document.body.style.background = "white";
    var mainContent = document.querySelector(".main-content");
    if (mainContent) {
        mainContent.style.background = "white";
    }
    
    // Garantir que o overlay de transição seja criado imediatamente
    var transitionOverlay = document.createElement("div");
    transitionOverlay.id = "logsTransitionOverlay";
    transitionOverlay.style.position = "fixed";
    transitionOverlay.style.top = "0";
    transitionOverlay.style.left = "0";
    transitionOverlay.style.width = "100%";
    transitionOverlay.style.height = "100%";
    transitionOverlay.style.backgroundColor = "rgba(255,255,255,0.65)"; // Semi-transparente
    transitionOverlay.style.backdropFilter = "blur(5px)"; // Efeito de blur suave
    transitionOverlay.style.zIndex = "999999"; // Z-index muito alto
    transitionOverlay.style.opacity = "1";
    transitionOverlay.style.display = "flex";
    transitionOverlay.style.justifyContent = "center";
    transitionOverlay.style.alignItems = "center";
    transitionOverlay.style.pointerEvents = "all";
    
    // Criar container para o spinner com um fundo suave
    var spinnerContainer = document.createElement("div");
    spinnerContainer.style.display = "flex";
    spinnerContainer.style.flexDirection = "column";
    spinnerContainer.style.alignItems = "center";
    spinnerContainer.style.padding = "30px";
    spinnerContainer.style.borderRadius = "12px";
    spinnerContainer.style.backgroundColor = "rgba(255,255,255,0.95)";
    spinnerContainer.style.boxShadow = "0 10px 25px rgba(0,0,0,0.1)";
    
    // Adicionar spinner
    var spinner = document.createElement("div");
    spinner.style.width = "50px";
    spinner.style.height = "50px";
    spinner.style.border = "5px solid rgba(8, 107, 141, 0.1)";
    spinner.style.borderTop = "5px solid #086B8D";
    spinner.style.borderRadius = "50%";
    spinner.style.animation = "pageSpin 0.8s linear infinite";
    
    // Adicionar texto de carregamento
    var loadingText = document.createElement("div");
    loadingText.style.marginTop = "15px";
    loadingText.style.color = "#086B8D";
    loadingText.style.fontWeight = "500";
    loadingText.textContent = "Carregando...";
    
    // Adicionar estilo para animação
    var style = document.createElement("style");
    style.textContent = "@keyframes pageSpin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }";
    document.head.appendChild(style);
    
    spinnerContainer.appendChild(spinner);
    spinnerContainer.appendChild(loadingText);
    transitionOverlay.appendChild(spinnerContainer);
    document.body.appendChild(transitionOverlay);
    
    // Manipular todos os links do sidebar para fazer transições suaves
    document.querySelectorAll(".sidebar-link").forEach(function(link) {
        link.addEventListener("click", function(e) {
            e.preventDefault();
            
            // Esconder tudo imediatamente
            document.documentElement.classList.add("js-loading");
            
            // Mostrar overlay de transição imediatamente
            transitionOverlay.style.display = "flex";
            transitionOverlay.style.opacity = "1";
            
            // Esconder o conteúdo principal
            if (mainContent) {
                mainContent.style.opacity = "0";
                mainContent.style.visibility = "hidden";
            }
            
            // Navegar imediatamente - overlay já está visível
            window.location.href = link.href;
        });
    });
    
    // Esconder overlay quando a página estiver completamente carregada
    window.addEventListener("load", function() {
        setTimeout(function() {
            var logsTransitionOverlay = document.getElementById("logsTransitionOverlay");
            if (logsTransitionOverlay) {
                logsTransitionOverlay.style.opacity = "0";
                setTimeout(function() {
                    logsTransitionOverlay.style.display = "none";
                }, 200);
            }
        }, 300);
    });
});
</script>
';
// BLOCO PARA AJAX (adicione abaixo)
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    echo $content;
    exit;
}

include __DIR__ . '/../views/layouts/main.php';