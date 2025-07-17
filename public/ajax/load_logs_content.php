<?php
session_start();
require_once __DIR__ . '/../../includes/helpers.php';
requireAuthentication();

ob_start();
?>
<div class="content-area">
    <h2>Área AJAX de Logs</h2>
    <p>Página em branco para testes da barra lateral.</p>
</div>
<?php
$content = ob_get_clean();
echo $content;
?>
