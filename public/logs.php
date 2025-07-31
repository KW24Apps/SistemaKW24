<?php
/**
 * LOGS - Página de logs do sistema
 * Este arquivo contém apenas o conteúdo específico de logs
 * O layout (sidebar, topbar, head) está no index.php
 */

// Verificação de segurança - este arquivo só pode ser incluído
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /Apps/public/login.php');
    exit;
}

// Verifica ação dos submenus
$type = $_GET['type'] ?? 'sistema';
?>

<h1>Logs - KW24 Sistema</h1>

<div class="welcome-section">
    <p><strong>Usuário:</strong> <?php echo htmlspecialchars($user_data['nome']); ?></p>
    <p><strong>Seção:</strong> Logs - <?php echo ucfirst($type); ?></p>
</div>

<div class="page-content">
    <h2>Visualização de Logs</h2>
    
    <div class="log-controls">
        <label for="log-filter">Filtrar por tipo:</label>
        <select id="log-filter">
            <option value="sistema" <?php echo $type === 'sistema' ? 'selected' : ''; ?>>Sistema</option>
            <option value="acesso" <?php echo $type === 'acesso' ? 'selected' : ''; ?>>Acesso</option>
            <option value="erros" <?php echo $type === 'erros' ? 'selected' : ''; ?>>Erros</option>
        </select>
    </div>
    
    <div class="log-content">
        <?php if ($type === 'sistema'): ?>
            <h3>📊 Logs do Sistema</h3>
            <p>Logs gerais de funcionamento do sistema.</p>
            
        <?php elseif ($type === 'acesso'): ?>
            <h3>🔐 Logs de Acesso</h3>
            <p>Logs de login e autenticação de usuários.</p>
            
        <?php elseif ($type === 'erros'): ?>
            <h3>❌ Logs de Erros</h3>
            <p>Logs de erros e exceções do sistema.</p>
            
        <?php endif; ?>
        
        <div class="log-viewer">
            <pre id="log-display">
Aguardando carregamento dos logs...
Os logs reais seriam carregados via AJAX aqui.
            </pre>
        </div>
    </div>
</div>

<script>
// Simular mudança de filtro
document.getElementById('log-filter').addEventListener('change', function() {
    const type = this.value;
    window.location.href = `?page=logs&type=${type}`;
});
</script>
