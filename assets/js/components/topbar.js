/**
 * TOPBAR V2 JAVASCRIPT - TOPBAR MANAGER
 * Sistema robusto de gerenciamento do topbar com integração ao sidebar
 */

class TopbarManager {
    constructor() {
        this.topbar = null;
        this.submenus = null;
        this.profile = null;
        this.profileDropdown = null;
        this.currentSubmenus = null;
        this.isProfileOpen = false;
        this.debounceTimer = null;
        this.eventListeners = new Map();
        
        this.init();
    }

    /**
     * Inicializa o TopbarManager
     */
    init() {
        this.cacheElements();
        this.setupEventListeners();
        this.setupSidebarIntegration();
        this.setupAccessibility();
        this.updateLayout();
        
        console.log('TopbarManager initialized successfully');
    }

    /**
     * Cache dos elementos DOM principais
     */
    cacheElements() {
        this.topbar = document.querySelector('.topbar');
        this.submenus = document.querySelector('.topbar-submenus');
        this.profile = document.querySelector('.topbar-profile');
        this.profileDropdown = document.querySelector('.topbar-profile-dropdown');
        
        if (!this.topbar) {
            console.error('Topbar element not found');
            return;
        }
        
        console.log('Elements cached:', {
            topbar: !!this.topbar,
            submenus: !!this.submenus,
            profile: !!this.profile,
            dropdown: !!this.profileDropdown
        });
    }

    /**
     * Configura todos os event listeners
     */
    setupEventListeners() {
        // Profile dropdown toggle
        if (this.profile) {
            this.addEventListenerWithCleanup(this.profile, 'click', (e) => {
                e.stopPropagation();
                this.toggleProfileDropdown();
            });
        }

        // Fecha dropdown ao clicar fora
        this.addEventListenerWithCleanup(document, 'click', (e) => {
            if (this.isProfileOpen && !this.profile?.contains(e.target)) {
                this.closeProfileDropdown();
            }
        });

        // Escape key para fechar dropdown
        this.addEventListenerWithCleanup(document, 'keydown', (e) => {
            if (e.key === 'Escape' && this.isProfileOpen) {
                this.closeProfileDropdown();
            }
        });

        // Responsive handling
        this.addEventListenerWithCleanup(window, 'resize', 
            this.debounce(() => this.handleResize(), 150)
        );

        // Scroll handling para submenus
        if (this.submenus) {
            this.addEventListenerWithCleanup(this.submenus, 'wheel', (e) => {
                if (this.submenus.scrollWidth > this.submenus.clientWidth) {
                    e.preventDefault();
                    this.submenus.scrollLeft += e.deltaY;
                }
            });
        }
    }

    /**
     * Integração com o Sidebar V2
     */
    setupSidebarIntegration() {
        // Escuta eventos do sidebar
        this.addEventListenerWithCleanup(document, 'sidebar:menuClick', (e) => {
            const { menuItem, submenus } = e.detail;
            this.updateSubmenus(submenus, menuItem);
        });

        // REMOVIDO: eventos de collapsed/expanded que causavam loading desnecessário
        // Os eventos de collapse/expand do sidebar não precisam atualizar submenus

        console.log('Sidebar integration setup complete');
    }

    /**
     * Configura acessibilidade
     */
    setupAccessibility() {
        if (this.profile) {
            this.profile.setAttribute('role', 'button');
            this.profile.setAttribute('aria-haspopup', 'true');
            this.profile.setAttribute('aria-expanded', 'false');
            this.profile.setAttribute('tabindex', '0');
            
            // Enter/Space para abrir dropdown
            this.addEventListenerWithCleanup(this.profile, 'keydown', (e) => {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.toggleProfileDropdown();
                }
            });
        }

        if (this.submenus) {
            this.submenus.setAttribute('role', 'navigation');
            this.submenus.setAttribute('aria-label', 'Submenus dinâmicos');
        }
    }

    /**
     * Atualiza submenus dinamicamente
     * @param {Array} submenusData - Array de objetos com dados dos submenus
     * @param {Object} parentMenu - Menu pai que foi clicado
     */
    updateSubmenus(submenusData, parentMenu) {
        if (!this.submenus) return;

        // Atualização direta sem loading
        this.renderSubmenus(submenusData, parentMenu);
        this.setSubmenuState(submenusData?.length ? 'active' : 'empty');
    }

    /**
     * Renderiza os submenus
     * @param {Array} submenusData - Dados dos submenus
     * @param {Object} parentMenu - Menu pai
     */
    renderSubmenus(submenusData, parentMenu) {
        if (!this.submenus) return;

        const container = this.submenus.querySelector('.submenu-container') || 
                         this.createSubmenuContainer();

        container.innerHTML = '';

        // Se não há submenus, simplesmente deixa vazio (sem mensagem)
        if (!submenusData || !submenusData.length) {
            return;
        }

        submenusData.forEach((submenu, index) => {
            const item = this.createSubmenuItem(submenu, index);
            container.appendChild(item);
        });

        this.currentSubmenus = submenusData;
        console.log('Submenus rendered:', submenusData.length);
    }

    /**
     * Cria container para submenus
     */
    createSubmenuContainer() {
        const container = document.createElement('div');
        container.className = 'submenu-container';
        this.submenus.appendChild(container);
        return container;
    }

    /**
     * Cria item individual do submenu
     * @param {Object} submenu - Dados do submenu
     * @param {number} index - Índice do item
     */
    createSubmenuItem(submenu, index) {
        const item = document.createElement('a');
        item.className = 'submenu-item';
        item.href = submenu.url || '#';
        item.setAttribute('role', 'menuitem');
        item.setAttribute('tabindex', '0');
        
        if (submenu.id) {
            item.setAttribute('data-submenu-id', submenu.id);
        }

        item.innerHTML = `
            ${submenu.icon ? `<i class="${submenu.icon}"></i>` : ''}
            <span>${submenu.text}</span>
        `;

        // Event listener para click
        this.addEventListenerWithCleanup(item, 'click', (e) => {
            e.preventDefault();
            this.handleSubmenuClick(submenu, item);
        });

        // Keyboard navigation
        this.addEventListenerWithCleanup(item, 'keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                this.handleSubmenuClick(submenu, item);
            } else if (e.key === 'ArrowRight') {
                this.focusNextSubmenu(index);
            } else if (e.key === 'ArrowLeft') {
                this.focusPrevSubmenu(index);
            }
        });

        return item;
    }

    /**
     * Manipula click em submenu
     * @param {Object} submenu - Dados do submenu
     * @param {Element} element - Elemento clicado
     */
    handleSubmenuClick(submenu, element) {
        // Remove active de outros
        this.submenus.querySelectorAll('.submenu-item').forEach(item => {
            item.classList.remove('active');
        });

        // Adiciona active no clicado
        element.classList.add('active');

        // Dispara evento customizado
        const event = new CustomEvent('topbar:submenuClick', {
            detail: { submenu, element }
        });
        document.dispatchEvent(event);

        // Se tem URL, navega
        if (submenu.url && submenu.url !== '#') {
            if (submenu.target === '_blank') {
                window.open(submenu.url, '_blank');
            } else {
                window.location.href = submenu.url;
            }
        }

        console.log('Submenu clicked:', submenu);
    }

    /**
     * Define estado dos submenus
     * @param {string} state - Estado: 'empty', 'active', 'hidden'
     */
    setSubmenuState(state) {
        if (!this.submenus) return;

        this.submenus.className = `topbar-submenus ${state}`;
        
        // Sempre define como não ocupado (sem loading)
        this.submenus.setAttribute('aria-busy', 'false');
    }

    /**
     * Toggle do dropdown do profile
     */
    toggleProfileDropdown() {
        if (this.isProfileOpen) {
            this.closeProfileDropdown();
        } else {
            this.openProfileDropdown();
        }
    }

    /**
     * Abre dropdown do profile
     */
    openProfileDropdown() {
        if (!this.profile) return;

        this.profile.classList.add('active');
        this.profile.setAttribute('aria-expanded', 'true');
        this.isProfileOpen = true;

        // Foca primeiro item do dropdown se existir
        setTimeout(() => {
            const firstItem = this.profileDropdown?.querySelector('.dropdown-item');
            if (firstItem) {
                firstItem.focus();
            }
        }, 100);

        console.log('Profile dropdown opened');
    }

    /**
     * Fecha dropdown do profile
     */
    closeProfileDropdown() {
        if (!this.profile) return;

        this.profile.classList.remove('active');
        this.profile.setAttribute('aria-expanded', 'false');
        this.isProfileOpen = false;

        console.log('Profile dropdown closed');
    }

    /**
     * Atualiza layout responsivo
     */
    updateLayout() {
        if (!this.topbar) return;

        // Remove estilos inline para deixar CSS fazer o trabalho
        this.topbar.style.left = '';
        this.topbar.style.width = '';
        
        console.log('Topbar layout updated - CSS rules now control positioning');
    }

    /**
     * Manipula redimensionamento
     */
    handleResize() {
        this.updateLayout();
        
        // Fecha dropdown em mobile se aberto
        if (window.innerWidth <= 767 && this.isProfileOpen) {
            this.closeProfileDropdown();
        }
    }

    /**
     * Navegação por teclado nos submenus
     */
    focusNextSubmenu(currentIndex) {
        const items = this.submenus?.querySelectorAll('.submenu-item');
        if (!items) return;

        const nextIndex = (currentIndex + 1) % items.length;
        items[nextIndex]?.focus();
    }

    focusPrevSubmenu(currentIndex) {
        const items = this.submenus?.querySelectorAll('.submenu-item');
        if (!items) return;

        const prevIndex = currentIndex === 0 ? items.length - 1 : currentIndex - 1;
        items[prevIndex]?.focus();
    }

    /**
     * Utilitário para debounce
     */
    debounce(func, wait) {
        return (...args) => {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => func.apply(this, args), wait);
        };
    }

    /**
     * Adiciona event listener com cleanup automático
     */
    addEventListenerWithCleanup(element, event, handler) {
        if (!element) return;

        element.addEventListener(event, handler);
        
        const key = `${element.constructor.name}-${event}`;
        if (!this.eventListeners.has(key)) {
            this.eventListeners.set(key, []);
        }
        this.eventListeners.get(key).push({ element, event, handler });
    }

    /**
     * API Pública - Métodos para uso externo
     */

    /**
     * Limpa todos os submenus
     */
    clearSubmenus() {
        this.updateSubmenus([], null);
    }

    /**
     * Define submenus manualmente
     * @param {Array} submenus - Array de submenus
     */
    setSubmenus(submenus) {
        this.updateSubmenus(submenus, { text: 'Manual' });
    }

    /**
     * Obtém submenu ativo atual
     */
    getActiveSubmenu() {
        const activeItem = this.submenus?.querySelector('.submenu-item.active');
        return activeItem ? {
            id: activeItem.getAttribute('data-submenu-id'),
            text: activeItem.textContent.trim(),
            element: activeItem
        } : null;
    }

    /**
     * Força atualização do layout
     */
    refresh() {
        this.updateLayout();
    }

    /**
     * Cleanup completo
     */
    destroy() {
        // Remove todos os event listeners
        this.eventListeners.forEach(listeners => {
            listeners.forEach(({ element, event, handler }) => {
                element.removeEventListener(event, handler);
            });
        });
        
        this.eventListeners.clear();
        clearTimeout(this.debounceTimer);
        
        console.log('TopbarManager destroyed');
    }
}

// Inicialização automática quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    window.topbarManager = new TopbarManager();
});

// Export para uso em modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TopbarManager;
}
