<?php
session_start();
require_once __DIR__ . '/../includes/helpers.php';
requireAuthentication();

$pageTitle = 'Log Viewer - Sistema KW24';
$activeMenu = 'logs';

ob_start();
?>

<div class="log-viewer-container">
    <h2>Área de Logs</h2>
    <p>Página em branco para testes da barra lateral.</p>
</div>

<?php
$content = ob_get_clean();

if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    echo $content;
    exit;
}

include __DIR__ . '/../views/layouts/main.php';
?>
