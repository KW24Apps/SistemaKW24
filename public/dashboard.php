<?php
// public/dashboard.php (limpo)

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
    </div>
    <!-- TESTE: Visualize a responsividade e alinhamento da área branca -->
    <div class="atuacao-teste-info">
      <p>Esta área branca deve estar centralizada e responsiva.<br>
      Altere o estado da sidebar (minimizada/maximizada) para testar o alinhamento.<br>
      <span style="font-size:0.95em;color:#888;">(Remova este bloco após validar o layout)</span></p>
    </div>
  </div>
</div>
<?php
$content = ob_get_clean();

$additionalCSS = '<link rel="stylesheet" href="/Apps/assets/css/dashboard.css">\n<link rel="stylesheet" href="/Apps/assets/css/area-atuacao.css">';
$additionalJS  = '<script src="/Apps/assets/js/dashboard.js"></script>';

// Layout base (sidebar, etc)
include __DIR__ . '/../views/layouts/main.php';
?>
