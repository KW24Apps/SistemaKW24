<?php
/**
 * CADASTRO - Página de cadastros do sistema
 * Este arquivo contém apenas o conteúdo específico de cadastros
 * O layout (sidebar, topbar, head) está no index.php
 */

// Verificação de segurança - este arquivo só pode ser incluído
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /Apps/public/login.php');
    exit;
}

// Verifica se há ação específica nos submenus
$action = $_GET['action'] ?? 'main';
?>

<h1>Cadastros - KW24 Sistema</h1>

<div class="welcome-section">
    <p><strong>Usuário:</strong> <?php echo htmlspecialchars($user_data['nome']); ?></p>
    <p><strong>Seção:</strong> Cadastros
    <?php if ($action !== 'main'): ?>
        - <?php echo ucfirst($action); ?>
    <?php endif; ?>
    </p>
</div>

<div class="page-content">
    <?php if ($action === 'cliente'): ?>
        <h2>Novo Cliente</h2>
        <p>Formulário para cadastro de novos clientes.</p>
        <div class="form-placeholder">
            <p>📝 Formulário de cliente será implementado aqui.</p>
        </div>
        
    <?php elseif ($action === 'contato'): ?>
        <h2>Novo Contato</h2>
        <p>Formulário para cadastro de novos contatos.</p>
        <div class="form-placeholder">
            <p>📞 Formulário de contato será implementado aqui.</p>
        </div>
        
    <?php elseif ($action === 'import'): ?>
        <h2>Importar Dados</h2>
        <p>Ferramenta para importação de dados em lote.</p>
        <div class="form-placeholder">
            <p>📥 Ferramenta de importação será implementada aqui.</p>
        </div>
        
    <?php else: ?>
        <h2>Módulo de Cadastros</h2>
        <p>Utilize os submenus no topbar para acessar as funcionalidades específicas.</p>
        
        <div class="module-overview">
            <h3>Funcionalidades Disponíveis</h3>
            <ul>
                <li><strong>Novo Cliente:</strong> Cadastrar informações de clientes</li>
                <li><strong>Novo Contato:</strong> Cadastrar contatos associados</li>
                <li><strong>Importar Dados:</strong> Importação em lote via arquivo</li>
            </ul>
        </div>
    <?php endif; ?>
</div>
