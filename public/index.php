<?php
/**
 * Dashboard Principal - Sistema KW24
 */

session_start();

// Incluir dependências
require_once __DIR__ . '/../includes/helpers.php';

// Verificar autenticação
requireAuthentication();

// Configurações da página
$pageTitle = 'Dashboard - Sistema KW24';
$activeMenu = 'dashboard';
$sidebarState = $_GET['sidebar'] ?? '';

// Conteúdo da página
ob_start();
?>

<div class="welcome-message">
    <h2>Bem-vindo!</h2>
</div>

<?php
$content = ob_get_clean();

// BLOCO AJAX
if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
    echo $content;
    exit;
}

// Incluir o layout principal
include __DIR__ . '/../views/layouts/main.php';
?>
?>
