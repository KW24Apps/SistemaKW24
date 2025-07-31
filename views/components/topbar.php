<?php
/**
 * TOPBAR - Componente topbar otimizado
 */
?>

<div class="topbar" role="banner" aria-label="Barra superior de navegação">
    
    <!-- Submenus Dinâmicos -->
    <nav class="topbar-submenus" 
         role="navigation" 
         aria-label="Submenus dinâmicos"
         aria-live="polite">
        
        <div class="submenu-container" role="menubar">
            <!-- Área para submenus dinâmicos -->
        </div>
        
    </nav>
    
    <!-- Logo Section -->
    <div class="topbar-logo" role="img" aria-label="Logo KW24">
        <img src="/Apps/assets/img/Logo_TOPBAR.png" 
             alt="KW24 Logo" 
             title="KW24 - Sistema de Gestão"
             onerror="this.src='/Apps/assets/img/03_KW24_BRANCO1.png'">
    </div>
    
    <!-- Profile Section -->
    <div class="topbar-profile" 
         role="button" 
         aria-haspopup="true" 
         aria-expanded="false"
         aria-label="Menu do usuário"
         tabindex="0">
        
        <i class="fas fa-user-circle topbar-profile-avatar" aria-hidden="true"></i>
        
        <div class="topbar-profile-info">
            <p class="topbar-profile-name" id="profile-name">
                <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuário'); ?>
            </p>
            <p class="topbar-profile-role" id="profile-role">
                <?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Administrador'); ?>
            </p>
        </div>
        
        <i class="fas fa-chevron-down topbar-profile-icon" aria-hidden="true"></i>
        
        <!-- Dropdown Menu -->
        <div class="topbar-profile-dropdown" 
             role="menu" 
             aria-labelledby="profile-name"
             aria-hidden="true">
            
            <a href="/Apps/public/profile.php" 
               class="dropdown-item" 
               role="menuitem"
               tabindex="-1">
                <i class="fas fa-user" aria-hidden="true"></i>
                <span>Meu Perfil</span>
            </a>
            
            <a href="/Apps/public/settings.php" 
               class="dropdown-item" 
               role="menuitem"
               tabindex="-1">
                <i class="fas fa-cog" aria-hidden="true"></i>
                <span>Configurações</span>
            </a>
            
            <a href="/Apps/public/help.php" 
               class="dropdown-item" 
               role="menuitem"
               tabindex="-1">
                <i class="fas fa-question-circle" aria-hidden="true"></i>
                <span>Ajuda</span>
            </a>
            
            <div class="dropdown-divider" role="separator"></div>
            
            <a href="/Apps/public/logout.php" 
               class="dropdown-item" 
               role="menuitem"
               tabindex="-1">
                <i class="fas fa-sign-out-alt" aria-hidden="true"></i>
                <span>Sair</span>
            </a>
            
        </div>
        
    </div>
    
</div>
