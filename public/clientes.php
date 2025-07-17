<?php
// public/clientes.php

session_start();
require_once __DIR__ . '/../includes/helpers.php';
requireAuthentication();

$pageTitle = 'Clientes - Sistema KW24';
$activeMenu = 'clientes';

// Conteúdo da página Clientes
ob_start();
?>
<div class="clientes-container">
    <h1 id="clientes-title">Clientes</h1>
    <div id="clientes-date" class="clientes-date" aria-live="polite"><?php echo date('Y-m-d H:i:s'); ?></div>
    <button id="btn-refresh-clientes">Atualizar</button>
    <div id="clientes-loader" class="clientes-loader" style="display:none">
        <span class="loading-spinner"></span>
        <span class="loading-text">Atualizando...</span>
    </div>
</div>
<?php
$content = ob_get_clean();

$additionalCSS = '<link rel="stylesheet" href="/Apps/assets/css/clientes.css">';
$additionalJS  = '<script src="/Apps/assets/js/clientes.js"></script>';

// Layout base (sidebar, etc)
include __DIR__ . '/../views/layouts/main.php';
