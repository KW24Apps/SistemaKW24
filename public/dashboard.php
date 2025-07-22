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
    <div style="text-align:center; color:#2a4a5a; font-size:1.1em;">
      Área branca centralizada para teste.<br>
      Altere o estado da sidebar (minimizada/maximizada) para validar o alinhamento.
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();

$additionalCSS = '<link rel="stylesheet" href="/Apps/assets/css/area-atuacao.css">';
$additionalJS  = '';

// Layout base (sidebar, etc)
include __DIR__ . '/../views/layouts/main.php';
?>
