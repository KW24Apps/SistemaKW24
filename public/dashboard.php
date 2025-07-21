<?php
// public/dashboard.php (limpo e corrigido)

session_start();
require_once __DIR__ . '/../includes/helpers.php';
requireAuthentication();

$pageTitle = 'Dashboard - Sistema KW24';
$activeMenu = 'dashboard';

ob_start();
?>
<div class="area-atuacao-wrapper">
  <div class="area-atuacao">
    <!-- Conteúdo futuro do dashboard -->
  </div>
</div>
<?php
$content = ob_get_clean();

$additionalCSS = '<link rel="stylesheet" href="/Apps/assets/css/area-atuacao.css">';
$additionalJS  = '';

// Layout base (sidebar, etc)
include __DIR__ . '/../views/layouts/main.php';
?>
