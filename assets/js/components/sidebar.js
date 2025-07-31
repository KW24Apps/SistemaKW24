/**
 * SIDEBAR JAVASCRIPT - Versão otimizada
 */

class SidebarManager {
    constructor() {
        this.sidebar = null;
        this.toggleBtn = null;
        this.hoverTimeout = null;
        this.isCollapsed = false;
        this.isHovered = false;
        
        this.config = {
            hoverDelay: 500,
            storageKey: 'sidebarState',
            mobileBreakpoint: 768
        };
        
        this.init();
    }

    init() {
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
        this.setupAccessibility();
        this.handleResize();
    }

    loadSavedState() {
        try {
            const savedState = localStorage.getItem(this.config.storageKey);
            if (savedState === 'collapsed') {
                this.setCollapsed(true, false);
            }
        } catch (error) {
            console.warn('[Sidebar] Erro ao carregar estado:', error);
        }
    }

    bindEvents() {
        this.toggleBtn.addEventListener('click', (e) => {
            this.toggle();
            e.target.blur();
        });
        
        this.sidebar.addEventListener('mouseenter', () => this.handleMouseEnter());
        this.sidebar.addEventListener('mouseleave', () => this.handleMouseLeave());
        this.sidebar.addEventListener('keydown', (e) => this.handleKeydown(e));
        
        window.addEventListener('resize', () => this.handleResize());
        
        this.sidebar.addEventListener('focusin', () => this.handleFocusIn());
        this.sidebar.addEventListener('focusout', (e) => this.handleFocusOut(e));
        
        this.setupMenuItemEvents();
    }

    setupMenuItemEvents() {
        const menuItems = this.sidebar.querySelectorAll('.sidebar-link');
        
        menuItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                
                const menuData = this.extractMenuData(item);
                const submenus = this.getSubmenusForMenu(menuData.id);
                
                const event = new CustomEvent('sidebar:menuClick', {
                    detail: {
                        menuItem: menuData,
                        submenus: submenus
                    }
                });
                document.dispatchEvent(event);
            });
        });
    }

    extractMenuData(menuElement) {
        const icon = menuElement.querySelector('i');
        const text = menuElement.querySelector('.sidebar-link-text');
        
        return {
            id: this.generateMenuId(text?.textContent || 'menu'),
            text: text?.textContent || 'Menu',
            icon: icon?.className || 'fas fa-circle',
            url: menuElement.href || '#',
            element: menuElement
        };
    }

    generateMenuId(text) {
        return text.toLowerCase()
            .replace(/\s+/g, '-')
            .replace(/[^a-z0-9-]/g, '')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    }

    getSubmenusForMenu(menuId) {
        const submenusMap = {
            'dashboard': [
                { id: 'dash-overview', text: 'Visão Geral', icon: 'fas fa-chart-line', url: '/Apps/public/dashboard.php?view=overview' },
                { id: 'dash-analytics', text: 'Analytics', icon: 'fas fa-chart-bar', url: '/Apps/public/dashboard.php?view=analytics' },
                { id: 'dash-kpi', text: 'KPIs', icon: 'fas fa-tachometer-alt', url: '/Apps/public/dashboard.php?view=kpi' }
            ],
            'cadastro': [
                { id: 'cad-cliente', text: 'Novo Cliente', icon: 'fas fa-user-plus', url: '/Apps/public/cliente_create.php' },
                { id: 'cad-contato', text: 'Novo Contato', icon: 'fas fa-address-card', url: '/Apps/public/contato_create.php' },
                { id: 'cad-import', text: 'Importar Dados', icon: 'fas fa-upload', url: '/Apps/public/cadastro.php?action=import' }
            ],
            'relatórios': [
                { id: 'rel-clientes', text: 'Relatório de Clientes', icon: 'fas fa-users', url: '/Apps/public/relatorio.php?type=clientes' },
                { id: 'rel-vendas', text: 'Relatório de Vendas', icon: 'fas fa-chart-line', url: '/Apps/public/relatorio.php?type=vendas' },
                { id: 'rel-custom', text: 'Relatório Personalizado', icon: 'fas fa-cogs', url: '/Apps/public/relatorio.php?type=custom' }
            ],
            'logs': [
                { id: 'log-system', text: 'Logs do Sistema', icon: 'fas fa-server', url: '/Apps/public/logs.php?type=system' },
                { id: 'log-user', text: 'Logs de Usuário', icon: 'fas fa-user-clock', url: '/Apps/public/logs.php?type=user' },
                { id: 'log-errors', text: 'Logs de Erro', icon: 'fas fa-exclamation-triangle', url: '/Apps/public/logs.php?type=errors' }
            ]
        };
        
        return submenusMap[menuId] || [];
    }

    setupAccessibility() {
        this.sidebar.setAttribute('role', 'navigation');
        this.sidebar.setAttribute('aria-label', 'Menu principal');
        this.updateAriaStates();
        
        const links = this.sidebar.querySelectorAll('.sidebar-link');
        links.forEach(link => {
            link.setAttribute('tabindex', '0');
        });
    }

    toggle() {
        this.setCollapsed(!this.isCollapsed);
        this.saveState();
    }

    setCollapsed(collapsed, animate = true) {
        this.isCollapsed = collapsed;
        this.setHovered(false);
        
        if (collapsed) {
            this.sidebar.classList.add('collapsed');
            document.body.classList.add('sidebar-collapsed');
        } else {
            this.sidebar.classList.remove('collapsed');
            document.body.classList.remove('sidebar-collapsed');
        }

        this.updateAriaStates();
        this.dispatchStateChange();
    }

    handleMouseEnter() {
        if (!this.isCollapsed) return;
        
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
        const isMobile = window.innerWidth <= this.config.mobileBreakpoint;
        
        if (isMobile) {
            this.sidebar.classList.add('mobile');
        } else {
            this.sidebar.classList.remove('mobile');
        }
    }

    handleFocusIn() {
        if (this.isCollapsed) {
            this.setHovered(true);
        }
    }

    handleFocusOut(event) {
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
        this.toggleBtn.setAttribute('aria-expanded', !this.isCollapsed);
        this.sidebar.setAttribute('aria-label', 
            this.isCollapsed ? 'Menu lateral colapsado' : 'Menu lateral expandido'
        );
    }

    dispatchStateChange() {
        const event = new CustomEvent('sidebarStateChange', {
            detail: {
                collapsed: this.isCollapsed,
                hovered: this.isHovered
            }
        });
        document.dispatchEvent(event);
        
        if (this.isCollapsed) {
            const collapseEvent = new CustomEvent('sidebar:collapsed');
            document.dispatchEvent(collapseEvent);
        } else {
            const expandEvent = new CustomEvent('sidebar:expanded');
            document.dispatchEvent(expandEvent);
        }
    }

    getState() {
        return {
            collapsed: this.isCollapsed,
            hovered: this.isHovered
        };
    }

    destroy() {
        if (this.hoverTimeout) {
            clearTimeout(this.hoverTimeout);
        }
        window.removeEventListener('resize', this.handleResize);
    }
}

// Auto-inicialização
let sidebarManager;

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => {
        sidebarManager = new SidebarManager();
    });
} else {
    sidebarManager = new SidebarManager();
}

// Export para uso global
window.SidebarManager = SidebarManager;
window.sidebarManager = sidebarManager;
