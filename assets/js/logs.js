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
        this.setupTableFilters();
        this.setupAutoRefresh();
        this.setupKeyboardShortcuts();
        
        console.log('📋 Log Viewer inicializado');
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
