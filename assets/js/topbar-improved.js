/**
 * TopbarManager - Gerenciador melhorado para topbar
 * Corrige problemas de state management e interactions
 */

class TopbarManager {
    constructor() {
        this.topbar = null;
        this.profileDropdown = null;
        this.submenuButtons = [];
        this.isDropdownOpen = false;
        this.currentActiveSubmenu = null;
        this.resizeObserver = null;
        
        this.init();
    }

    /**
     * Inicialização do gerenciador
     */
    init() {
        this.cacheElements();
        this.bindEvents();
        this.setupResizeObserver();
        this.initKeyboardNavigation();
        this.setInitialStates();
    }

    /**
     * Cache dos elementos DOM
     */
    cacheElements() {
        this.topbar = document.querySelector('.topbar');
        this.profileDropdown = document.querySelector('.topbar-profile');
        this.submenuButtons = Array.from(document.querySelectorAll('.topbar-submenu-btn'));
    }

    /**
     * Configuração dos event listeners
     */
    bindEvents() {
        if (!this.topbar) return;

        // Profile dropdown
        if (this.profileDropdown) {
            this.profileDropdown.addEventListener('click', this.toggleProfileDropdown.bind(this));
        }

        // Submenu buttons
        this.submenuButtons.forEach(btn => {
            btn.addEventListener('click', this.handleSubmenuClick.bind(this, btn));
        });

        // Close dropdown on outside click
        document.addEventListener('click', this.handleOutsideClick.bind(this));
        
        // Close dropdown on escape
        document.addEventListener('keydown', this.handleKeyDown.bind(this));

        // Window resize
        window.addEventListener('resize', this.handleResize.bind(this));
    }

    /**
     * Toggle do dropdown de perfil
     * @param {Event} event 
     */
    toggleProfileDropdown(event) {
        event.stopPropagation();
        
        this.isDropdownOpen = !this.isDropdownOpen;
        this.profileDropdown.classList.toggle('active', this.isDropdownOpen);
        
        // ARIA support
        this.profileDropdown.setAttribute('aria-expanded', this.isDropdownOpen);
        
        this.announceToScreenReader(
            this.isDropdownOpen ? 'Menu de perfil aberto' : 'Menu de perfil fechado'
        );
    }

    /**
     * Manipula clique nos botões do submenu
     * @param {HTMLElement} button 
     * @param {Event} event 
     */
    handleSubmenuClick(button, event) {
        event.preventDefault();
        
        // Remove active de todos os botões
        this.submenuButtons.forEach(btn => {
            btn.classList.remove('active');
            btn.setAttribute('aria-pressed', 'false');
        });
        
        // Ativa o botão clicado
        button.classList.add('active');
        button.setAttribute('aria-pressed', 'true');
        
        this.currentActiveSubmenu = button;
        
        // Executa callback se existir
        const callback = button.dataset.callback;
        if (callback && typeof window[callback] === 'function') {
            window[callback](button);
        }
        
        // Carrega conteúdo via AJAX se especificado
        const ajaxUrl = button.dataset.ajaxUrl;
        if (ajaxUrl) {
            this.loadSubmenuContent(ajaxUrl, button);
        }
        
        this.announceToScreenReader(`${button.textContent} selecionado`);
    }

    /**
     * Carrega conteúdo via AJAX
     * @param {string} url 
     * @param {HTMLElement} button 
     */
    async loadSubmenuContent(url, button) {
        try {
            // Adiciona estado de loading
            button.classList.add('loading');
            
            const response = await fetch(url);
            if (!response.ok) throw new Error('Erro na requisição');
            
            const content = await response.text();
            
            // Atualiza conteúdo principal
            const mainContent = document.querySelector('.main-content');
            if (mainContent) {
                mainContent.innerHTML = content;
                
                // Dispara evento para componentes que precisam se reinicializar
                window.dispatchEvent(new CustomEvent('contentLoaded', {
                    detail: { source: 'topbar-submenu', button }
                }));
            }
            
        } catch (error) {
            console.error('Erro ao carregar conteúdo:', error);
            this.showError('Erro ao carregar conteúdo. Tente novamente.');
        } finally {
            button.classList.remove('loading');
        }
    }

    /**
     * Fecha dropdown ao clicar fora
     * @param {Event} event 
     */
    handleOutsideClick(event) {
        if (!this.profileDropdown) return;
        
        if (!this.profileDropdown.contains(event.target)) {
            this.closeProfileDropdown();
        }
    }

    /**
     * Manipula teclas globais
     * @param {KeyboardEvent} event 
     */
    handleKeyDown(event) {
        switch (event.key) {
            case 'Escape':
                this.closeProfileDropdown();
                break;
            case 'ArrowLeft':
                if (document.activeElement.classList.contains('topbar-submenu-btn')) {
                    this.navigateSubmenu(-1);
                    event.preventDefault();
                }
                break;
            case 'ArrowRight':
                if (document.activeElement.classList.contains('topbar-submenu-btn')) {
                    this.navigateSubmenu(1);
                    event.preventDefault();
                }
                break;
        }
    }

    /**
     * Navega pelos botões do submenu com teclado
     * @param {number} direction 
     */
    navigateSubmenu(direction) {
        const currentIndex = this.submenuButtons.findIndex(btn => btn === document.activeElement);
        if (currentIndex === -1) return;
        
        const nextIndex = currentIndex + direction;
        if (nextIndex >= 0 && nextIndex < this.submenuButtons.length) {
            this.submenuButtons[nextIndex].focus();
        }
    }

    /**
     * Fecha dropdown de perfil
     */
    closeProfileDropdown() {
        if (!this.isDropdownOpen) return;
        
        this.isDropdownOpen = false;
        this.profileDropdown.classList.remove('active');
        this.profileDropdown.setAttribute('aria-expanded', 'false');
    }

    /**
     * Manipula redimensionamento da janela
     */
    handleResize() {
        // Debounce para performance
        clearTimeout(this.resizeTimeout);
        this.resizeTimeout = setTimeout(() => {
            this.adjustForMobile();
        }, 100);
    }

    /**
     * Ajustes para mobile
     */
    adjustForMobile() {
        const isMobile = window.innerWidth <= 768;
        
        if (isMobile) {
            // Fecha dropdown se estiver aberto
            this.closeProfileDropdown();
            
            // Ajusta submenu para scroll horizontal se necessário
            const submenu = document.querySelector('.topbar-submenu');
            if (submenu) {
                const isOverflowing = submenu.scrollWidth > submenu.clientWidth;
                submenu.classList.toggle('overflow-scroll', isOverflowing);
            }
        }
    }

    /**
     * Configuração do ResizeObserver
     */
    setupResizeObserver() {
        if (!window.ResizeObserver) return;
        
        this.resizeObserver = new ResizeObserver(entries => {
            for (const entry of entries) {
                this.adjustForMobile();
            }
        });
        
        if (this.topbar) {
            this.resizeObserver.observe(this.topbar);
        }
    }

    /**
     * Inicializa navegação por teclado
     */
    initKeyboardNavigation() {
        this.submenuButtons.forEach((btn, index) => {
            btn.setAttribute('tabindex', '0');
            btn.setAttribute('role', 'button');
            btn.setAttribute('aria-pressed', 'false');
        });
        
        if (this.profileDropdown) {
            this.profileDropdown.setAttribute('tabindex', '0');
            this.profileDropdown.setAttribute('role', 'button');
            this.profileDropdown.setAttribute('aria-expanded', 'false');
            this.profileDropdown.setAttribute('aria-haspopup', 'true');
        }
    }

    /**
     * Define estados iniciais
     */
    setInitialStates() {
        // Ativa primeiro botão do submenu se nenhum estiver ativo
        const activeBtn = this.submenuButtons.find(btn => btn.classList.contains('active'));
        if (!activeBtn && this.submenuButtons.length > 0) {
            this.submenuButtons[0].classList.add('active');
            this.submenuButtons[0].setAttribute('aria-pressed', 'true');
            this.currentActiveSubmenu = this.submenuButtons[0];
        }
    }

    /**
     * Exibe mensagem de erro
     * @param {string} message 
     */
    showError(message) {
        // Implementar sistema de notificações se necessário
        console.error(message);
        
        // Fallback para alert
        if (confirm(`${message}\n\nDeseja tentar novamente?`)) {
            location.reload();
        }
    }

    /**
     * Anuncia para screen readers
     * @param {string} message 
     */
    announceToScreenReader(message) {
        const announcement = document.createElement('div');
        announcement.setAttribute('aria-live', 'polite');
        announcement.setAttribute('aria-atomic', 'true');
        announcement.className = 'sr-only';
        announcement.textContent = message;
        
        document.body.appendChild(announcement);
        
        setTimeout(() => {
            document.body.removeChild(announcement);
        }, 1000);
    }

    /**
     * Atualiza estado ativo do submenu
     * @param {string} identifier 
     */
    setActiveSubmenu(identifier) {
        const button = this.submenuButtons.find(btn => 
            btn.dataset.id === identifier || 
            btn.textContent.toLowerCase().includes(identifier.toLowerCase())
        );
        
        if (button) {
            this.handleSubmenuClick(button, new Event('click'));
        }
    }

    /**
     * Adiciona novo botão ao submenu
     * @param {Object} config 
     */
    addSubmenuButton(config) {
        const button = document.createElement('button');
        button.className = 'topbar-submenu-btn';
        button.textContent = config.text;
        button.dataset.id = config.id;
        button.dataset.callback = config.callback;
        button.dataset.ajaxUrl = config.ajaxUrl;
        
        // Adiciona aos elementos cacheados
        this.submenuButtons.push(button);
        
        // Adiciona eventos
        button.addEventListener('click', this.handleSubmenuClick.bind(this, button));
        
        // Adiciona ao DOM
        const submenu = document.querySelector('.topbar-submenu');
        if (submenu) {
            submenu.appendChild(button);
        }
        
        return button;
    }

    /**
     * Remove botão do submenu
     * @param {string} identifier 
     */
    removeSubmenuButton(identifier) {
        const index = this.submenuButtons.findIndex(btn => 
            btn.dataset.id === identifier
        );
        
        if (index !== -1) {
            const button = this.submenuButtons[index];
            button.remove();
            this.submenuButtons.splice(index, 1);
        }
    }

    /**
     * Destroi o gerenciador
     */
    destroy() {
        // Remove event listeners
        if (this.profileDropdown) {
            this.profileDropdown.removeEventListener('click', this.toggleProfileDropdown);
        }
        
        this.submenuButtons.forEach(btn => {
            btn.removeEventListener('click', this.handleSubmenuClick);
        });
        
        document.removeEventListener('click', this.handleOutsideClick);
        document.removeEventListener('keydown', this.handleKeyDown);
        window.removeEventListener('resize', this.handleResize);
        
        // Disconnects observers
        if (this.resizeObserver) {
            this.resizeObserver.disconnect();
        }
        
        // Clear timeouts
        clearTimeout(this.resizeTimeout);
    }
}

// Inicialização automática
document.addEventListener('DOMContentLoaded', () => {
    window.topbarManager = new TopbarManager();
});

// Export para módulos
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TopbarManager;
}
