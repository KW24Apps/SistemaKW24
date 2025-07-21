<?php
// public/aplicacoes.php

session_start();
require_once __DIR__ . '/../includes/helpers.php';
requireAuthentication();

$pageTitle = 'Aplicações - Sistema KW24';
$activeMenu = 'aplicacoes';

// Conteúdo da página Aplicações
ob_start();
?>
<div class="aplicacoes-container">
    <h1 id="aplicacoes-title">Aplicações</h1>
    <div id="aplicacoes-date" class="aplicacoes-date" aria-live="polite"><?php echo date('Y-m-d H:i:s'); ?></div>
    <button id="btn-refresh-aplicacoes">Atualizar</button>
    <div id="aplicacoes-loader" class="aplicacoes-loader" style="display:none">
        <span class="loading-spinner"></span>
        <span class="loading-text">Atualizando...</span>
    </div>
</div>
<?php
$content = ob_get_clean();

$additionalCSS = '<link rel="stylesheet" href="/Apps/assets/css/aplicacoes.css">';
$additionalJS  = '<script src="/Apps/assets/js/aplicacoes.js"></script>';

// Layout base (sidebar, etc)
include __DIR__ . '/../views/layouts/main.php';
