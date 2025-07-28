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
        
        // AJAX para navegação dos submenus
        document.querySelectorAll('.logs-submenu-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const page = this.dataset.page;
                
                // Remove active de todos os botões
                document.querySelectorAll('.logs-submenu-btn').forEach(b => b.classList.remove('active'));
                // Adiciona active no botão clicado
                this.classList.add('active');
                
                // Carrega conteúdo via AJAX
                loadLogsContent(page);
            });
        });
    }
});

function loadLogsContent(page) {
    const mainContent = document.querySelector('.logs-content');
    if (!mainContent) return;
    
    // Mostra loading
    mainContent.innerHTML = '<div class="loading-container"><div class="loading-spinner"></div><span>Carregando...</span></div>';
    
    // Faz requisição AJAX
    fetch(`/Apps/public/ajax/logs-content.php?sub=${page}`)
        .then(response => response.text())
        .then(html => {
            mainContent.innerHTML = html;
            
            // Reexecuta scripts se necessário
            const scripts = mainContent.querySelectorAll('script');
            scripts.forEach(script => {
                const newScript = document.createElement('script');
                newScript.textContent = script.textContent;
                script.replaceWith(newScript);
            });
            
            // Atualiza URL sem recarregar página
            history.pushState({}, '', `/Apps/public/logs.php?sub=${page}`);
        })
        .catch(error => {
            console.error('Erro ao carregar conteúdo:', error);
            mainContent.innerHTML = '<div class="error-container">Erro ao carregar conteúdo. Tente novamente.</div>';
        });
}
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
