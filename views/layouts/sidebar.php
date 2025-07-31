<nav class="sidebar" id="sidebar">
    <!-- Header -->
    <div class="sidebar-header">
        <div class="sidebar-link">
            <div class="sidebar-link-inner">
                <span class="sidebar-link-icon">
                    <button class="sidebar-toggle" id="sidebarToggle" aria-label="Toggle sidebar">
                        <i class="fas fa-bars"></i>
                    </button>
                </span>
                <span class="sidebar-link-text">KW24</span>
            </div>
        </div>
    </div>

    <!-- Menu Principal -->
    <ul class="sidebar-menu">
        <li>
            <a href="?page=dashboard" class="sidebar-link active">
                <div class="sidebar-link-inner">
                    <span class="sidebar-link-icon"><i class="fas fa-home"></i></span>
                    <span class="sidebar-link-text">Dashboard</span>
                </div>
            </a>
        </li>
        <li>
            <a href="?page=cadastro" class="sidebar-link">
                <div class="sidebar-link-inner">
                    <span class="sidebar-link-icon"><i class="fas fa-plus-circle"></i></span>
                    <span class="sidebar-link-text">Cadastro</span>
                </div>
            </a>
        </li>
        <li>
            <a href="?page=relatorio" class="sidebar-link">
                <div class="sidebar-link-inner">
                    <span class="sidebar-link-icon"><i class="fas fa-chart-bar"></i></span>
                    <span class="sidebar-link-text">Relatórios</span>
                </div>
            </a>
        </li>
        <li>
            <a href="?page=logs" class="sidebar-link">
                <div class="sidebar-link-inner">
                    <span class="sidebar-link-icon"><i class="fas fa-file-alt"></i></span>
                    <span class="sidebar-link-text">Logs</span>
                </div>
            </a>
        </li>
        
        <!-- Menu Admin no final (sem divisor) -->
        <?php if (isset($user_data['perfil']) && $user_data['perfil'] === 'Administrador'): ?>
        <li>
            <a href="?page=configuracoes" class="sidebar-link sidebar-admin-item">
                <div class="sidebar-link-inner">
                    <span class="sidebar-link-icon"><i class="fas fa-cog"></i></span>
                    <span class="sidebar-link-text">Configurações</span>
                </div>
            </a>
        </li>
        <?php endif; ?>
    </ul>
</nav>
