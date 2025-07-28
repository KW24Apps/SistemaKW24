/**
 * SIDEBAR JAVASCRIPT MELHORADO
 * Corrige problemas de performance, states e acessibilidade
 */

class SidebarManager {
    constructor() {
        this.sidebar = null;
        this.toggleBtn = null;
        this.hoverTimeout = null;
        this.isCollapsed = false;
        this.isHovered = false;
        
        // Configurações
        this.config = {
            hoverDelay: 500,
            storageKey: 'sidebarState',
            animationDuration: 250
        };
        
        this.init();
    }

    init() {
        // Aguarda DOM estar pronto
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupElements());
        } else {
            this.setupElements();
        }
    }

    setupElements() {
        this.sidebar = document.getElementById('sidebar');
        this.toggleBtn = document.getElementById('sidebarToggle');
        
        if (!this.sidebar || !this.toggleBtn) {
            console.warn('[Sidebar] Elementos não encontrados');
            return;
        }

        this.loadSavedState();
        this.bindEvents();
        this.setupKeyboardNavigation();
        
        console.log('[Sidebar] Inicializado com sucesso');
    }

    loadSavedState() {
        try {
            const savedState = localStorage.getItem(this.config.storageKey);
            if (savedState === 'collapsed') {
                this.setCollapsed(true, false); // false = sem animação inicial
            }
        } catch (error) {
            console.warn('[Sidebar] Erro ao carregar estado salvo:', error);
        }
    }

    bindEvents() {
        // Toggle click
        this.toggleBtn.addEventListener('click', () => this.toggle());
        
        // Hover events (apenas quando colapsado)
        this.sidebar.addEventListener('mouseenter', () => this.handleMouseEnter());
        this.sidebar.addEventListener('mouseleave', () => this.handleMouseLeave());
        
        // Keyboard navigation
        this.sidebar.addEventListener('keydown', (e) => this.handleKeydown(e));
        
        // Cleanup em resize
        window.addEventListener('resize', () => this.handleResize());
        
        // Focus management
        this.sidebar.addEventListener('focusin', () => this.handleFocusIn());
        this.sidebar.addEventListener('focusout', () => this.handleFocusOut());
    }

    toggle() {
        this.setCollapsed(!this.isCollapsed);
        this.saveState();
        
        // Analytics/tracking se necessário
        this.trackUsage('toggle', { collapsed: this.isCollapsed });
    }

    setCollapsed(collapsed, animate = true) {
        this.isCollapsed = collapsed;
        
        // Remove hover state quando expanding/collapsing
        this.setHovered(false);
        
        if (collapsed) {
            this.sidebar.classList.add('collapsed');
            document.body.classList.add('sidebar-collapsed');
        } else {
            this.sidebar.classList.remove('collapsed');
            document.body.classList.remove('sidebar-collapsed');
        }

        // Dispatch custom event para outros componentes
        this.dispatchStateChange();
        
        // Melhora acessibilidade
        this.updateAriaStates();
    }

    handleMouseEnter() {
        if (!this.isCollapsed) return;
        
        // Debounce hover para evitar flickering
        if (this.hoverTimeout) {
            clearTimeout(this.hoverTimeout);
        }
        
        this.hoverTimeout = setTimeout(() => {
            this.setHovered(true);
        }, this.config.hoverDelay);
    }

    handleMouseLeave() {
        if (this.hoverTimeout) {
            clearTimeout(this.hoverTimeout);
            this.hoverTimeout = null;
        }
        
        this.setHovered(false);
    }

    setHovered(hovered) {
        if (this.isHovered === hovered) return;
        
        this.isHovered = hovered;
        
        if (hovered) {
            this.sidebar.classList.add('hovered');
        } else {
            this.sidebar.classList.remove('hovered');
        }
    }

    handleKeydown(event) {
        switch (event.key) {
            case 'Escape':
                if (this.isCollapsed && this.isHovered) {
                    this.setHovered(false);
                    event.preventDefault();
                }
                break;
                
            case 'Enter':
            case ' ':
                if (event.target === this.toggleBtn) {
                    this.toggle();
                    event.preventDefault();
                }
                break;
        }
    }

    handleResize() {
        // Mobile/tablet handling
        const isMobile = window.innerWidth <= 768;
        
        if (isMobile) {
            this.sidebar.classList.add('mobile');
        } else {
            this.sidebar.classList.remove('mobile');
        }
    }

    handleFocusIn() {
        // Quando sidebar ganha foco via teclado, expande se colapsado
        if (this.isCollapsed) {
            this.setHovered(true);
        }
    }

    handleFocusOut(event) {
        // Se foco sair completamente da sidebar, recolhe hover
        setTimeout(() => {
            if (!this.sidebar.contains(document.activeElement)) {
                this.setHovered(false);
            }
        }, 100);
    }

    saveState() {
        try {
            const state = this.isCollapsed ? 'collapsed' : 'expanded';
            localStorage.setItem(this.config.storageKey, state);
        } catch (error) {
            console.warn('[Sidebar] Erro ao salvar estado:', error);
        }
    }

    updateAriaStates() {
        // Melhora acessibilidade
        this.toggleBtn.setAttribute('aria-expanded', !this.isCollapsed);
        this.sidebar.setAttribute('aria-label', 
            this.isCollapsed ? 'Menu lateral colapsado' : 'Menu lateral expandido'
        );
    }

    dispatchStateChange() {
        // Event customizado para outros componentes reagirem
        const event = new CustomEvent('sidebarStateChange', {
            detail: {
                collapsed: this.isCollapsed,
                hovered: this.isHovered
            }
        });
        document.dispatchEvent(event);
    }

    trackUsage(action, data = {}) {
        // Analytics/tracking opcional
        if (window.analytics) {
            window.analytics.track('sidebar_interaction', {
                action,
                ...data,
                timestamp: Date.now()
            });
        }
    }

    // API pública para outros componentes
    getState() {
        return {
            collapsed: this.isCollapsed,
            hovered: this.isHovered
        };
    }

    // Destroy method para cleanup
    destroy() {
        if (this.hoverTimeout) {
            clearTimeout(this.hoverTimeout);
        }
        
        // Remove event listeners se necessário
        window.removeEventListener('resize', this.handleResize);
    }
}

// Auto-initialize
let sidebarManager;

// Initialize on DOM ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        sidebarManager = new SidebarManager();
    });
} else {
    sidebarManager = new SidebarManager();
}

// Export para uso em outros módulos
window.SidebarManager = SidebarManager;
window.sidebarManager = sidebarManager;
