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
            <a href="index.php?page=clientes" class="sidebar-link ajax-link <?= $activeMenu === 'clientes' ? 'active' : '' ?>" title="Clientes">
                <i class="fas fa-users"></i> <span>Clientes</span>
                <div class="menu-tooltip">Clientes</div>
            </a>
            <a href="index.php?page=aplicacoes" class="sidebar-link ajax-link <?= $activeMenu === 'aplicacoes' ? 'active' : '' ?>" title="Aplicações">
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
