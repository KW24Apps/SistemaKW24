<?php
// $allowedPagesByProfile vem do index.php (null = irrestrito, array = lista de páginas permitidas)
function _sidebarOk(string $key): bool {
    global $allowedPagesByProfile;
    return $allowedPagesByProfile === null || in_array($key, $allowedPagesByProfile, true);
}
function _sidebarGroupOk(array $keys): bool {
    global $allowedPagesByProfile;
    if ($allowedPagesByProfile === null) return true;
    foreach ($keys as $k) {
        if (in_array($k, $allowedPagesByProfile, true)) return true;
    }
    return false;
}
$_sidebarAllowedJson = $allowedPagesByProfile === null ? 'null' : json_encode($allowedPagesByProfile);
?>
<nav class="sidebar" id="sidebar"
     data-perfil="<?= htmlspecialchars($user_data['perfil'] ?? '') ?>"
     data-allowed-menus="<?= htmlspecialchars($_sidebarAllowedJson) ?>">
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
        <?php if (_sidebarOk('dashboard')): ?>
        <li>
            <a href="?page=dashboard" class="sidebar-link active">
                <div class="sidebar-link-inner">
                    <span class="sidebar-link-icon"><i class="fas fa-home"></i></span>
                    <span class="sidebar-link-text">Dashboard</span>
                </div>
            </a>
        </li>
        <?php endif; ?>
        <?php if (_sidebarGroupOk(['cadastro', 'usuarios', 'aplicacoes', 'permissoes', 'organizacoes'])): ?>
        <li>
            <a href="?page=cadastro" class="sidebar-link">
                <div class="sidebar-link-inner">
                    <span class="sidebar-link-icon"><i class="fas fa-plus-circle"></i></span>
                    <span class="sidebar-link-text">Cadastro</span>
                </div>
            </a>
        </li>
        <?php endif; ?>
        <?php if (_sidebarGroupOk(['financeiro', 'financeiro-relatorios', 'portais'])): ?>
        <li>
            <a href="?page=financeiro" class="sidebar-link">
                <div class="sidebar-link-inner">
                    <span class="sidebar-link-icon"><i class="fas fa-dollar-sign"></i></span>
                    <span class="sidebar-link-text">Financeiro</span>
                </div>
            </a>
        </li>
        <?php endif; ?>
        <li style="padding:.25rem 0" aria-hidden="true">
            <div style="height:1px;background:rgba(255,255,255,.13);margin:0 1rem"></div>
        </li>
        <?php if (_sidebarGroupOk(['relatorio-teste', 'portais-bi'])): ?>
        <li>
            <a href="?page=relatorio-teste" class="sidebar-link">
                <div class="sidebar-link-inner">
                    <span class="sidebar-link-icon"><i class="fas fa-chart-bar"></i></span>
                    <span class="sidebar-link-text">Relatórios BI</span>
                </div>
            </a>
        </li>
        <?php endif; ?>
    </ul>

    <!-- Menu Admin no FINAL DA SIDEBAR (separado) -->
    <?php if (isset($user_data['perfil']) && $user_data['perfil'] === 'admin_interno'): ?>
    <div class="sidebar-footer">
        <ul class="sidebar-admin-menu">
            <li>
                <a href="?page=configuracoes" class="sidebar-link sidebar-admin-item">
                    <div class="sidebar-link-inner">
                        <span class="sidebar-link-icon"><i class="fas fa-cog"></i></span>
                        <span class="sidebar-link-text">Configurações</span>
                    </div>
                </a>
            </li>
        </ul>
    </div>
    <?php endif; ?>
</nav>
