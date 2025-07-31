/**
 * TOPBAR JAVASCRIPT - Versão otimizada
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

    init() {
        if (!this.cacheElements()) {
            setTimeout(() => {
                this.init();
            }, 500);
            return;
        }
        
        this.setupEventListeners();
        this.setupSidebarIntegration();
        this.setupAccessibility();
        this.updateLayout();
    }

    cacheElements() {
        this.topbar = document.querySelector('.topbar');
        this.submenus = document.querySelector('.topbar-submenus');
        this.profile = document.querySelector('.topbar-profile');
        this.profileDropdown = document.querySelector('.topbar-profile-dropdown');
        
        return !!this.topbar;
    }

    setupEventListeners() {
        if (this.profile) {
            this.addEventListenerWithCleanup(this.profile, 'click', (e) => {
                e.stopPropagation();
                this.toggleProfileDropdown();
            });
        }

        this.addEventListenerWithCleanup(document, 'click', (e) => {
            if (this.isProfileOpen && !this.profile?.contains(e.target)) {
                this.closeProfileDropdown();
            }
        });

        this.addEventListenerWithCleanup(document, 'keydown', (e) => {
            if (e.key === 'Escape' && this.isProfileOpen) {
                this.closeProfileDropdown();
            }
        });

        this.addEventListenerWithCleanup(window, 'resize', 
            this.debounce(() => this.handleResize(), 150)
        );

        if (this.submenus) {
            this.addEventListenerWithCleanup(this.submenus, 'wheel', (e) => {
                if (this.submenus.scrollWidth > this.submenus.clientWidth) {
                    e.preventDefault();
                    this.submenus.scrollLeft += e.deltaY;
                }
            });
        }
    }

    setupSidebarIntegration() {
        this.addEventListenerWithCleanup(document, 'sidebar:menuClick', (e) => {
            const { menuItem, submenus } = e.detail;
            this.updateSubmenus(submenus, menuItem);
        });
    }

    setupAccessibility() {
        if (this.profile) {
            this.profile.setAttribute('role', 'button');
            this.profile.setAttribute('aria-haspopup', 'true');
            this.profile.setAttribute('aria-expanded', 'false');
            this.profile.setAttribute('tabindex', '0');
            
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

    updateSubmenus(submenusData, parentMenu) {
        if (!this.submenus) return;

        this.renderSubmenus(submenusData, parentMenu);
        this.setSubmenuState(submenusData?.length ? 'active' : 'empty');
    }

    renderSubmenus(submenusData, parentMenu) {
        if (!this.submenus) return;

        const container = this.submenus.querySelector('.submenu-container') || 
                         this.createSubmenuContainer();

        container.innerHTML = '';

        if (!submenusData || !submenusData.length) {
            return;
        }

        submenusData.forEach((submenu, index) => {
            const item = this.createSubmenuItem(submenu, index);
            container.appendChild(item);
        });

        this.currentSubmenus = submenusData;
    }

    createSubmenuContainer() {
        const container = document.createElement('div');
        container.className = 'submenu-container';
        this.submenus.appendChild(container);
        return container;
    }

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

        this.addEventListenerWithCleanup(item, 'click', (e) => {
            e.preventDefault();
            this.handleSubmenuClick(submenu, item);
        });

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

    handleSubmenuClick(submenu, element) {
        this.submenus.querySelectorAll('.submenu-item').forEach(item => {
            item.classList.remove('active');
        });

        element.classList.add('active');

        const event = new CustomEvent('topbar:submenuClick', {
            detail: { submenu, element }
        });
        document.dispatchEvent(event);

        if (submenu.url && submenu.url !== '#') {
            if (submenu.target === '_blank') {
                window.open(submenu.url, '_blank');
            } else {
                window.location.href = submenu.url;
            }
        }
    }

    setSubmenuState(state) {
        if (!this.submenus) return;

        this.submenus.className = `topbar-submenus ${state}`;
        this.submenus.setAttribute('aria-busy', 'false');
    }

    toggleProfileDropdown() {
        if (this.isProfileOpen) {
            this.closeProfileDropdown();
        } else {
            this.openProfileDropdown();
        }
    }

    openProfileDropdown() {
        if (!this.profile) return;

        this.profile.classList.add('active');
        this.profile.setAttribute('aria-expanded', 'true');
        this.isProfileOpen = true;

        setTimeout(() => {
            const firstItem = this.profileDropdown?.querySelector('.dropdown-item');
            if (firstItem) {
                firstItem.focus();
            }
        }, 100);
    }

    closeProfileDropdown() {
        if (!this.profile) return;

        this.profile.classList.remove('active');
        this.profile.setAttribute('aria-expanded', 'false');
        this.isProfileOpen = false;
    }

    updateLayout() {
        if (!this.topbar) return;

        this.topbar.style.left = '';
        this.topbar.style.width = '';
    }

    handleResize() {
        this.updateLayout();
        
        if (window.innerWidth <= 767 && this.isProfileOpen) {
            this.closeProfileDropdown();
        }
    }

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

    debounce(func, wait) {
        return (...args) => {
            clearTimeout(this.debounceTimer);
            this.debounceTimer = setTimeout(() => func.apply(this, args), wait);
        };
    }

    addEventListenerWithCleanup(element, event, handler) {
        if (!element) return;

        element.addEventListener(event, handler);
        
        const key = `${element.constructor.name}-${event}`;
        if (!this.eventListeners.has(key)) {
            this.eventListeners.set(key, []);
        }
        this.eventListeners.get(key).push({ element, event, handler });
    }

    // API Pública
    clearSubmenus() {
        this.updateSubmenus([], null);
    }

    setSubmenus(submenus) {
        this.updateSubmenus(submenus, { text: 'Manual' });
    }

    getActiveSubmenu() {
        const activeItem = this.submenus?.querySelector('.submenu-item.active');
        return activeItem ? {
            id: activeItem.getAttribute('data-submenu-id'),
            text: activeItem.textContent.trim(),
            element: activeItem
        } : null;
    }

    refresh() {
        this.updateLayout();
    }

    destroy() {
        this.eventListeners.forEach(listeners => {
            listeners.forEach(({ element, event, handler }) => {
                element.removeEventListener(event, handler);
            });
        });
        
        this.eventListeners.clear();
        clearTimeout(this.debounceTimer);
    }
}

// Inicialização automática
document.addEventListener('DOMContentLoaded', () => {
    window.topbarManager = new TopbarManager();
});

// Export para uso em modules
if (typeof module !== 'undefined' && module.exports) {
    module.exports = TopbarManager;
}
