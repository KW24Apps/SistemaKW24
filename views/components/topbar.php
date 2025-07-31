<?php
/**
 * TOPBAR V2 - ESTRUTURA HTML SEMÂNTICA
 * Componente topbar otimizado e acessível
 */
?>

<div class="topbar" role="banner" aria-label="Barra superior de navegação">
    
    <!-- Submenus Dinâmicos - Primeira seção (esquerda) -->
    <nav class="topbar-submenus" 
         role="navigation" 
         aria-label="Submenus dinâmicos"
         aria-live="polite">
        
        <div class="submenu-container" role="menubar">
            <!-- Área limpa para submenus dinâmicos -->
        </div>
        
    </nav>
    
    <!-- Logo Section - Segunda seção (meio-direita) -->
    <div class="topbar-logo" role="img" aria-label="Logo KW24">
        <img src="/Apps/assets/img/Logo_TOPBAR.png" 
             alt="KW24 Logo" 
             title="KW24 - Sistema de Gestão"
             onerror="this.src='/Apps/assets/img/03_KW24_BRANCO1.png'">
    </div>
    
    <!-- Profile Section - Terceira seção (direita) -->
    <div class="topbar-profile" 
         role="button" 
         aria-haspopup="true" 
         aria-expanded="false"
         aria-label="Menu do usuário"
         tabindex="0">
        
        <!-- Avatar --> 
        <i class="fas fa-user-circle topbar-profile-avatar" aria-hidden="true"></i>
        
        <!-- Informações do usuário -->
        <div class="topbar-profile-info">
            <p class="topbar-profile-name" id="profile-name">
                <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuário'); ?>
            </p>
            <p class="topbar-profile-role" id="profile-role">
                <?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Administrador'); ?>
            </p>
        </div>
        
        <!-- Ícone dropdown -->
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

<style>
/* Estilos específicos para o componente */
.dropdown-divider {
    height: 1px;
    background: rgba(255,255,255,0.2);
    margin: 4px 0;
}
</style>

<script>
// Configurações específicas do componente
document.addEventListener('DOMContentLoaded', function() {
    
    // Dados do usuário para JavaScript
    window.currentUser = {
        name: '<?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Usuário'); ?>',
        role: '<?php echo htmlspecialchars($_SESSION['user_role'] ?? 'Administrador'); ?>',
        avatar: 'icon', // Usando ícone FontAwesome
        id: '<?php echo $_SESSION['user_id'] ?? '1'; ?>'
    };
    
    console.log('Topbar component loaded with user:', window.currentUser);
});
</script>
