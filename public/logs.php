<?php
/**
 * Visualizador de Logs - Sistema KW24
 * Página organizada com MVC e arquivos separados
 */

session_start();

// Incluir dependências
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../controllers/LogController.php';

// Verificar autenticação
requireAuthentication();

// Configurações da página
$pageTitle = 'Log Viewer - Sistema KW24';
$activeMenu = 'logs';
$sidebarState = $_GET['sidebar'] ?? '';

// Instanciar controller de logs
$logController = new LogController();

// Processar ações
$action = $_GET['action'] ?? 'view';
$domain = $_GET['domain'] ?? '';
$date = $_GET['date'] ?? date('Y-m-d');
$traceId = $_GET['trace'] ?? '';

switch ($action) {
    case 'download':
        $logController->downloadLog($domain, $date);
        exit;
    
    case 'clear':
        $logController->clearLog($domain, $date);
        header("Location: logs.php?domain=$domain&date=$date");
        exit;
        
    default:
        $logs = $logController->getLogs($domain, $date, $traceId);
        $domains = $logController->getAvailableDomains();
        break;
}

// CSS adicional para esta página
$additionalCSS = '<link rel="stylesheet" href="/Apps/assets/css/logs.css">';

// JavaScript adicional para esta página  
$additionalJS = '<script src="/Apps/assets/js/logs.js"></script>';

// Conteúdo da página
ob_start();
?>

<div class="log-viewer-container">
    <!-- Sidebar de Filtros -->
    <div class="log-sidebar">
        <div class="sidebar-header">
            <h3><i class="fas fa-filter"></i> Filtro</h3>
            <button class="btn-collapse" id="collapseSidebar">
                <i class="fas fa-chevron-left"></i>
            </button>
        </div>
        
        <div class="sidebar-content">
            <form method="GET" action="logs.php" class="filter-form">
                <!-- Seleção de Domínio -->
                <div class="form-group">
                    <label for="domain">Domínio:</label>
                    <select name="domain" id="domain" class="form-control">
                        <option value="">Todos os domínios</option>
                        <?php foreach ($domains as $d): ?>
                            <option value="<?= htmlspecialchars($d) ?>" <?= $domain === $d ? 'selected' : '' ?>>
                                <?= htmlspecialchars($d) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Seleção de Data -->
                <div class="form-group">
                    <label for="date">Data:</label>
                    <input type="date" name="date" id="date" value="<?= htmlspecialchars($date) ?>" class="form-control">
                </div>

                <!-- Filtro por Trace ID -->
                <div class="form-group">
                    <label for="trace">Trace ID:</label>
                    <select name="trace" id="trace" class="form-control">
                        <option value="">Todos os traces</option>
                        <?php if (isset($logs['traces'])): ?>
                            <?php foreach ($logs['traces'] as $t): ?>
                                <option value="<?= htmlspecialchars($t) ?>" <?= $traceId === $t ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($t) ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>

                <button type="submit" class="btn btn-primary btn-block">
                    <i class="fas fa-search"></i> Filtrar
                </button>
            </form>

            <!-- Ações Rápidas -->
            <div class="quick-actions">
                <h4>Ações Rápidas</h4>
                
                <?php if ($domain && $date): ?>
                    <a href="logs.php?action=download&domain=<?= urlencode($domain) ?>&date=<?= urlencode($date) ?>" 
                       class="btn btn-success btn-sm btn-block">
                        <i class="fas fa-download"></i> Download
                    </a>
                    
                    <button onclick="clearLog('<?= htmlspecialchars($domain) ?>', '<?= htmlspecialchars($date) ?>')" 
                            class="btn btn-danger btn-sm btn-block">
                        <i class="fas fa-trash"></i> Limpar Log
                    </button>
                <?php endif; ?>
                
                <button onclick="refreshLogs()" class="btn btn-info btn-sm btn-block">
                    <i class="fas fa-sync"></i> Atualizar
                </button>
                
                <button onclick="toggleAutoRefresh()" class="btn btn-secondary btn-sm btn-block" id="autoRefreshBtn">
                    <i class="fas fa-play"></i> Auto Refresh OFF
                </button>
            </div>
        </div>
    </div>

    <!-- Área Principal dos Logs -->
    <div class="log-content">
        <!-- Header com Informações -->
        <div class="log-header">
            <div class="log-info">
                <?php if (isset($logs['summary'])): ?>
                    <div class="log-summary">
                        <span class="summary-item">
                            <strong><?= $logs['summary']['total_files'] ?></strong> arquivo(s) de log processado(s)
                        </span>
                        <span class="summary-item">
                            <strong><?= $logs['summary']['total_entries'] ?></strong> registros encontrados para a data 
                            <strong><?= date('d/m/Y', strtotime($date)) ?></strong>
                        </span>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="log-actions">
                <button onclick="exportToCSV()" class="btn btn-outline-primary btn-sm">
                    <i class="fas fa-file-csv"></i> Exportar CSV
                </button>
                <button onclick="printLogs()" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-print"></i> Imprimir
                </button>
            </div>
        </div>

        <!-- Tabela de Logs -->
        <div class="log-table-container">
            <?php if (isset($logs['entries']) && !empty($logs['entries'])): ?>
                <table class="log-table" id="logTable">
                    <thead>
                        <tr>
                            <th class="col-origin">Origem</th>
                            <th class="col-datetime">Data</th>
                            <th class="col-trace">Trace</th>
                            <th class="col-message">Log</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($logs['entries'] as $entry): ?>
                            <tr class="log-entry" data-trace="<?= htmlspecialchars($entry['trace_id'] ?? '') ?>">
                                <td class="log-origin">
                                    <span class="origin-badge origin-<?= htmlspecialchars($entry['origin_type'] ?? 'default') ?>">
                                        <?= htmlspecialchars($entry['origin'] ?? 'N/A') ?>
                                    </span>
                                </td>
                                <td class="log-datetime">
                                    <div class="datetime-info">
                                        <span class="date"><?= htmlspecialchars($entry['date'] ?? '') ?></span>
                                        <span class="time"><?= htmlspecialchars($entry['time'] ?? '') ?></span>
                                    </div>
                                </td>
                                <td class="log-trace">
                                    <?php if (!empty($entry['trace_id'])): ?>
                                        <button class="trace-btn" onclick="filterByTrace('<?= htmlspecialchars($entry['trace_id']) ?>')">
                                            <?= htmlspecialchars($entry['trace_id']) ?>
                                        </button>
                                    <?php else: ?>
                                        <span class="no-trace">-</span>
                                    <?php endif; ?>
                                </td>
                                <td class="log-message">
                                    <div class="message-content">
                                        <?= nl2br(htmlspecialchars($entry['message'] ?? '')) ?>
                                    </div>
                                    <?php if (!empty($entry['details'])): ?>
                                        <button class="btn-details" onclick="toggleDetails(this)">
                                            <i class="fas fa-chevron-down"></i> Detalhes
                                        </button>
                                        <div class="message-details" style="display: none;">
                                            <pre><?= htmlspecialchars($entry['details']) ?></pre>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="no-logs">
                    <div class="no-logs-content">
                        <i class="fas fa-inbox fa-3x"></i>
                        <h3>Nenhum log encontrado</h3>
                        <p>Não há registros para os filtros selecionados.</p>
                        <p>Tente ajustar o domínio, data ou trace ID.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Incluir o layout principal
include __DIR__ . '/../views/layouts/main.php';
?>
