/**
 * Log Viewer - Módulo AJAX
 * Este script adiciona funcionalidade AJAX ao visualizador de logs
 * para permitir atualizações de conteúdo sem recarregar a barra lateral
 */

class LogViewerAJAX {
    constructor() {
        this.contentContainer = document.querySelector('.log-viewer-container');
        this.loadingOverlay = null;
        this.activeRequest = null;
        
        this.init();
    }
    
    init() {
        // Criar overlay de carregamento
        this.createLoadingOverlay();
        
        // Inicializar após o DOM estar pronto
        document.addEventListener('DOMContentLoaded', () => {
            this.setupFormHandlers();
            this.setupLinkHandlers();
            
            // Observar mudanças no DOM para capturar links dinâmicos
            this.setupMutationObserver();
        });
        
        console.log('📡 AJAX Log Viewer inicializado');
    }
    
    createLoadingOverlay() {
        // Verificar se o overlay já existe
        if (document.getElementById('ajax-loading-overlay')) {
            this.loadingOverlay = document.getElementById('ajax-loading-overlay');
            return;
        }
        
        // Criar overlay com efeito de desfoque
        const overlay = document.createElement('div');
        overlay.id = 'ajax-loading-overlay';
        overlay.style.position = 'fixed';
        overlay.style.top = '0';
        overlay.style.left = '0';
        overlay.style.width = '100%';
        overlay.style.height = '100%';
        overlay.style.backgroundColor = 'rgba(255, 255, 255, 0.7)';
        overlay.style.backdropFilter = 'blur(5px)';
        overlay.style.zIndex = '9999';
        overlay.style.display = 'none';
        overlay.style.opacity = '0';
        overlay.style.transition = 'opacity 0.3s ease';
        overlay.style.justifyContent = 'center';
        overlay.style.alignItems = 'center';
        
        // Criar spinner container
        const spinnerContainer = document.createElement('div');
        spinnerContainer.style.display = 'flex';
        spinnerContainer.style.flexDirection = 'column';
        spinnerContainer.style.alignItems = 'center';
        spinnerContainer.style.padding = '30px';
        spinnerContainer.style.borderRadius = '12px';
        spinnerContainer.style.backgroundColor = 'rgba(255, 255, 255, 0.95)';
        spinnerContainer.style.boxShadow = '0 10px 25px rgba(0,0,0,0.1)';
        
        // Adicionar spinner
        const spinner = document.createElement('div');
        spinner.style.width = '40px';
        spinner.style.height = '40px';
        spinner.style.border = '4px solid rgba(8, 107, 141, 0.1)';
        spinner.style.borderTop = '4px solid #086B8D';
        spinner.style.borderRadius = '50%';
        spinner.style.animation = 'ajaxSpin 0.8s linear infinite';
        
        // Adicionar texto
        const loadingText = document.createElement('div');
        loadingText.style.marginTop = '15px';
        loadingText.style.color = '#086B8D';
        loadingText.style.fontWeight = '500';
        loadingText.textContent = 'Carregando...';
        
        // Adicionar estilo para animação
        if (!document.getElementById('ajax-spin-style')) {
            const style = document.createElement('style');
            style.id = 'ajax-spin-style';
            style.textContent = '@keyframes ajaxSpin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }';
            document.head.appendChild(style);
        }
        
        spinnerContainer.appendChild(spinner);
        spinnerContainer.appendChild(loadingText);
        overlay.appendChild(spinnerContainer);
        document.body.appendChild(overlay);
        
        this.loadingOverlay = overlay;
    }
    
    showLoading() {
        if (this.loadingOverlay) {
            this.loadingOverlay.style.display = 'flex';
            // Pequeno atraso para garantir que a transição funcione
            setTimeout(() => {
                this.loadingOverlay.style.opacity = '1';
            }, 10);
        }
    }
    
    hideLoading() {
        if (this.loadingOverlay) {
            this.loadingOverlay.style.opacity = '0';
            setTimeout(() => {
                this.loadingOverlay.style.display = 'none';
            }, 300);
        }
    }
    
    setupFormHandlers() {
        // Capturar o formulário de filtro
        const filterForm = document.getElementById('filterForm');
        if (filterForm) {
            // Substituir o comportamento padrão do formulário
            filterForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.loadContentFromForm(filterForm);
            });
            
            // Adicionar handler para mudanças nos filtros
            const filterInputs = filterForm.querySelectorAll('select, input[type="date"]');
            filterInputs.forEach(input => {
                if (input.id !== 'trace') { // Exclude the hidden trace select
                    input.addEventListener('change', () => {
                        this.loadContentFromForm(filterForm);
                    });
                }
            });
            
            // Manipular o trace select de forma especial
            const traceSelect = document.getElementById('trace');
            const traceSearch = document.getElementById('trace-search');
            
            if (traceSelect && traceSearch) {
                // Quando um trace é selecionado do dropdown
                traceSelect.addEventListener('change', () => {
                    traceSearch.value = traceSelect.options[traceSelect.selectedIndex].text;
                    this.loadContentFromForm(filterForm);
                });
            }
        }
        
        // Manipular o seletor de itens por página
        const perPageSelect = document.getElementById('per-page-select');
        if (perPageSelect) {
            perPageSelect.addEventListener('change', () => {
                // Obter os parâmetros atuais da URL
                const url = new URL(window.location.href);
                url.searchParams.set('per_page', perPageSelect.value);
                
                // Carregar conteúdo com os novos parâmetros
                this.loadContent(url.search);
            });
        }
    }
    
    setupLinkHandlers() {
        // Configurar handlers para links que devem usar AJAX
        document.querySelectorAll('.ajax-link, .pagination-link:not(.disabled)').forEach(link => {
            this.setupLinkHandler(link);
        });
        
        // Configurar botões de modo
        document.querySelectorAll('.mode-button').forEach(button => {
            button.addEventListener('click', (e) => {
                e.preventDefault();
                
                // Remover classe ativa de todos os botões
                document.querySelectorAll('.mode-button').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Adicionar classe ativa ao botão clicado
                button.classList.add('active');
                
                // Carregar conteúdo
                this.loadContent(new URL(button.href).search);
            });
        });
    }
    
    setupLinkHandler(link) {
        link.addEventListener('click', (e) => {
            e.preventDefault();
            
            // Apenas processar se não for um link desabilitado
            if (!link.classList.contains('disabled')) {
                // Se for um link de paginação, atualizar a classe ativa
                if (link.classList.contains('pagination-link')) {
                    document.querySelectorAll('.pagination-link').forEach(l => {
                        l.classList.remove('active');
                    });
                    link.classList.add('active');
                }
                
                // Carregar conteúdo
                const url = new URL(link.href);
                this.loadContent(url.search);
            }
        });
    }
    
    setupMutationObserver() {
        // Observar mudanças no DOM para capturar novos links adicionados dinamicamente
        const observer = new MutationObserver(mutations => {
            mutations.forEach(mutation => {
                if (mutation.type === 'childList') {
                    mutation.addedNodes.forEach(node => {
                        // Verificar se o nó é um elemento
                        if (node.nodeType === 1) {
                            // Verificar se é um link que deve usar AJAX
                            if (node.matches && node.matches('.ajax-link, .pagination-link:not(.disabled)')) {
                                this.setupLinkHandler(node);
                            }
                            
                            // Ou se contém links que devem usar AJAX
                            const links = node.querySelectorAll && node.querySelectorAll('.ajax-link, .pagination-link:not(.disabled)');
                            if (links) {
                                links.forEach(link => {
                                    this.setupLinkHandler(link);
                                });
                            }
                        }
                    });
                }
            });
        });
        
        // Iniciar observação
        observer.observe(document.body, { 
            childList: true, 
            subtree: true 
        });
    }
    
    loadContentFromForm(form) {
        // Construir URL a partir do formulário
        const formData = new FormData(form);
        const params = new URLSearchParams();
        
        for (const [key, value] of formData.entries()) {
            params.append(key, value);
        }
        
        // Carregar conteúdo
        this.loadContent('?' + params.toString());
    }
    
    loadContent(queryString) {
        // Cancelar qualquer solicitação ativa
        if (this.activeRequest) {
            this.activeRequest.abort();
        }
        
        // Mostrar overlay de carregamento
        this.showLoading();
        
        // Atualizar a URL no navegador sem recarregar a página
        window.history.pushState({}, '', queryString);
        
        // Criar uma nova solicitação
        this.activeRequest = new AbortController();
        const signal = this.activeRequest.signal;
        
            // Fazer a solicitação AJAX
            let fetchUrl = queryString;
            if (!fetchUrl.includes('.php')) {
                fetchUrl = '/Apps/public/ajax/load_logs_content.php' + queryString;
            }
            // Fazer a solicitação AJAX
            fetch(fetchUrl, { 
                signal,
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Erro na resposta: ' + response.status);
                }
                return response.text();
            })
            .then(html => {
                // Atualizar apenas o conteúdo
                if (this.contentContainer) {
                    this.contentContainer.innerHTML = html;
                    
                    // Reinicializar handlers
                    this.setupFormHandlers();
                    this.setupLinkHandlers();
                    
                    // Executar scripts no conteúdo carregado
                    this.executeScripts(html);
                    
                    // Disparar evento personalizado
                    const event = new CustomEvent('contentLoaded', { detail: { queryString } });
                    document.dispatchEvent(event);
                    
                    // Rolar para o topo
                    window.scrollTo(0, 0);
                }
                
                // Esconder overlay de carregamento
                this.hideLoading();
                this.activeRequest = null;
            })
            .catch(error => {
                // Ignorar erros de solicitação abortada
                if (error.name !== 'AbortError') {
                    console.error('Erro ao carregar conteúdo:', error);
                    
                    // Esconder overlay de carregamento
                    this.hideLoading();
                    this.activeRequest = null;
                    
                    // Mostrar mensagem de erro
                    this.showError('Erro ao carregar conteúdo. Por favor, tente novamente.');
                }
            });
    }
    
    executeScripts(html) {
        // Extrair scripts do HTML e executá-los
        const parser = new DOMParser();
        const doc = parser.parseFromString(html, 'text/html');
        const scripts = doc.querySelectorAll('script');
        
        scripts.forEach(script => {
            // Apenas executar scripts em linha
            if (!script.src) {
                try {
                    eval(script.textContent);
                } catch (e) {
                    console.error('Erro ao executar script:', e);
                }
            }
        });
    }
    
    showError(message) {
        // Criar elemento de notificação
        const notification = document.createElement('div');
        notification.className = 'ajax-error-notification';
        notification.innerHTML = `
            <div class="notification-content">
                <i class="fas fa-exclamation-circle"></i>
                <span>${message}</span>
            </div>
            <button class="notification-close">×</button>
        `;
        
        // Estilizar notificação
        Object.assign(notification.style, {
            position: 'fixed',
            bottom: '20px',
            right: '20px',
            backgroundColor: '#f56565',
            color: 'white',
            padding: '12px 20px',
            borderRadius: '8px',
            boxShadow: '0 4px 15px rgba(0, 0, 0, 0.15)',
            zIndex: '10000',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'space-between',
            fontWeight: '500',
            maxWidth: '350px',
            transform: 'translateX(400px)',
            transition: 'transform 0.3s ease'
        });
        
        // Estilizar conteúdo
        const content = notification.querySelector('.notification-content');
        Object.assign(content.style, {
            display: 'flex',
            alignItems: 'center',
            gap: '10px'
        });
        
        // Estilizar botão de fechar
        const closeButton = notification.querySelector('.notification-close');
        Object.assign(closeButton.style, {
            background: 'none',
            border: 'none',
            color: 'white',
            fontSize: '20px',
            cursor: 'pointer',
            marginLeft: '10px'
        });
        
        // Adicionar ao DOM
        document.body.appendChild(notification);
        
        // Animar entrada
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 10);
        
        // Configurar botão de fechar
        closeButton.addEventListener('click', () => {
            notification.style.transform = 'translateX(400px)';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        });
        
        // Remover após 5 segundos
        setTimeout(() => {
            if (document.body.contains(notification)) {
                notification.style.transform = 'translateX(400px)';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        document.body.removeChild(notification);
                    }
                }, 300);
            }
        }, 5000);
    }
}

// Inicializar quando o documento estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    // Inicializar o módulo AJAX
    window.logViewerAJAX = new LogViewerAJAX();
});

// Função global para mudar itens por página
function changeItemsPerPage(value) {
    // Obter os parâmetros atuais da URL
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    
    // Usar o módulo AJAX para carregar conteúdo se disponível
    if (window.logViewerAJAX) {
        window.logViewerAJAX.loadContent(url.search);
    } else {
        // Fallback para comportamento padrão
        window.location.href = url.toString();
    }
}

