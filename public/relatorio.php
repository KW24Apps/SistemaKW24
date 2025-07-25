<?php
// public/relatorio.php

session_start();
require_once __DIR__ . '/../includes/helpers.php';
requireAuthentication();

$pageTitle = 'Relatorio - Sistema KW24';
$activeMenu = 'relatorio';

// Conteúdo do relatorio
ob_start();
?>
<div class="dashboard-container">
    <h1 id="dashboard-title">Relatorio</h1>
    <div id="dashboard-date" class="dashboard-date" aria-live="polite"><?= date('Y-m-d H:i:s') ?></div>
    <button id="btn-refresh-dashboard">Atualizar</button>
    <div id="dashboard-loader" class="dashboard-loader" style="display:none">
        <span class="loading-spinner"></span>
        <span class="loading-text">Atualizando...</span>
    </div>
</div>
<?php
$content = ob_get_clean();

$additionalCSS = '<link rel="stylesheet" href="/Apps/assets/css/dashboard.css">';
$additionalJS  = '<script src="/Apps/assets/js/dashboard.js"></script>';

// Layout base (sidebar, etc)
include __DIR__ . '/../views/layouts/main.php';
?>
