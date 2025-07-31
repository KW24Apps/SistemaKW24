<?php
/**
 * RELATÓRIOS - Página de relatórios do sistema
 * Este arquivo contém apenas o conteúdo específico de relatórios
 * O layout (sidebar, topbar, head) está no index.php
 */

// Verificação de segurança - este arquivo só pode ser incluído
if (!defined('SYSTEM_ACCESS') && !isset($user_data)) {
    header('Location: /Apps/public/login.php');
    exit;
}

// Verifica ação dos submenus
$report = $_GET['report'] ?? 'vendas';
?>

<h1>Relatórios - KW24 Sistema</h1>

<div class="welcome-section">
    <p><strong>Usuário:</strong> <?php echo htmlspecialchars($user_data['nome']); ?></p>
    <p><strong>Seção:</strong> Relatórios - <?php echo ucfirst($report); ?></p>
</div>

<div class="page-content">
    <h2>Geração de Relatórios</h2>
    
    <div class="report-filters">
        <h3>Filtros</h3>
        <div class="filter-group">
            <label for="date-start">Data Início:</label>
            <input type="date" id="date-start" value="<?php echo date('Y-m-01'); ?>">
            
            <label for="date-end">Data Fim:</label>
            <input type="date" id="date-end" value="<?php echo date('Y-m-d'); ?>">
        </div>
    </div>
    
    <div class="report-content">
        <?php if ($report === 'vendas'): ?>
            <h3>📈 Relatório de Vendas</h3>
            <p>Análise de vendas por período selecionado.</p>
            
            <div class="report-preview">
                <table class="report-table">
                    <thead>
                        <tr>
                            <th>Período</th>
                            <th>Vendas</th>
                            <th>Valor</th>
                            <th>Meta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><?php echo date('M/Y'); ?></td>
                            <td>45</td>
                            <td>R$ 125.000,00</td>
                            <td>80%</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
        <?php elseif ($report === 'clientes'): ?>
            <h3>👥 Relatório de Clientes</h3>
            <p>Análise do cadastro de clientes por período.</p>
            
            <div class="report-preview">
                <p>📊 Novos clientes cadastrados: <strong>23</strong></p>
                <p>📈 Crescimento: <strong>+15%</strong></p>
            </div>
            
        <?php elseif ($report === 'performance'): ?>
            <h3>⚡ Relatório de Performance</h3>
            <p>Métricas de performance do sistema e equipe.</p>
            
            <div class="report-preview">
                <p>🎯 Taxa de conversão: <strong>12.5%</strong></p>
                <p>⏱️ Tempo médio de resposta: <strong>2.3s</strong></p>
            </div>
            
        <?php endif; ?>
        
        <div class="report-actions">
            <button class="btn-primary" onclick="generateReport()">
                📄 Gerar Relatório
            </button>
            <button class="btn-secondary" onclick="exportReport()">
                📥 Exportar Excel
            </button>
        </div>
    </div>
</div>

<script>
function generateReport() {
    alert('Funcionalidade de geração será implementada!');
}

function exportReport() {
    alert('Funcionalidade de exportação será implementada!');
}
</script>

<style>
.report-filters {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 4px;
    margin-bottom: 1rem;
}

.filter-group {
    display: flex;
    gap: 1rem;
    align-items: center;
}

.filter-group label {
    font-weight: bold;
}

.filter-group input {
    padding: 0.5rem;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.report-table {
    width: 100%;
    border-collapse: collapse;
    margin: 1rem 0;
}

.report-table th,
.report-table td {
    padding: 0.75rem;
    text-align: left;
    border-bottom: 1px solid #ddd;
}

.report-table th {
    background: #f8f9fa;
    font-weight: bold;
}

.report-actions {
    margin-top: 2rem;
    display: flex;
    gap: 1rem;
}

.btn-primary, .btn-secondary {
    padding: 0.75rem 1.5rem;
    border: none;
    border-radius: 4px;
    cursor: pointer;
    font-weight: bold;
}

.btn-primary {
    background: #007bff;
    color: white;
}

.btn-secondary {
    background: #6c757d;
    color: white;
}

.btn-primary:hover,
.btn-secondary:hover {
    opacity: 0.9;
}
</style>
