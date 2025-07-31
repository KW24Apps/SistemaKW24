<?php
/**
 * DASHBOARD - Página inicial do sistema
 * Este arquivo contém apenas o conteúdo específico do dashboard
 * O layout (sidebar, topbar, head) está no index.php
 */

// Verificação de segurança - este arquivo só pode ser incluído
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /Apps/public/login.php');
    exit;
}
?>

<h1>Dashboard - KW24 Sistema</h1>

<div class="welcome-section">
    <p><strong>Usuário:</strong> <?php echo htmlspecialchars($user_data['nome']); ?></p>
    <p><strong>Perfil:</strong> <?php echo htmlspecialchars($user_data['perfil']); ?></p>
    <p><strong>Último Login:</strong> <?php echo date('d/m/Y H:i:s', $user_data['login_time']); ?></p>
</div>

<div class="dashboard-content">
    <h2>Bem-vindo ao Sistema KW24</h2>
    <p>Utilize o menu lateral para navegar pelos módulos do sistema.</p>
    
    <div class="quick-actions">
        <h3>Ações Rápidas</h3>
        <ul>
            <li><a href="?page=cadastro">Novo Cadastro</a></li>
            <li><a href="?page=relatorio">Relatórios</a></li>
            <li><a href="?page=logs">Logs do Sistema</a></li>
        </ul>
    </div>
    
    <!-- Conteúdo futuro do dashboard -->
    <div class="dashboard-widgets">
        <h3>Resumo do Sistema</h3>
        <p>Esta seção conterá widgets, gráficos e informações relevantes do dashboard.</p>
        <p>Será implementada posteriormente conforme necessidades específicas.</p>
    </div>
</div>
