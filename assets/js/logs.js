/**
 * Log Viewer - JavaScript Específico
 * Funcionalidades: Auto-refresh, Filtros, Export, Print
 */

class LogViewer {
    constructor() {
        this.autoRefreshInterval = null;
        this.isAutoRefreshActive = false;
        this.refreshIntervalTime = 10000; // 10 segundos
        
        this.init();
    }

    init() {
        this.setupEventListeners();
        this.setupSidebarCollapse();
        this.injectCustomStyles();
        this.setupTableFilters();
        this.setupAutoRefresh();
        this.setupKeyboardShortcuts();
        
        console.log('📋 Log Viewer inicializado');
    }

    injectCustomStyles() {
        // Adiciona estilos customizados para o Log Viewer
        const customStyles = `
            .log-viewer-container {
                width: 100%;
            }
            
            /* Barra superior com seletores de modo */
            .mode-selector {
                display: flex;
                gap: 10px;
            }
            
            .mode-button {
                padding: 8px 15px;
                border-radius: 4px;
                text-decoration: none;
                color: #086B8D;
                background-color: white;
                border: 1px solid #086B8D;
                font-weight: 500;
                transition: all 0.2s;
                display: inline-flex;
                align-items: center;
                gap: 5px;
            }
            
            .mode-button.active {
                background-color: #086B8D;
                color: white;
            }
            
            .mode-button:hover {
                background-color: #0DC2FF;
                color: white;
            }
            
            /* Cards */
            .card, .filter-card, .logs-card {
                background: white;
                border-radius: 8px;
                overflow: hidden;
                box-shadow: 0 2px 6px rgba(0,0,0,0.1);
                margin-bottom: 20px;
            }
            
            .card-header {
                padding: 15px 20px;
                border-bottom: 1px solid #e0e0e0;
                background-color: #f8f9fa;
            }
            
            .card-header h2 {
                margin: 0;
                font-size: 18px;
                color: #033140;
            }
            
            .card-body {
                padding: 20px;
            }
            
            /* Filtros */
            .filter-card {
                padding: 20px;
            }
            
            .filters {
                display: flex;
                gap: 30px;
            }
            
            .filter-group {
                flex: 1;
            }
            
            .filter-group label {
                font-weight: 600;
                font-size: 14px;
                margin-bottom: 10px;
                color: #033140;
            }
            
            .select-wrapper {
                position: relative;
            }
            
            .form-select {
                width: 100%;
                padding: 12px 15px;
                border: 1px solid #e0e0e0;
                border-radius: 6px;
                appearance: none;
                background-color: white;
                font-size: 15px;
                color: #033140;
            }
            
            .select-arrow {
                position: absolute;
                right: 15px;
                top: 50%;
                transform: translateY(-50%);
                pointer-events: none;
                color: #086B8D;
            }
            
            /* Barra de estatísticas */
            .stats-bar {
                background-color: #f8f9fa;
                padding: 12px 20px;
                border-radius: 6px;
                margin-bottom: 20px;
                color: #033140;
                font-size: 15px;
            }
            
            .stats-bar i {
                margin-right: 5px;
            }
            
            /* Tabela de logs */
            .logs-card {
                padding: 0;
                overflow: hidden;
            }
            
            .logs-table th {
                background-color: #033140;
                color: white;
                text-align: left;
                padding: 15px;
                font-size: 13px;
                font-weight: 600;
            }
            
            .logs-table td {
                padding: 12px 15px;
                border-bottom: 1px solid #e0e0e0;
                font-size: 14px;
            }
            
            .logs-table tr:hover {
                background-color: #f5f9fc;
            }
            
            .col-origin, .col-datetime, .col-trace, .col-message {
                padding: 12px 15px;
            }
            
            .col-origin {
                width: 15%;
            }
            
            .col-datetime {
                width: 15%;
            }
            
            .col-trace {
                width: 10%;
            }
            
            .col-message {
                width: 60%;
            }
            
            /* Estado vazio */
            .empty {
                padding: 40px;
                text-align: center;
                color: #777;
            }
            
            .empty i {
                color: #ccc;
                margin-bottom: 15px;
            }
            
            .empty h3 {
                margin: 10px 0;
                font-weight: 500;
            }
            
            .empty p {
                margin: 5px 0;
            }
        `;

        // Criar e adicionar o elemento style
        const styleElement = document.createElement('style');
        styleElement.textContent = customStyles;
        document.head.appendChild(styleElement);
    }

    setupEventListeners() {
        // Botão de colapsar sidebar
        const collapseBtn = document.getElementById('collapseSidebar');
        if (collapseBtn) {
            collapseBtn.addEventListener('click', () => {
                this.toggleSidebar();
            });
        }

        // Auto refresh button
        const autoRefreshBtn = document.getElementById('autoRefreshBtn');
        if (autoRefreshBtn) {
            autoRefreshBtn.addEventListener('click', () => {
                this.toggleAutoRefresh();
            });
        }

        // Form de filtros
        const filterForm = document.querySelector('.filter-form');
        if (filterForm) {
            filterForm.addEventListener('submit', (e) => {
                this.showLoading();
            });
        }

        // Dropdown changes para auto-submit
        const domainSelect = document.getElementById('domain');
        const dateInput = document.getElementById('date');
        const traceSelect = document.getElementById('trace');

        if (domainSelect) {
            domainSelect.addEventListener('change', () => {
                this.autoSubmitFilter();
            });
        }

        if (dateInput) {
            dateInput.addEventListener('change', () => {
                this.autoSubmitFilter();
            });
        }

        if (traceSelect) {
            traceSelect.addEventListener('change', () => {
                this.autoSubmitFilter();
            });
        }
    }

    setupSidebarCollapse() {
        const sidebar = document.querySelector('.log-sidebar');
        const collapseBtn = document.getElementById('collapseSidebar');
        
        if (!sidebar || !collapseBtn) return;

        // Recuperar estado do localStorage
        const isCollapsed = localStorage.getItem('log_sidebar_collapsed') === 'true';
        if (isCollapsed) {
            sidebar.classList.add('collapsed');
            collapseBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
        }
    }

    toggleSidebar() {
        const sidebar = document.querySelector('.log-sidebar');
        const collapseBtn = document.getElementById('collapseSidebar');
        
        if (!sidebar || !collapseBtn) return;

        const isCollapsed = sidebar.classList.contains('collapsed');
        
        if (isCollapsed) {
            sidebar.classList.remove('collapsed');
            collapseBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
            localStorage.setItem('log_sidebar_collapsed', 'false');
        } else {
            sidebar.classList.add('collapsed');
            collapseBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
            localStorage.setItem('log_sidebar_collapsed', 'true');
        }
    }

    setupTableFilters() {
        // Filtro de busca em tempo real (se implementado)
        const searchInput = document.getElementById('search');
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.filterTableRows(e.target.value);
            });
        }
    }

    filterTableRows(searchTerm) {
        const table = document.getElementById('logTable');
        if (!table) return;

        const rows = table.querySelectorAll('tbody tr');
        const term = searchTerm.toLowerCase();

        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(term) ? '' : 'none';
        });
    }

    setupAutoRefresh() {
        // Recuperar estado do auto refresh
        const savedState = localStorage.getItem('log_auto_refresh');
        if (savedState === 'true') {
            this.startAutoRefresh();
        }
    }

    toggleAutoRefresh() {
        if (this.isAutoRefreshActive) {
            this.stopAutoRefresh();
        } else {
            this.startAutoRefresh();
        }
    }

    startAutoRefresh() {
        this.isAutoRefreshActive = true;
        const btn = document.getElementById('autoRefreshBtn');
        
        if (btn) {
            btn.innerHTML = '<i class="fas fa-pause"></i> Auto Refresh ON';
            btn.classList.add('auto-refresh-active');
        }

        this.autoRefreshInterval = setInterval(() => {
            this.refreshLogs();
        }, this.refreshIntervalTime);

        localStorage.setItem('log_auto_refresh', 'true');
        this.showNotification('Auto-refresh ativado (10s)', 'success');
    }

    stopAutoRefresh() {
        this.isAutoRefreshActive = false;
        const btn = document.getElementById('autoRefreshBtn');
        
        if (btn) {
            btn.innerHTML = '<i class="fas fa-play"></i> Auto Refresh OFF';
            btn.classList.remove('auto-refresh-active');
        }

        if (this.autoRefreshInterval) {
            clearInterval(this.autoRefreshInterval);
            this.autoRefreshInterval = null;
        }

        localStorage.setItem('log_auto_refresh', 'false');
        this.showNotification('Auto-refresh desativado', 'info');
    }

    refreshLogs() {
        const currentUrl = window.location.href;
        
        // Adicionar indicador de loading no botão
        const btn = document.getElementById('autoRefreshBtn');
        if (btn) {
            const originalText = btn.innerHTML;
            btn.innerHTML = '<span class="loading-spinner-small"></span> Atualizando...';
            
            // Fazer requisição AJAX
            fetch(currentUrl)
                .then(response => response.text())
                .then(html => {
                    // Extrair apenas a tabela do HTML
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newTable = doc.querySelector('.log-table-container');
                    
                    if (newTable) {
                        const currentTable = document.querySelector('.log-table-container');
                        if (currentTable) {
                            currentTable.innerHTML = newTable.innerHTML;
                            this.showNotification('Logs atualizados', 'success');
                        }
                    }
                })
                .catch(error => {
                    console.error('Erro ao atualizar logs:', error);
                    this.showNotification('Erro ao atualizar logs', 'error');
                })
                .finally(() => {
                    if (btn) {
                        btn.innerHTML = originalText;
                    }
                });
        }
    }

    setupKeyboardShortcuts() {
        document.addEventListener('keydown', (e) => {
            // Ctrl/Cmd + R = Refresh
            if ((e.ctrlKey || e.metaKey) && e.key === 'r') {
                e.preventDefault();
                this.refreshLogs();
            }

            // Ctrl/Cmd + F = Focus no filtro de domínio
            if ((e.ctrlKey || e.metaKey) && e.key === 'f') {
                e.preventDefault();
                const domainSelect = document.getElementById('domain');
                if (domainSelect) {
                    domainSelect.focus();
                }
            }

            // Esc = Colapsar sidebar
            if (e.key === 'Escape') {
                this.toggleSidebar();
            }
        });
    }

    autoSubmitFilter() {
        // Aguardar um pouco antes de submeter para evitar múltiplas requisições
        clearTimeout(this.submitTimeout);
        this.submitTimeout = setTimeout(() => {
            this.showLoading();
            document.querySelector('.filter-form').submit();
        }, 500);
    }

    showLoading() {
        if (window.KW24 && window.KW24.LoadingManager) {
            window.KW24.LoadingManager.show('Carregando logs...');
        }
    }

    showNotification(message, type = 'info') {
        if (window.KW24 && window.KW24.showNotification) {
            window.KW24.showNotification(message, type);
        }
    }

    // Exportar logs para CSV
    exportToCSV() {
        const table = document.getElementById('logTable');
        if (!table) return;

        const rows = Array.from(table.querySelectorAll('tr'));
        const csvContent = rows.map(row => {
            const cells = Array.from(row.querySelectorAll('th, td'));
            return cells.map(cell => {
                let content = cell.textContent.trim();
                // Escapar aspas duplas
                content = content.replace(/"/g, '""');
                // Envolver em aspas se contém vírgula ou quebra de linha
                if (content.includes(',') || content.includes('\n') || content.includes('"')) {
                    content = `"${content}"`;
                }
                return content;
            }).join(',');
        }).join('\n');

        // Criar e baixar arquivo
        const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
        const link = document.createElement('a');
        const url = URL.createObjectURL(blob);
        const currentDate = new Date().toISOString().split('T')[0];
        
        link.setAttribute('href', url);
        link.setAttribute('download', `logs_${currentDate}.csv`);
        link.style.visibility = 'hidden';
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);

        this.showNotification('CSV exportado com sucesso!', 'success');
    }

    // Imprimir logs
    printLogs() {
        const printWindow = window.open('', '_blank');
        const table = document.getElementById('logTable');
        
        if (!table) return;

        const currentDate = new Date().toLocaleDateString('pt-BR');
        const domain = document.getElementById('domain').value || 'Todos os domínios';
        const date = document.getElementById('date').value || 'Todas as datas';

        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Logs - Sistema KW24</title>
                <style>
                    body { font-family: Arial, sans-serif; margin: 20px; }
                    .print-header { margin-bottom: 20px; border-bottom: 2px solid #333; padding-bottom: 10px; }
                    .print-header h1 { margin: 0; color: #333; }
                    .print-info { margin: 10px 0; color: #666; }
                    table { width: 100%; border-collapse: collapse; margin-top: 20px; }
                    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; font-size: 12px; }
                    th { background-color: #f2f2f2; font-weight: bold; }
                    tr:nth-child(even) { background-color: #f9f9f9; }
                    .origin-badge { padding: 2px 6px; border-radius: 3px; font-size: 10px; }
                    @media print { body { margin: 0; } }
                </style>
            </head>
            <body>
                <div class="print-header">
                    <h1>Sistema de Logs - KW24</h1>
                    <div class="print-info">
                        <strong>Domínio:</strong> ${domain}<br>
                        <strong>Data:</strong> ${date}<br>
                        <strong>Relatório gerado em:</strong> ${currentDate}
                    </div>
                </div>
                ${table.outerHTML}
            </body>
            </html>
        `);

        printWindow.document.close();
        printWindow.focus();
        printWindow.print();
        printWindow.close();

        this.showNotification('Preparando impressão...', 'info');
    }
}

// Script para atualizar a página automaticamente quando mudam os filtros
document.getElementById('date')?.addEventListener('change', function() {
    const trace = document.getElementById('trace').value;
    const sidebarState = localStorage.getItem('sidebarState') || '';
    window.location.href = `?mode=filter&date=${this.value}${trace ? '&trace=' + trace : ''}${sidebarState ? '&sidebar=' + sidebarState : ''}`;
});

document.getElementById('trace')?.addEventListener('change', function() {
    const date = document.getElementById('date').value;
    const sidebarState = localStorage.getItem('sidebarState') || '';
    window.location.href = `?mode=filter&trace=${this.value}${date ? '&date=' + date : ''}${sidebarState ? '&sidebar=' + sidebarState : ''}`;
});

// Manipular cliques nos links da barra lateral
document.querySelectorAll('.sidebar-link').forEach(link => {
    link.addEventListener('click', function(e) {
        e.preventDefault();
        const mode = this.getAttribute('data-mode');
        // Sempre expandir o menu lateral ao clicar em Logs
        localStorage.setItem('sidebarState', 'expanded');
        let params = `mode=${mode}&sidebar=expanded`;
        if (mode === 'filter') {
            const date = document.getElementById('date')?.value || '';
            const trace = document.getElementById('trace')?.value || '';
            if (date) params += `&date=${date}`;
            if (trace) params += `&trace=${trace}`;
        }
        window.location.href = `?${params}`;
    });
});

// Função para controlar a barra lateral
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.querySelector('.sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    const mainContent = document.querySelector('.main-content');
    
    // Função para aplicar o estado correto da barra lateral
    function applySidebarState(state) {
        if (state === 'collapsed') {
            sidebar.classList.add('collapsed');
            mainContent.classList.add('expanded');
            document.body.classList.add('sidebar-collapsed');
        } else {
            sidebar.classList.remove('collapsed');
            mainContent.classList.remove('expanded');
            document.body.classList.remove('sidebar-collapsed');
        }
    }
    
    // Verificar se há uma preferência na URL ou no localStorage
    const urlParams = new URLSearchParams(window.location.search);
    const urlSidebarState = urlParams.get('sidebar');
    const localSidebarState = localStorage.getItem('sidebarState');
    
    // Priorizar o estado da URL, depois usar o localStorage
    if (urlSidebarState) {
        applySidebarState(urlSidebarState);
        localStorage.setItem('sidebarState', urlSidebarState);
    } else if (localSidebarState) {
        applySidebarState(localSidebarState);
    }
    
    // Adicionar evento de clique ao botão de toggle
    toggleBtn.addEventListener('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        sidebar.classList.toggle('collapsed');
        mainContent.classList.toggle('expanded');
        document.body.classList.toggle('sidebar-collapsed');
        
        // Salvar o estado atual no localStorage
        if (sidebar.classList.contains('collapsed')) {
            localStorage.setItem('sidebarState', 'collapsed');
        } else {
            localStorage.setItem('sidebarState', 'expanded');
        }
    });
});

// Funções globais chamadas pelos botões
function toggleDetails(button) {
    const details = button.nextElementSibling;
    const icon = button.querySelector('i');
    
    if (details.style.display === 'none') {
        details.style.display = 'block';
        icon.className = 'fas fa-chevron-up';
        button.innerHTML = '<i class="fas fa-chevron-up"></i> Ocultar';
    } else {
        details.style.display = 'none';
        icon.className = 'fas fa-chevron-down';
        button.innerHTML = '<i class="fas fa-chevron-down"></i> Detalhes';
    }
}

function filterByTrace(traceId) {
    const traceSelect = document.getElementById('trace');
    if (traceSelect) {
        traceSelect.value = traceId;
        document.querySelector('.filter-form').submit();
    }
}

function clearLog(domain, date) {
    if (confirm(`Tem certeza que deseja limpar o log de ${domain} da data ${date}?\n\nEsta ação não pode ser desfeita.`)) {
        window.location.href = `logs.php?action=clear&domain=${encodeURIComponent(domain)}&date=${encodeURIComponent(date)}`;
    }
}

function refreshLogs() {
    if (window.logViewer) {
        window.logViewer.refreshLogs();
    } else {
        location.reload();
    }
}

function toggleAutoRefresh() {
    if (window.logViewer) {
        window.logViewer.toggleAutoRefresh();
    }
}

function exportToCSV() {
    if (window.logViewer) {
        window.logViewer.exportToCSV();
    }
}

function printLogs() {
    if (window.logViewer) {
        window.logViewer.printLogs();
    }
}

// Inicializar quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.logViewer = new LogViewer();
    console.log('📋 Log Viewer carregado e pronto!');
});
