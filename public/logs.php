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
$mode = sanitizeInput($_GET['mode'] ?? 'filter');

// Obter dados para a view
$filterOptions = $logController->getFilterOptions();
$uniqueDates = $filterOptions['dates'];
$uniqueTraces = $filterOptions['traces'];

$allLogEntries = ($mode === 'filter') ? $logController->getLogs($selectedStartDate, $selectedEndDate, $selectedTrace) : [];
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
                            <div class="select-wrapper">
                                <input type="date" name="start_date" id="start_date" class="form-select" value="<?= $selectedStartDate ?>">
                                <i class="fas fa-calendar-alt select-arrow"></i>
                            </div>
                        </div>
                        <div class="filter-group">
                            <label for="end_date">DATA FINAL:</label>
                            <div class="select-wrapper">
                                <input type="date" name="end_date" id="end_date" class="form-select" value="<?= $selectedEndDate ?>">
                                <i class="fas fa-calendar-alt select-arrow"></i>
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
    // Aplicar fundo branco globalmente
    document.body.style.background = "white";
    var mainContent = document.querySelector(".main-content");
    if (mainContent) {
        mainContent.style.background = "white";
    }
    
    // Manipular todos os links do sidebar para fazer transições suaves
    document.querySelectorAll(".sidebar-link").forEach(function(link) {
        link.addEventListener("click", function(e) {
            e.preventDefault();
            
            // Criar overlay de transição personalizado se não existir
            var transitionOverlay = document.getElementById("transitionOverlay");
            if (!transitionOverlay) {
                transitionOverlay = document.createElement("div");
                transitionOverlay.id = "transitionOverlay";
                transitionOverlay.style.position = "fixed";
                transitionOverlay.style.top = "0";
                transitionOverlay.style.left = "0";
                transitionOverlay.style.width = "100%";
                transitionOverlay.style.height = "100%";
                transitionOverlay.style.backgroundColor = "white";
                transitionOverlay.style.zIndex = "99999";
                transitionOverlay.style.opacity = "0";
                transitionOverlay.style.transition = "opacity 0.15s ease";
                transitionOverlay.style.pointerEvents = "none";
                document.body.appendChild(transitionOverlay);
            }
            
            // Mostrar overlay de transição imediatamente
            transitionOverlay.style.opacity = "1";
            transitionOverlay.style.pointerEvents = "all";
            
            // Navegar após um pequeno delay - muito curto para evitar piscar
            setTimeout(function() {
                window.location.href = link.href;
            }, 50);
        });
    });
});
</script>
';

include __DIR__ . '/../views/layouts/main.php';
