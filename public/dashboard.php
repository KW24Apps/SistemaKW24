<?php
// public/dashboard.php

session_start();
require_once __DIR__ . '/../includes/helpers.php';
requireAuthentication();

$pageTitle = 'Dashboard - Sistema KW24';
$activeMenu = 'dashboard';

ob_start();
?>
<div style="text-align:center; color:#2a4a5a; font-size:1.1em;">
  Conteúdo do dashboard aqui.<br>
  Teste a responsividade da área branca ao alternar a sidebar.
</div>
<?php
$content = ob_get_clean();

// Layout base (sidebar, área branca, etc)
include __DIR__ . '/../views/layouts/main.php';
?>
