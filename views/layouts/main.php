<!DOCTYPE html>
<html>
<head>
    <title><?= $pageTitle ?? 'Sistema Administrativo KW24' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Prevenir flash de conteúdo não estilizado -->
    <style>
        .js-loading *, .js-loading *:before, .js-loading *:after {
            animation-play-state: paused !important;
        }
        html.js-loading {
            visibility: hidden;
        }
    </style>
    <script>
        // Esconder todo o HTML até que esteja pronto (apagar)
        document.documentElement.classList.add('js-loading');
        
        // Criar overlay de carregamento inicial
        document.addEventListener('DOMContentLoaded', function() {
            // Criar overlay de inicialização
            var initialOverlay = document.createElement('div');
            initialOverlay.id = 'initialLoadOverlay';
            initialOverlay.style.position = 'fixed';
            initialOverlay.style.top = '0';
            initialOverlay.style.left = '0';
            initialOverlay.style.width = '100%';
            initialOverlay.style.height = '100%';
            initialOverlay.style.backgroundColor = 'white';
            initialOverlay.style.zIndex = '99999';
            initialOverlay.style.display = 'flex';
            initialOverlay.style.justifyContent = 'center';
            initialOverlay.style.alignItems = 'center';
            
            // Adicionar spinner
            var spinner = document.createElement('div');
            spinner.style.width = '50px';
            spinner.style.height = '50px';
            spinner.style.border = '5px solid rgba(8, 107, 141, 0.1)';
            spinner.style.borderTop = '5px solid #086B8D';
            spinner.style.borderRadius = '50%';
            spinner.style.animation = 'initialSpin 0.8s linear infinite';
            
            // Adicionar estilo para animação
            var style = document.createElement('style');
            style.textContent = '@keyframes initialSpin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
            document.head.appendChild(style);
            
            initialOverlay.appendChild(spinner);
            document.body.appendChild(initialOverlay);
        });
        
        // Revelar página quando estiver completamente carregada
        window.addEventListener('load', function() {
            // Remover classe de loading após pequeno delay para garantir que todos os estilos foram aplicados
            setTimeout(function() {
                document.documentElement.classList.remove('js-loading');
                
                // Esconder overlay inicial com uma transição suave
                var initialOverlay = document.getElementById('initialLoadOverlay');
                if (initialOverlay) {
                    initialOverlay.style.transition = 'opacity 0.3s ease-out';
                    initialOverlay.style.opacity = '0';
                    setTimeout(function() {
                        initialOverlay.remove();
                    }, 300);
                }
            }, 300);
        });
    </script>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/Apps/assets/css/main.css">
    <link rel="stylesheet" href="/Apps/assets/css/sidebar-fixes.css">
    <?= $additionalCSS ?? '' ?>
</head>
<body class="<?= $sidebarState === 'collapsed' ? 'sidebar-collapsed' : '' ?>">
    <div class="container">
    <!-- Menu Lateral -->
    <div class="sidebar <?= $sidebarState === 'collapsed' ? 'collapsed' : '' ?>">
        <button id="sidebarToggle" class="toggle-btn" title="Expandir/Recolher Menu">
            <i class="fas fa-angle-left"></i>
        </button>
        <div class="logo-container">
            <img src="https://gabriel.kw24.com.br/02_KW24_HORIZONTAL_NEGATIVO.png" alt="KW24 Logo">
        </div>
        <div class="sidebar-content">
            <div class="sidebar-menu">
                <a href="index.php" class="sidebar-link ajax-link <?= $activeMenu === 'dashboard' ? 'active' : '' ?>" title="Dashboard">
                    <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
                    <div class="menu-tooltip">Dashboard</div>
                </a>
                <a href="#" class="sidebar-link" title="Clientes (Em breve)">
                    <i class="fas fa-users"></i> <span>Clientes</span>
                    <div class="menu-tooltip">Clientes</div>
                </a>
                <a href="#" class="sidebar-link" title="Aplicações (Em breve)">
                    <i class="fas fa-cogs"></i> <span>Aplicações</span>
                    <div class="menu-tooltip">Aplicações</div>
                </a>
                <a href="logs.php" class="sidebar-link ajax-link <?= $activeMenu === 'logs' ? 'active' : '' ?>" title="Logs">
                    <i class="fas fa-file-alt"></i> <span>Logs</span>
                    <div class="menu-tooltip">Logs</div>
                </a>
            </div>
        </div>
        <div class="user-panel">
            <a href="#" class="sidebar-link user-link" title="Perfil de Usuário">
                <i class="fas fa-user-circle"></i> <span><?= htmlspecialchars($_SESSION['logviewer_user'] ?? 'Usuário') ?></span>
                <div class="menu-tooltip">Perfil</div>
            </a>
            <form method="post" action="logout.php" style="flex: 1;">
                <button type="submit" class="logout-btn" title="Sair">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sair</span>
                    <div class="menu-tooltip">Sair</div>
                </button>
            </form>
        </div>
    </div>
    
    <!-- Conteúdo Principal -->
    <div class="main-content">
        <?php if (isset($pageTitle) && $pageTitle !== 'Dashboard - Sistema KW24'): ?>
            <div class="page-header">
                <h1><?= $pageTitle ?></h1>
                <?php if (isset($pageActions)): ?>
                    <div class="page-actions">
                        <?= $pageActions ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>
        
        <div class="content-area">
            <?= $content ?>
        </div>
        
        <div class="footer">
            <p>Sistema Administrativo KW24 v1.0 - <?= date('Y') ?></p>
        </div>
    </div>
    
    <script src="/Apps/assets/js/main.js"></script>
    <?= $additionalJS ?? '' ?>
    
    <?php if ($activeMenu === 'logs'): ?>
    <script>
        // Garante que o menu lateral expanda quando a página de logs é carregada
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.sidebar');
            const body = document.body;
            
            if (sidebar && sidebar.classList.contains('collapsed')) {
                sidebar.classList.remove('collapsed');
                body.classList.remove('sidebar-collapsed');
                
                const toggleBtn = document.getElementById('sidebarToggle');
                if (toggleBtn && toggleBtn.querySelector('i')) {
                    toggleBtn.querySelector('i').className = 'fas fa-angle-left';
                }
            }
            
            // Garante fundo branco na página de logs
            document.body.style.background = 'white';
            var mainContent = document.querySelector('.main-content');
            if (mainContent) {
                mainContent.style.background = 'white';
            }
        });
    </script>
    <?php endif; ?>
    

</body>
</html>
