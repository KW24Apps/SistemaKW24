<?php
/**
 * Ponto de entrada principal do sistema administrativo KW24
 */

session_start();

// Incluir dependências
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../controllers/LogController.php';

// Verificar autenticação
requireAuthentication();

// Configurações da página
$pageTitle = 'Dashboard - Sistema KW24';
$activeMenu = 'dashboard';
$sidebarState = $_GET['sidebar'] ?? '';

// Conteúdo da página
ob_start();
?>

<div class="dashboard-cards">
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-file-alt"></i> Logs</h3>
        </div>
        <div class="card-body">
            <p>Visualize e gerencie logs de todos os domínios e subdomínios.</p>
            <a href="logs.php" class="btn btn-primary">
                <i class="fas fa-eye"></i> Visualizar Logs
            </a>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-users"></i> Clientes</h3>
        </div>
        <div class="card-body">
            <p>Gerencie informações de clientes e contas.</p>
            <button class="btn btn-secondary" disabled>
                <i class="fas fa-clock"></i> Em Desenvolvimento
            </button>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-cogs"></i> Aplicações</h3>
        </div>
        <div class="card-body">
            <p>Controle e monitore todas as aplicações da plataforma.</p>
            <button class="btn btn-secondary" disabled>
                <i class="fas fa-clock"></i> Em Desenvolvimento
            </button>
        </div>
    </div>
    
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-line"></i> Relatórios</h3>
        </div>
        <div class="card-body">
            <p>Analise métricas e gere relatórios detalhados.</p>
            <button class="btn btn-secondary" disabled>
                <i class="fas fa-clock"></i> Em Desenvolvimento
            </button>
        </div>
    </div>
</div>

<div class="recent-activity">
    <h3>Atividade Recente</h3>
    <div class="activity-list">
        <div class="activity-item">
            <i class="fas fa-sign-in-alt"></i>
            <span>Login realizado às <?= date('H:i') ?></span>
        </div>
        <div class="activity-item">
            <i class="fas fa-file-alt"></i>
            <span>Sistema de logs atualizado</span>
        </div>
    </div>
</div>

<?php
$content = ob_get_clean();

// Incluir o layout principal
include __DIR__ . '/../views/layouts/main.php';
?>
