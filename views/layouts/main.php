<!DOCTYPE html>
<html>
<head>
    <title><?= $pageTitle ?? 'Sistema Administrativo KW24' ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Rubik:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="/Apps/assets/css/main.css">
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
                <a href="index.php" class="sidebar-link <?= $activeMenu === 'dashboard' ? 'active' : '' ?>" title="Dashboard">
                    <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
                    <div class="menu-tooltip">Dashboard</div>
                </a>
                <a href="logs.php" class="sidebar-link <?= $activeMenu === 'logs' ? 'active' : '' ?>" title="Logs">
                    <i class="fas fa-file-alt"></i> <span>Logs</span>
                    <div class="menu-tooltip">Logs</div>
                </a>
                <a href="#" class="sidebar-link" title="Clientes (Em breve)">
                    <i class="fas fa-users"></i> <span>Clientes</span>
                    <div class="menu-tooltip">Clientes</div>
                </a>
                <a href="#" class="sidebar-link" title="Aplicações (Em breve)">
                    <i class="fas fa-cogs"></i> <span>Aplicações</span>
                    <div class="menu-tooltip">Aplicações</div>
                </a>
            </div>
        </div>
        <div class="user-panel">
            <div class="user-info"><?= htmlspecialchars($_SESSION['logviewer_user'] ?? 'Usuário') ?></div>
            <form method="post" action="logout.php">
                <button type="submit" class="logout-btn" title="Sair">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Sair</span>
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
</body>
</html>
