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
    <?php
    $sidebarState = $sidebarState ?? '';
    $activeMenu = $activeMenu ?? '';
    ?>
    <?php include __DIR__ . '/Apps/views/layouts/sidebar.php'; ?>
    
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
