<?php
// public/logs.php

session_start();
require_once __DIR__ . '/../includes/helpers.php';
requireAuthentication();

$pageTitle = 'Logs - Sistema KW24';
$activeMenu = 'logs';

// Conteúdo da página Logs
ob_start();
?>
<div class="logs-container">
    <h1 id="logs-title">Logs</h1>
    <div id="logs-date" class="logs-date" aria-live="polite"><?php echo date('Y-m-d H:i:s'); ?></div>
    <button id="btn-refresh-logs">Atualizar</button>
    <div id="logs-loader" class="logs-loader" style="display:none">
        <span class="loading-spinner"></span>
        <span class="loading-text">Atualizando...</span>
    </div>
</div>
<?php
$content = ob_get_clean();

$additionalCSS = '<link rel="stylesheet" href="/Apps/assets/css/logs.css">';
$additionalJS  = '<script src="/Apps/assets/js/logs.js"></script>';

// Layout base (sidebar, etc)
include __DIR__ . '/../views/layouts/main.php';
