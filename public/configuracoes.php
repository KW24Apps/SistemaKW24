<?php
/**
 * CONFIGURAÇÕES - Página administrativa do sistema
 * Este arquivo contém apenas o conteúdo específico de configurações
 * O layout (sidebar, topbar, head) está no index.php
 */

// Verificação de segurança - este arquivo só pode ser incluído
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /Apps/public/login.php');
    exit;
}

// PROTEÇÃO DUPLA: Verificação de permissão administrativa
if (!isset($user_data['perfil']) || $user_data['perfil'] !== 'Administrador') {
    // Se chegou aqui sem ser admin, é tentativa de acesso indevido
    error_log("Tentativa de acesso não autorizado à área admin - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    header('Location: ?page=dashboard&error=unauthorized');
    exit;
}

// Verifica ação dos submenus
$action = $_GET['action'] ?? 'main';
?>

<h1>Configurações - KW24 Sistema</h1>

<div class="welcome-section">
    <p><strong>Usuário:</strong> <?php echo htmlspecialchars($user_data['nome']); ?></p>
    <p><strong>Seção:</strong> Configurações Administrativas
    <?php if ($action !== 'main'): ?>
        - <?php echo ucfirst($action); ?>
    <?php endif; ?>
    </p>
</div>

<div class="page-content">
    <?php if ($action === 'colaboradores'): ?>
        <h2>⚙️ Gestão de Colaboradores</h2>
        <p>Gerenciamento de usuários do sistema.</p>
        
        <div class="admin-panel">
            <h3>Funcionalidades</h3>
            <ul>
                <li>📝 Cadastrar novos colaboradores</li>
                <li>✏️ Editar informações existentes</li>
                <li>🔒 Alterar permissões de acesso</li>
                <li>🚫 Ativar/Desativar usuários</li>
                <li>🔑 Resetar senhas</li>
            </ul>
            
            <div class="admin-actions">
                <button class="btn-admin primary">
                    <i class="fas fa-plus"></i> Novo Colaborador
                </button>
                <button class="btn-admin secondary">
                    <i class="fas fa-list"></i> Listar Todos
                </button>
            </div>
        </div>
        
    <?php elseif ($action === 'permissoes'): ?>
        <h2>🛡️ Gestão de Permissões</h2>
        <p>Controle de acesso e níveis de usuário.</p>
        
        <div class="admin-panel">
            <h3>Níveis de Acesso</h3>
            <div class="permission-levels">
                <div class="level-card admin">
                    <h4>👑 Administrador</h4>
                    <p>Acesso total ao sistema</p>
                </div>
                <div class="level-card supervisor">
                    <h4>👮 Supervisor</h4>
                    <p>Acesso moderado com supervisão</p>
                </div>
                <div class="level-card user">
                    <h4>👤 Usuário</h4>
                    <p>Acesso básico limitado</p>
                </div>
            </div>
        </div>
        
    <?php elseif ($action === 'sistema'): ?>
        <h2>🔧 Configurações do Sistema</h2>
        <p>Parâmetros gerais e manutenção.</p>
        
        <div class="admin-panel">
            <h3>Configurações</h3>
            <ul>
                <li>🗃️ Backup do banco de dados</li>
                <li>🧹 Limpeza de logs antigos</li>
                <li>⚡ Cache do sistema</li>
                <li>📧 Configurações de email</li>
                <li>🔐 Políticas de senha</li>
            </ul>
        </div>
        
    <?php else: ?>
        <h2>⚙️ Painel Administrativo</h2>
        <p>Utilize os submenus no topbar para acessar as funcionalidades administrativas.</p>
        
        <div class="admin-overview">
            <h3>Áreas Disponíveis</h3>
            <div class="admin-cards">
                <div class="admin-card">
                    <i class="fas fa-users-cog"></i>
                    <h4>Colaboradores</h4>
                    <p>Gestão de usuários e acesso</p>
                </div>
                <div class="admin-card">
                    <i class="fas fa-shield-alt"></i>
                    <h4>Permissões</h4>
                    <p>Controle de níveis de acesso</p>
                </div>
                <div class="admin-card">
                    <i class="fas fa-tools"></i>
                    <h4>Sistema</h4>
                    <p>Configurações gerais</p>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<style>
.admin-panel {
    background: #f8f9fa;
    padding: 1.5rem;
    border-radius: 8px;
    margin: 1rem 0;
    border-left: 4px solid #007bff;
}

.admin-actions {
    margin-top: 1.5rem;
    display: flex;
    gap: 1rem;
}

.btn-admin {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 6px;
    cursor: pointer;
    font-weight: bold;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.btn-admin.primary {
    background: #007bff;
    color: white;
}

.btn-admin.secondary {
    background: #6c757d;
    color: white;
}

.btn-admin:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

.permission-levels {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin-top: 1rem;
}

.level-card {
    padding: 1rem;
    border-radius: 6px;
    text-align: center;
    border: 2px solid;
}

.level-card.admin {
    background: #fff5f5;
    border-color: #dc3545;
}

.level-card.supervisor {
    background: #fff8e1;
    border-color: #ffc107;
}

.level-card.user {
    background: #f0f8ff;
    border-color: #007bff;
}

.admin-overview {
    margin-top: 2rem;
}

.admin-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1.5rem;
    margin-top: 1rem;
}

.admin-card {
    background: white;
    padding: 2rem;
    border-radius: 8px;
    text-align: center;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border: 1px solid #e9ecef;
    transition: transform 0.2s;
}

.admin-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.admin-card i {
    font-size: 2.5rem;
    color: #007bff;
    margin-bottom: 1rem;
}

.admin-card h4 {
    margin: 0.5rem 0;
    color: #333;
}

.admin-card p {
    color: #666;
    margin: 0;
}
</style>
