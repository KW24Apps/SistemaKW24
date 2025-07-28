<?php
// AJAX endpoint para carregar conteúdo dos logs
session_start();
require_once __DIR__ . '/../../includes/helpers.php';
requireAuthentication();

$sub = isset($_GET['sub']) ? $_GET['sub'] : 'filtro';

// Valida subpáginas
$validSubs = ['filtro', 'download'];
if (!in_array($sub, $validSubs)) {
    $sub = 'filtro';
}

// Retorna apenas o conteúdo (sem layout)
?>
<?php if ($sub === 'filtro'): ?>
    <!-- CONTEÚDO FILTRO -->
    <div class="logs-filtro-container">
        <h1>Filtro de Logs</h1>
        <div class="filtro-form">
            <div class="form-row">
                <div class="form-group">
                    <label for="log-date-start">Data Início:</label>
                    <input type="date" id="log-date-start" class="form-control">
                </div>
                <div class="form-group">
                    <label for="log-date-end">Data Fim:</label>
                    <input type="date" id="log-date-end" class="form-control">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="log-level">Nível:</label>
                    <select id="log-level" class="form-control">
                        <option value="">Todos</option>
                        <option value="error">Error</option>
                        <option value="warning">Warning</option>
                        <option value="info">Info</option>
                        <option value="debug">Debug</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="log-search">Buscar:</label>
                    <input type="text" id="log-search" class="form-control" placeholder="Texto para buscar...">
                </div>
            </div>
            <div class="form-actions">
                <button id="btn-filtrar-logs" class="btn btn-primary">Filtrar Logs</button>
                <button id="btn-limpar-filtro" class="btn btn-secondary">Limpar</button>
            </div>
        </div>
        
        <div id="logs-results" class="logs-results">
            <!-- Resultados dos logs aparecerão aqui -->
            <p class="no-results">Use os filtros acima para buscar logs.</p>
        </div>
    </div>

    <script>
    // JavaScript específico para filtro
    document.getElementById('btn-filtrar-logs').addEventListener('click', function() {
        // Implementar lógica de filtro
        console.log('Filtrando logs...');
    });
    
    document.getElementById('btn-limpar-filtro').addEventListener('click', function() {
        document.getElementById('log-date-start').value = '';
        document.getElementById('log-date-end').value = '';
        document.getElementById('log-level').value = '';
        document.getElementById('log-search').value = '';
        document.getElementById('logs-results').innerHTML = '<p class="no-results">Use os filtros acima para buscar logs.</p>';
    });
    </script>

<?php elseif ($sub === 'download'): ?>
    <!-- CONTEÚDO DOWNLOAD -->
    <div class="logs-download-container">
        <h1>Download de Logs</h1>
        <div class="download-options">
            <div class="download-card">
                <h3>Logs Completos</h3>
                <p>Baixar todos os logs do sistema</p>
                <div class="download-actions">
                    <button class="btn btn-download" data-type="all">
                        <i class="fas fa-download"></i> Download Completo
                    </button>
                </div>
            </div>
            
            <div class="download-card">
                <h3>Logs por Data</h3>
                <p>Baixar logs de um período específico</p>
                <div class="date-range">
                    <input type="date" id="download-date-start" class="form-control">
                    <span>até</span>
                    <input type="date" id="download-date-end" class="form-control">
                </div>
                <div class="download-actions">
                    <button class="btn btn-download" data-type="date-range">
                        <i class="fas fa-download"></i> Download por Data
                    </button>
                </div>
            </div>
            
            <div class="download-card">
                <h3>Logs por Nível</h3>
                <p>Baixar apenas logs de erro ou específicos</p>
                <select id="download-level" class="form-control">
                    <option value="error">Apenas Erros</option>
                    <option value="warning">Warnings</option>
                    <option value="info">Informações</option>
                    <option value="debug">Debug</option>
                </select>
                <div class="download-actions">
                    <button class="btn btn-download" data-type="level">
                        <i class="fas fa-download"></i> Download por Nível
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script>
    // JavaScript específico para download
    document.querySelectorAll('.btn-download').forEach(btn => {
        btn.addEventListener('click', function() {
            const type = this.dataset.type;
            console.log('Download tipo:', type);
            // Implementar lógica de download
        });
    });
    </script>

<?php endif; ?>
