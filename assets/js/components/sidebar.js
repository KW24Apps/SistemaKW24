/**
 * SIDEBAR JAVASCRIPT CONSOLIDADO E MELHORADO
 * Unifica funcionalidades com melhorias de performance e acessibilidade
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
            mobileBreakpoint: 768
        };
        
        this.init();
    }

    /**
     * Inicializa o sidebar
     */
    init() {
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', () => this.setupElements());
        } else {
            this.setupElements();
        }
    }

    /**
     * Configura elementos DOM e eventos
     */
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
        
        console.log('[Sidebar] Inicializado com sucesso');
    }

    /**
     * Carrega estado salvo do localStorage
     */
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

    /**
     * Vincula eventos
     */
    bindEvents() {
        // Toggle click
        this.toggleBtn.addEventListener('click', (e) => {
            this.toggle();
            // CORREÇÃO: Remove o focus após o clique para evitar hover "grudado"
            e.target.blur();
        });
        
        // Hover events (debounced)
        this.sidebar.addEventListener('mouseenter', () => this.handleMouseEnter());
        this.sidebar.addEventListener('mouseleave', () => this.handleMouseLeave());
        
        // Keyboard events
        this.sidebar.addEventListener('keydown', (e) => this.handleKeydown(e));
        
        // Window resize
        window.addEventListener('resize', () => this.handleResize());
        
        // Focus management
        this.sidebar.addEventListener('focusin', () => this.handleFocusIn());
        this.sidebar.addEventListener('focusout', (e) => this.handleFocusOut(e));
        
        // Menu items click - Para integração com topbar
        this.setupMenuItemEvents();
    }

    /**
     * Configura eventos dos itens do menu para integração com topbar
     */
    setupMenuItemEvents() {
        const menuItems = this.sidebar.querySelectorAll('.sidebar-link');
        
        menuItems.forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault(); // Evita navegação para demonstração
                
                const menuData = this.extractMenuData(item);
                const submenus = this.getSubmenusForMenu(menuData.id);
                
                // Dispara evento para o topbar
                const event = new CustomEvent('sidebar:menuClick', {
                    detail: {
                        menuItem: menuData,
                        submenus: submenus
                    }
                });
                document.dispatchEvent(event);
                
                console.log('[Sidebar] Menu clicked:', menuData.text, 'Submenus:', submenus.length);
            });
        });
    }

    /**
     * Extrai dados do item do menu
     */
    extractMenuData(menuElement) {
        const icon = menuElement.querySelector('i');
        const text = menuElement.querySelector('.sidebar-link-text'); // CORRIGIDO: era .sidebar-text
        
        return {
            id: this.generateMenuId(text?.textContent || 'menu'),
            text: text?.textContent || 'Menu',
            icon: icon?.className || 'fas fa-circle',
            url: menuElement.href || '#',
            element: menuElement
        };
    }

    /**
     * Gera ID único para menu baseado no texto
     */
    generateMenuId(text) {
        return text.toLowerCase()
            .replace(/\s+/g, '-')
            .replace(/[^a-z0-9-]/g, '')
            .replace(/-+/g, '-')
            .replace(/^-|-$/g, '');
    }

    /**
     * Retorna submenus para um menu específico
     */
    getSubmenusForMenu(menuId) {
        // Dados dos submenus baseados nos menus reais do sidebar.php
        const submenusMap = {
            'dashboard': [
                { id: 'dash-overview', text: 'Visão Geral', icon: 'fas fa-chart-line', url: '/Appsv2/public/dashboard.php?view=overview' },
                { id: 'dash-analytics', text: 'Analytics', icon: 'fas fa-chart-bar', url: '/Appsv2/public/dashboard.php?view=analytics' },
                { id: 'dash-kpi', text: 'KPIs', icon: 'fas fa-tachometer-alt', url: '/Appsv2/public/dashboard.php?view=kpi' }
            ],
            'cadastro': [
                { id: 'cad-cliente', text: 'Novo Cliente', icon: 'fas fa-user-plus', url: '/Appsv2/public/cliente_create.php' },
                { id: 'cad-contato', text: 'Novo Contato', icon: 'fas fa-address-card', url: '/Appsv2/public/contato_create.php' },
                { id: 'cad-import', text: 'Importar Dados', icon: 'fas fa-upload', url: '/Appsv2/public/cadastro.php?action=import' }
            ],
            'relatórios': [
                { id: 'rel-clientes', text: 'Relatório de Clientes', icon: 'fas fa-users', url: '/Appsv2/public/relatorio.php?type=clientes' },
                { id: 'rel-vendas', text: 'Relatório de Vendas', icon: 'fas fa-chart-line', url: '/Appsv2/public/relatorio.php?type=vendas' },
                { id: 'rel-custom', text: 'Relatório Personalizado', icon: 'fas fa-cogs', url: '/Appsv2/public/relatorio.php?type=custom' }
            ],
            'logs': [
                { id: 'log-system', text: 'Logs do Sistema', icon: 'fas fa-server', url: '/Appsv2/public/logs.php?type=system' },
                { id: 'log-user', text: 'Logs de Usuário', icon: 'fas fa-user-clock', url: '/Appsv2/public/logs.php?type=user' },
                { id: 'log-errors', text: 'Logs de Erro', icon: 'fas fa-exclamation-triangle', url: '/Appsv2/public/logs.php?type=errors' }
            ]
        };
        
        return submenusMap[menuId] || [];
    }

    /**
     * Configura acessibilidade
     */
    setupAccessibility() {
        this.sidebar.setAttribute('role', 'navigation');
        this.sidebar.setAttribute('aria-label', 'Menu principal');
        this.updateAriaStates();
        
        // Melhora navegação por teclado
        const links = this.sidebar.querySelectorAll('.sidebar-link');
        links.forEach(link => {
            link.setAttribute('tabindex', '0');
        });
    }

    /**
     * Alterna estado do sidebar
     */
    toggle() {
        this.setCollapsed(!this.isCollapsed);
        this.saveState();
    }

    /**
     * Define estado colapsado
     */
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

    /**
     * Gerencia hover com debounce
     */
    handleMouseEnter() {
        if (!this.isCollapsed) return;
        
        if (this.hoverTimeout) {
            clearTimeout(this.hoverTimeout);
        }
        
        this.hoverTimeout = setTimeout(() => {
            this.setHovered(true);
        }, this.config.hoverDelay);
    }

    /**
     * Remove hover
     */
    handleMouseLeave() {
        if (this.hoverTimeout) {
            clearTimeout(this.hoverTimeout);
            this.hoverTimeout = null;
        }
        this.setHovered(false);
    }

    /**
     * Define estado hover
     */
    setHovered(hovered) {
        if (this.isHovered === hovered) return;
        
        this.isHovered = hovered;
        
        if (hovered) {
            this.sidebar.classList.add('hovered');
        } else {
            this.sidebar.classList.remove('hovered');
        }
    }

    /**
     * Gerencia eventos de teclado
     */
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

    /**
     * Gerencia responsividade
     */
    handleResize() {
        const isMobile = window.innerWidth <= this.config.mobileBreakpoint;
        
        if (isMobile) {
            this.sidebar.classList.add('mobile');
        } else {
            this.sidebar.classList.remove('mobile');
        }
    }

    /**
     * Expande quando ganha foco via teclado
     */
    handleFocusIn() {
        if (this.isCollapsed) {
            this.setHovered(true);
        }
    }

    /**
     * Recolhe quando perde foco completamente
     */
    handleFocusOut(event) {
        setTimeout(() => {
            if (!this.sidebar.contains(document.activeElement)) {
                this.setHovered(false);
            }
        }, 100);
    }

    /**
     * Salva estado no localStorage
     */
    saveState() {
        try {
            const state = this.isCollapsed ? 'collapsed' : 'expanded';
            localStorage.setItem(this.config.storageKey, state);
        } catch (error) {
            console.warn('[Sidebar] Erro ao salvar estado:', error);
        }
    }

    /**
     * Atualiza estados ARIA para acessibilidade
     */
    updateAriaStates() {
        this.toggleBtn.setAttribute('aria-expanded', !this.isCollapsed);
        this.sidebar.setAttribute('aria-label', 
            this.isCollapsed ? 'Menu lateral colapsado' : 'Menu lateral expandido'
        );
    }

    /**
     * Dispara evento customizado
     */
    dispatchStateChange() {
        const event = new CustomEvent('sidebarStateChange', {
            detail: {
                collapsed: this.isCollapsed,
                hovered: this.isHovered
            }
        });
        document.dispatchEvent(event);
        
        // Eventos específicos para o topbar
        if (this.isCollapsed) {
            const collapseEvent = new CustomEvent('sidebar:collapsed');
            document.dispatchEvent(collapseEvent);
        } else {
            const expandEvent = new CustomEvent('sidebar:expanded');
            document.dispatchEvent(expandEvent);
        }
    }

    /**
     * API pública - retorna estado atual
     */
    getState() {
        return {
            collapsed: this.isCollapsed,
            hovered: this.isHovered
        };
    }

    /**
     * Cleanup para destruir instância
     */
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
