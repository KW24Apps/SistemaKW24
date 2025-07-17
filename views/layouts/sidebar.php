<?php echo 'Teste Sidebar'; ?>

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
            <!-- Adicione aqui os outros links do menu lateral, igual estava no seu main.php -->
        </div>
    </div>
</div>
