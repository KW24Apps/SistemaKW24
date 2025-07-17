<?php
// public/dashboard.php

session_start();
require_once __DIR__ . '/../includes/helpers.php';
requireAuthentication();

$pageTitle = 'Dashboard - Sistema KW24';
$activeMenu = 'dashboard';

// Conteúdo do dashboard
ob_start();
?>
<div class="dashboard-container">
    <h1 id="dashboard-title">Dashboard</h1>
    <div id="dashboard-date" class="dashboard-date"></div>
    <button id="btn-refresh-dashboard">Atualizar</button>
    <div id="dashboard-loader" class="dashboard-loader" style="display:none"></div>
</div>
<?php
$content = ob_get_clean();

$additionalCSS = '<link rel="stylesheet" href="/Apps/assets/css/dashboard.css">';
$additionalJS  = '<script src="/Apps/assets/js/dashboard.js"></script>';

// Layout base (sidebar, etc)
include __DIR__ . '/../views/layouts/main.php';
