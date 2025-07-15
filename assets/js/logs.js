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
        this.setupOverlay();
        this.setupPageTransitions();
        
        // Inicializar componentes se os elementos existirem
        if (document.querySelector('.logs-table')) {
            this.setupTableFilters();
        }
        
        if (document.getElementById('autoRefreshBtn')) {
            this.setupAutoRefresh();
        }
        
        this.setupKeyboardShortcuts();
        
        // Esconder o overlay depois que a página carregar completamente
        window.addEventListener('load', () => {
            setTimeout(() => {
                this.hideLoading();
            }, 300);
        });
        
        console.log('📋 Log Viewer inicializado');
    }
    
    setupOverlay() {
        // Criar o overlay de loading
        const overlay = document.createElement('div');
        overlay.id = 'loadingOverlay';
        overlay.className = 'loading-overlay';
        
        const spinner = document.createElement('div');
        spinner.className = 'loading-spinner';
        
        overlay.appendChild(spinner);
        document.body.appendChild(overlay);
    }

    injectCustomStyles() {
        // Adiciona estilos customizados para o Log Viewer
        const customStyles = `
            body {
                background: #f4f7fa !important;
                font-family: 'Inter', sans-serif;
            }
            
            /* Corrigir layout do sidebar */
            .sidebar-link.active {
                background-color: rgba(255, 255, 255, 0.15) !important;
                border-left: 4px solid #26FF93 !important;
            }
            
            .main-content {
                padding: 0 !important;
                background: #f4f7fa !important;
            }
            
            /* Container principal */
            .log-viewer-container {
                width: 100%;
                padding: 20px 30px;
                transition: opacity 0.3s ease-in-out;
            }
            
            /* Overlay de carregamento */
            .loading-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(255, 255, 255, 0.8);
                display: flex;
                justify-content: center;
                align-items: center;
                z-index: 9999;
                opacity: 0;
                visibility: hidden;
                transition: opacity 0.3s ease, visibility 0.3s ease;
            }
            
            .loading-overlay.active {
                opacity: 1;
                visibility: visible;
            }
            
            .loading-spinner {
                width: 50px;
                height: 50px;
                border: 5px solid #f3f3f3;
                border-top: 5px solid #086B8D;
                border-radius: 50%;
                animation: spin 1s linear infinite;
            }
            
            @keyframes spin {
                0% { transform: rotate(0deg); }
                100% { transform: rotate(360deg); }
            }
            
            /* Top Bar estilizada */
            .top-bar {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 25px;
            }
            
            .page-title {
                font-size: 24px;
                font-weight: 600;
                color: #033140;
                margin: 0;
            }
            
            /* Barra superior com seletores de modo */
            .mode-selector {
                display: flex;
                gap: 10px;
                background-color: white;
                padding: 5px;
                border-radius: 8px;
                box-shadow: 0 2px 6px rgba(0,0,0,0.08);
            }
            
            .mode-button {
                padding: 10px 20px;
                border-radius: 6px;
                text-decoration: none;
                color: #086B8D;
                background-color: transparent;
                font-weight: 500;
                transition: all 0.2s;
                display: inline-flex;
                align-items: center;
                gap: 8px;
                border: none;
            }
            
            .mode-button.active {
                background-color: #086B8D;
                color: white;
                box-shadow: 0 2px 4px rgba(8, 107, 141, 0.3);
            }
            
            .mode-button:hover:not(.active) {
                background-color: #f0f7fa;
            }
            
            /* Cards com design moderno e transições */
            .card, .filter-card, .logs-card {
                background: white;
                border-radius: 12px;
                overflow: hidden;
                box-shadow: 0 3px 10px rgba(0,0,0,0.08);
                margin-bottom: 25px;
                border: none;
                transition: opacity 0.3s ease, transform 0.3s ease;
            }
            
            /* Transição de conteúdo */
            .content-area {
                transition: opacity 0.3s ease-in-out;
            }
            
            /* Filtros com mais espaço */
            .filter-card {
                padding: 25px;
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
                display: block;
            }
            
            .select-wrapper {
                position: relative;
                width: 100%;
            }
            
            .form-select {
                width: 100%;
                padding: 14px 18px;
                border: 1px solid #e0e0e0;
                border-radius: 8px;
                appearance: none;
                background-color: white;
                font-size: 15px;
                color: #033140;
                box-shadow: 0 1px 3px rgba(0,0,0,0.05);
                transition: all 0.2s;
            }
            
            .form-select:focus {
                border-color: #0DC2FF;
                box-shadow: 0 0 0 3px rgba(13, 194, 255, 0.2);
                outline: none;
            }
            
            .select-arrow {
                position: absolute;
                right: 18px;
                top: 50%;
                transform: translateY(-50%);
                pointer-events: none;
                color: #086B8D;
            }
            
            /* Barra de estatísticas */
            .stats-bar {
                background-color: #f0f7fa;
                padding: 15px 20px;
                border-radius: 10px;
                margin-bottom: 20px;
                color: #033140;
                font-size: 15px;
                border-left: 4px solid #0DC2FF;
            }
            
            .stats-bar i {
                margin-right: 8px;
                color: #086B8D;
            }
            
            .stats-bar strong {
                color: #086B8D;
                font-weight: 600;
            }
            
            /* Tabela de logs com design moderno */
            .logs-card {
                padding: 0;
                overflow: hidden;
            }
            
            .logs-table {
                width: 100%;
                border-collapse: separate;
                border-spacing: 0;
            }
            
            .logs-table th {
                background-color: #033140;
                color: white;
                text-align: left;
                padding: 16px 20px;
                font-size: 14px;
                font-weight: 600;
                letter-spacing: 0.5px;
                position: sticky;
                top: 0;
            }
            
            .logs-table th:first-child {
                border-top-left-radius: 8px;
            }
            
            .logs-table th:last-child {
                border-top-right-radius: 8px;
            }
            
            .logs-table td {
                padding: 14px 20px;
                border-bottom: 1px solid #eaedf0;
                font-size: 14px;
                vertical-align: top;
            }
            
            .logs-table tr:hover {
                background-color: #f0f7fa;
            }
            
            .logs-table tr:last-child td {
                border-bottom: none;
            }
            
            .col-origin, .col-datetime, .col-trace, .col-message {
                padding: 14px 20px;
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
            
            /* Estado vazio com design moderno */
            .empty {
                padding: 50px 20px;
                text-align: center;
                color: #777;
            }
            
            .empty i {
                color: #ccc;
                font-size: 3.5rem;
                margin-bottom: 20px;
                opacity: 0.7;
                margin-bottom: 15px;
            }
            
            .empty h3 {
                margin: 10px 0 15px;
                font-weight: 500;
                color: #033140;
                font-size: 20px;
            }
            
            .empty p {
                margin: 5px 0;
                color: #5a6a72;
            }
            
            /* Estilo para a lista de arquivos */
            .file-list {
                list-style: none;
                margin: 0;
                padding: 0;
            }
            
            .file-item {
                padding: 16px;
                border-bottom: 1px solid #eaedf0;
                display: flex;
                flex-direction: column;
                gap: 10px;
            }
            
            .file-item:last-child {
                border-bottom: none;
            }
            
            .file-name {
                font-weight: 600;
                font-size: 15px;
                color: #033140;
            }
            
            .file-meta {
                display: flex;
                align-items: center;
                gap: 20px;
                color: #5a6a72;
                font-size: 14px;
            }
            
            .file-size, .file-date {
                display: flex;
                align-items: center;
                gap: 6px;
            }
            
            .file-size:before {
                content: '';
                font-family: 'Font Awesome 6 Free';
                content: '\\f1c0';
                font-weight: 900;
            }
            
            .file-date:before {
                content: '';
                font-family: 'Font Awesome 6 Free';
                content: '\\f073';
                font-weight: 900;
            }
            
            .download-btn {
                margin-left: auto;
                padding: 6px 14px;
                background-color: #086B8D;
                color: white;
                text-decoration: none;
                border-radius: 6px;
                font-size: 13px;
                font-weight: 500;
                display: inline-flex;
                align-items: center;
                gap: 6px;
                transition: all 0.2s;
            }
            
            .download-btn:hover {
                background-color: #0DC2FF;
                transform: translateY(-1px);
            }
            
            /* Corrigir a página como um todo */
            .page-header {
                display: none;
            }
            
            .content-area {
                padding: 0 !important;
            }
            
            .footer {
                padding: 20px 30px !important;
                color: #5a6a72;
            }
        `;

        // Criar e adicionar o elemento style
        const styleElement = document.createElement('style');
        styleElement.textContent = customStyles;
        document.head.appendChild(styleElement);
    }

    setupEventListeners() {
        // Certifica-se de que o menu lateral esteja expandido quando estiver na página de logs
        this.ensureSidebarExpanded();
        
        // Botão de colapsar sidebar
        const collapseBtn = document.getElementById('sidebarToggle');
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

        // Dropdown changes para auto-submit com animação de transição
        const domainSelect = document.getElementById('domain');
        const dateInput = document.getElementById('date');
        const traceSelect = document.getElementById('trace');

        if (domainSelect) {
            domainSelect.addEventListener('change', () => {
                // Mostrar loading e suavizar transição
                this.showLoading();
                setTimeout(() => {
                    this.autoSubmitFilter();
                }, 100);
            });
        }

        if (dateInput) {
            dateInput.addEventListener('change', () => {
                // Mostrar loading e suavizar transição
                this.showLoading();
                setTimeout(() => {
                    this.autoSubmitFilter();
                }, 100);
            });
        }

        if (traceSelect) {
            traceSelect.addEventListener('change', () => {
                // Mostrar loading e suavizar transição
                this.showLoading();
                setTimeout(() => {
                    this.autoSubmitFilter();
                }, 100);
            });
        }
        
        // Adicionar event listener para os botões de modo com transição suave
        const filterButton = document.querySelector('a.mode-button[href*="mode=filter"]');
        const downloadButton = document.querySelector('a.mode-button[href*="mode=download"]');
        
        if (filterButton) {
            filterButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.showLoading();
                document.querySelectorAll('.mode-button').forEach(btn => btn.classList.remove('active'));
                filterButton.classList.add('active');
                
                // Redirecionar após um pequeno delay para permitir a animação
                setTimeout(() => {
                    window.location.href = filterButton.getAttribute('href');
                }, 300);
            });
        }
        
        if (downloadButton) {
            downloadButton.addEventListener('click', (e) => {
                e.preventDefault();
                this.showLoading();
                document.querySelectorAll('.mode-button').forEach(btn => btn.classList.remove('active'));
                downloadButton.classList.add('active');
                
                // Redirecionar após um pequeno delay para permitir a animação
                setTimeout(() => {
                    window.location.href = downloadButton.getAttribute('href');
                }, 300);
            });
        }
    }

    setupSidebarCollapse() {
        const sidebar = document.querySelector('.sidebar');
        const body = document.body;
        const collapseBtn = document.getElementById('sidebarToggle');
        
        if (!sidebar || !collapseBtn) return;
        
        // Verifica se estamos na página de logs
        const activeLogsLink = document.querySelector('.sidebar-link.active[href="logs.php"]');
        
        if (activeLogsLink) {
            // Se estiver na página de logs, garantir que o sidebar esteja expandido
            sidebar.classList.remove('collapsed');
            body.classList.remove('sidebar-collapsed');
            collapseBtn.querySelector('i').className = 'fas fa-angle-left';
            localStorage.setItem('sidebar_collapsed', 'false');
        } else {
            // Para outras páginas, recupera estado do localStorage
            const isCollapsed = localStorage.getItem('sidebar_collapsed') === 'true';
            if (isCollapsed) {
                sidebar.classList.add('collapsed');
                body.classList.add('sidebar-collapsed');
                collapseBtn.querySelector('i').className = 'fas fa-angle-right';
            }
        }
    }

    toggleSidebar() {
        const sidebar = document.querySelector('.sidebar');
        const body = document.body;
        const collapseBtn = document.getElementById('sidebarToggle');
        
        if (!sidebar || !collapseBtn) return;

        const isCollapsed = sidebar.classList.contains('collapsed');
        
        if (isCollapsed) {
            sidebar.classList.remove('collapsed');
            body.classList.remove('sidebar-collapsed');
            collapseBtn.querySelector('i').className = 'fas fa-angle-left';
            localStorage.setItem('sidebar_collapsed', 'false');
        } else {
            sidebar.classList.add('collapsed');
            body.classList.add('sidebar-collapsed');
            collapseBtn.querySelector('i').className = 'fas fa-angle-right';
            localStorage.setItem('sidebar_collapsed', 'true');
        }
    }
    
    ensureSidebarExpanded() {
        const sidebar = document.querySelector('.sidebar');
        const body = document.body;
        const collapseBtn = document.getElementById('sidebarToggle');
        
        if (!sidebar || !collapseBtn) return;
        
        // Verifica se estamos na página de logs
        const activeLogsLink = document.querySelector('.sidebar-link.active[href="logs.php"]');
        
        if (activeLogsLink) {
            // Se estiver na página de logs, garantir que o sidebar esteja expandido
            sidebar.classList.remove('collapsed');
            body.classList.remove('sidebar-collapsed');
            collapseBtn.querySelector('i').className = 'fas fa-angle-left';
            localStorage.setItem('sidebar_collapsed', 'false');
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
            // Aplicar transição suave
            const contentArea = document.querySelector('.content-area');
            if (contentArea) {
                contentArea.style.opacity = '0.4';
            }
            
            // Submeter o formulário após um pequeno delay
            setTimeout(() => {
                const filterForm = document.getElementById('filterForm');
                if (filterForm) {
                    filterForm.submit();
                } else {
                    // Fallback caso o formulário não exista
                    const date = document.getElementById('date')?.value || '';
                    const trace = document.getElementById('trace')?.value || '';
                    
                    const url = new URL(window.location.href);
                    url.searchParams.set('date', date);
                    url.searchParams.set('trace', trace);
                    url.searchParams.set('mode', 'filter');
                    
                    window.location.href = url.toString();
                }
            }, 300);
        }, 300);
    }

    showLoading() {
        // Verificar se o overlay já existe ou criar um novo
        let overlay = document.getElementById('loadingOverlay');
        
        if (!overlay) {
            overlay = document.createElement('div');
            overlay.id = 'loadingOverlay';
            overlay.className = 'loading-overlay';
            
            const spinner = document.createElement('div');
            spinner.className = 'loading-spinner';
            
            overlay.appendChild(spinner);
            document.body.appendChild(overlay);
        }
        
        // Desabilitar transições durante o carregamento
        const container = document.querySelector('.log-viewer-container');
        if (container) {
            container.style.opacity = '0.6';
        }
        
        // Mostrar o overlay
        setTimeout(() => {
            overlay.classList.add('active');
        }, 10);
        
        // Fallback para o loading manager existente
        if (window.KW24 && window.KW24.LoadingManager) {
            window.KW24.LoadingManager.show('Carregando logs...');
        }
    }

    showNotification(message, type = 'info') {
        if (window.KW24 && window.KW24.showNotification) {
            window.KW24.showNotification(message, type);
        }
    }
    
    hideLoading() {
        const overlay = document.getElementById('loadingOverlay');
        
        if (overlay) {
            overlay.classList.remove('active');
            
            // Restaurar a opacidade do container após um pequeno delay
            setTimeout(() => {
                const container = document.querySelector('.log-viewer-container');
                if (container) {
                    container.style.opacity = '1';
                }
            }, 300);
        }
        
        // Fallback para o loading manager existente
        if (window.KW24 && window.KW24.LoadingManager) {
            window.KW24.LoadingManager.hide();
        }
    }
    
    setupPageTransitions() {
        // Adicionar listener para eventos popstate (navegação pelo histórico)
        window.addEventListener('popstate', () => {
            this.showLoading();
        });
        
        // Aplicar fadeIn ao conteúdo quando a página carrega
        this.applyFadeInEffect();
    }
    
    applyFadeInEffect() {
        // Inicialmente esconde o conteúdo
        const container = document.querySelector('.log-viewer-container');
        if (container) {
            container.style.opacity = '0';
            
            // Aplicar fadeIn
            setTimeout(() => {
                container.style.opacity = '1';
            }, 100);
        }
        
        // Aplicar efeito de entrada nos cards
        const cards = document.querySelectorAll('.card, .filter-card, .logs-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(10px)';
            
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, 100 + (index * 50));
        });
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
