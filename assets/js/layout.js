/**
 * LAYOUT MANAGER V2 - KW24 APPS
 * Gerenciamento geral do layout e inicialização dos componentes
 */

class LayoutManager {
    constructor() {
        this.sidebar = null;
        this.topbar = null;
        this.initialized = false;
        
        this.init();
    }
    
    /**
     * Inicialização do layout
     */
    init() {
        try {
            // Aguarda que os components sejam carregados
            this.waitForComponents().then(() => {
                this.initializeComponents();
                this.setupGlobalEvents();
                this.initialized = true;
                
                if (window.location.hostname === 'localhost') {
                    console.log('[Layout] Sistema inicializado com sucesso');
                }
            });
        } catch (error) {
            if (window.location.hostname === 'localhost') {
                console.error('[Layout] Erro na inicialização:', error);
            }
        }
    }
    
    /**
     * Aguarda os componentes serem carregados
     */
    waitForComponents() {
        return new Promise((resolve) => {
            const checkComponents = () => {
                // Verifica se as classes dos componentes existem
                const sidebarExists = typeof SidebarManager !== 'undefined';
                const topbarExists = typeof TopbarManager !== 'undefined';
                
                if (sidebarExists && topbarExists) {
                    resolve();
                } else {
                    // Tenta novamente após 100ms
                    setTimeout(checkComponents, 100);
                }
            };
            
            checkComponents();
        });
    }
    
    /**
     * Inicializa os componentes principais
     */
    initializeComponents() {
        try {
            // Inicializa Sidebar se disponível
            if (typeof SidebarManager !== 'undefined') {
                this.sidebar = new SidebarManager();
            }
            
            // Inicializa Topbar se disponível
            if (typeof TopbarManager !== 'undefined') {
                this.topbar = new TopbarManager();
            }
            
            if (window.location.hostname === 'localhost') {
                console.log('[Layout] Componentes inicializados:', {
                    sidebar: !!this.sidebar,
                    topbar: !!this.topbar
                });
            }
        } catch (error) {
            if (window.location.hostname === 'localhost') {
                console.error('[Layout] Erro ao inicializar componentes:', error);
            }
        }
    }
    
    /**
     * Configuração de eventos globais
     */
    setupGlobalEvents() {
        // Redimensionamento da janela
        window.addEventListener('resize', () => {
            this.handleResize();
        });
        
        // Mudança de orientação (mobile)
        window.addEventListener('orientationchange', () => {
            setTimeout(() => {
                this.handleResize();
            }, 100);
        });
        
        // Atalhos de teclado globais
        document.addEventListener('keydown', (e) => {
            this.handleGlobalKeyboard(e);
        });
    }
    
    /**
     * Manipulação de redimensionamento
     */
    handleResize() {
        try {
            // Notifica componentes sobre mudança de tamanho
            if (this.sidebar && typeof this.sidebar.handleResize === 'function') {
                this.sidebar.handleResize();
            }
            
            if (this.topbar && typeof this.topbar.handleResize === 'function') {
                this.topbar.handleResize();
            }
        } catch (error) {
            if (window.location.hostname === 'localhost') {
                console.error('[Layout] Erro no resize:', error);
            }
        }
    }
    
    /**
     * Atalhos de teclado globais
     */
    handleGlobalKeyboard(event) {
        // Ctrl + B - Toggle sidebar
        if (event.ctrlKey && event.key === 'b') {
            event.preventDefault();
            if (this.sidebar && typeof this.sidebar.toggle === 'function') {
                this.sidebar.toggle();
            }
        }
        
        // ESC - Fechar menus
        if (event.key === 'Escape') {
            // Fechar dropdowns do topbar
            if (this.topbar && typeof this.topbar.closeDropdowns === 'function') {
                this.topbar.closeDropdowns();
            }
        }
    }
    
    /**
     * Método público para toggle da sidebar
     */
    toggleSidebar() {
        if (this.sidebar && typeof this.sidebar.toggle === 'function') {
            this.sidebar.toggle();
        }
    }
    
    /**
     * Status do layout
     */
    getStatus() {
        return {
            initialized: this.initialized,
            sidebar: !!this.sidebar,
            topbar: !!this.topbar
        };
    }
}

// =================== INICIALIZAÇÃO =================== //

// Aguarda DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Inicializa o layout manager
    window.layoutManager = new LayoutManager();
    
    if (window.location.hostname === 'localhost') {
        console.log('[Layout] LayoutManager carregado');
    }
});

// =================== UTILITÁRIOS GLOBAIS =================== //

/**
 * Função global para toggle da sidebar (compatibilidade)
 */
window.toggleSidebar = function() {
    if (window.layoutManager) {
        window.layoutManager.toggleSidebar();
    }
};

/**
 * Debug do layout (desenvolvimento)
 */
window.layoutDebug = {
    status: () => {
        return window.layoutManager ? window.layoutManager.getStatus() : null;
    },
    
    reinit: () => {
        if (window.layoutManager) {
            window.layoutManager.init();
        }
    }
};
