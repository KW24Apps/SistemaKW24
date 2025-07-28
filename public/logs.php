<?php
session_start();
require_once __DIR__ . '/../includes/helpers.php';
requireAuthentication();

$pageTitle = 'Logs - Sistema KW24';
$activeMenu = 'logs';

// Determina qual submenu mostrar
$sub = isset($_GET['sub']) ? $_GET['sub'] : 'filtro';

// Valida subpáginas permitidas
$validSubs = ['filtro', 'download'];
if (!in_array($sub, $validSubs)) {
    $sub = 'filtro';
}

ob_start();
?>
<!-- Submenu na topbar -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const submenuHtml = `
        <div class="logs-submenu">
            <button class="logs-submenu-btn <?= $sub === 'filtro' ? 'active' : '' ?>" data-page="filtro">
                <i class="fas fa-filter"></i> Filtro
            </button>
            <button class="logs-submenu-btn <?= $sub === 'download' ? 'active' : '' ?>" data-page="download">
                <i class="fas fa-download"></i> Download
            </button>
        </div>
        <div class="logs-submenu-separator"></div>
    `;
    
    const submenuContainer = document.querySelector('.topbar-submenu');
    if (submenuContainer) {
        submenuContainer.innerHTML = submenuHtml;
        
        // Adiciona eventos aos botões
        document.querySelectorAll('.logs-submenu-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const page = this.dataset.page;
                window.location.href = '/Apps/public/logs.php?sub=' + page;
            });
        });
    }
});
</script>

<div class="logs-content">
    <?php if ($sub === 'filtro'): ?>
        <!-- CONTEÚDO FILTRO -->
        <div class="logs-filtro-container">
            <h1>Filtro de Logs</h1>
            <p>Você está na subpágina <strong>Filtro</strong>.</p>
            <!-- Aqui será implementado o conteúdo de filtro -->
        </div>

    <?php elseif ($sub === 'download'): ?>
        <!-- CONTEÚDO DOWNLOAD -->
        <div class="logs-download-container">
            <h1>Download de Logs</h1>
            <p>Você está na subpágina <strong>Download</strong>.</p>
            <!-- Aqui será implementado o conteúdo de download -->
        </div>

    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();

$additionalCSS = '<link rel="stylesheet" href="/Apps/assets/css/logs.css">';
$additionalJS  = '<script src="/Apps/assets/js/logs.js"></script>';

// Layout base
include __DIR__ . '/../views/layouts/main.php';
