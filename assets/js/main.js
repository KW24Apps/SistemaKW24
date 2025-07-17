/**
 * Sistema Administrativo KW24 - JavaScript Principal
 * Funcionalidades: Sidebar, Dashboard Interativo, Animações
 */

class KW24Dashboard {
    constructor() {
        this.sidebar = document.querySelector('.sidebar');
        this.toggleBtn = document.getElementById('sidebarToggle');
        this.mainContent = document.querySelector('.main-content');
        this.body = document.body;
        
        this.init();
    }

    init() {
        this.setupSidebar();
        this.setupDashboardCards();
        this.setupResponsive();
        this.startActivityTimer();
        this.loadSidebarState();
    }

    // Configuração da Sidebar
    setupSidebar() {
        if (this.toggleBtn) {
            this.toggleBtn.addEventListener('click', () => {
                this.toggleSidebar();
                // Garantir que os ícones permaneçam bem posicionados após toggle
                setTimeout(() => {
                    this.adjustIconsVisibility();
                }, 300); // Pequeno delay para permitir que a transição termine
            });
        }

        // Tooltips para sidebar colapsada
        this.setupTooltips();
    }

    // Método para ajustar visibilidade dos ícones
    adjustIconsVisibility() {
        const sidebarLinks = document.querySelectorAll('.sidebar-link');
        const isCollapsed = this.sidebar.classList.contains('collapsed');
        
        sidebarLinks.forEach(link => {
            const icon = link.querySelector('i');
            if (icon) {
                // Reset de posicionamento para evitar problemas
                icon.style.position = 'relative';
                icon.style.left = '0';
                
                // Garantir visibilidade total
                icon.style.opacity = '1';
            }
        });
    }

    toggleSidebar() {
        const isCollapsed = this.sidebar.classList.contains('collapsed');
        
        if (isCollapsed) {
            this.expandSidebar();
        } else {
            this.collapseSidebar();
        }
        
        // Salvar estado no localStorage
        localStorage.setItem('kw24_sidebar_collapsed', !isCollapsed);
    }

    collapseSidebar() {
        this.sidebar.classList.add('collapsed');
        this.body.classList.add('sidebar-collapsed');
        
        // Animação do ícone
        const icon = this.toggleBtn.querySelector('i');
        if (icon) {
            icon.style.transform = 'rotate(180deg)';
        }
    }

    expandSidebar() {
        this.sidebar.classList.remove('collapsed');
        this.body.classList.remove('sidebar-collapsed');
        
        // Animação do ícone
        const icon = this.toggleBtn.querySelector('i');
        if (icon) {
            icon.style.transform = 'rotate(0deg)';
        }
        
        // Garante que os ícones estejam totalmente visíveis após expandir
        setTimeout(() => {
            document.querySelectorAll('.sidebar-link i').forEach(icon => {
                icon.style.opacity = '1';
                icon.style.visibility = 'visible';
            });
        }, 300);
    }

    loadSidebarState() {
        const isCollapsed = localStorage.getItem('kw24_sidebar_collapsed') === 'true';
        if (isCollapsed) {
            this.collapseSidebar();
        }
    }

    setupTooltips() {
        const sidebarLinks = document.querySelectorAll('.sidebar-link');
        
        sidebarLinks.forEach(link => {
            const tooltip = link.querySelector('.menu-tooltip');
            if (tooltip) {
                link.addEventListener('mouseenter', () => {
                    if (this.sidebar.classList.contains('collapsed')) {
                        tooltip.style.display = 'block';
                    }
                });
                
                link.addEventListener('mouseleave', () => {
                    tooltip.style.display = 'none';
                });
            }
        });
    }

    // Animações dos Cards do Dashboard
    setupDashboardCards() {
        const cards = document.querySelectorAll('.dashboard-cards .card');
        
        // Adicionar efeitos hover
        cards.forEach((card, index) => {
            // Animação de entrada com delay
            setTimeout(() => {
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);

            // Efeito hover personalizado
            card.addEventListener('mouseenter', () => {
                this.animateCardHover(card, true);
            });

            card.addEventListener('mouseleave', () => {
                this.animateCardHover(card, false);
            });
        });
    }

    animateCardHover(card, isHover) {
        const cardHeader = card.querySelector('.card-header');
        const cardBody = card.querySelector('.card-body');
        
        if (isHover) {
            card.style.transform = 'translateY(-8px) scale(1.02)';
            cardHeader.style.background = 'linear-gradient(135deg, #3182ce 0%, #2c5aa0 100%)';
            cardBody.style.background = '#f8fafc';
        } else {
            card.style.transform = 'translateY(0) scale(1)';
            cardHeader.style.background = 'linear-gradient(135deg, #4299e1 0%, #3182ce 100%)';
            cardBody.style.background = 'white';
        }
    }

    // Sistema Responsivo
    setupResponsive() {
        // Detectar mobile
        this.checkMobile();
        
        // Listener para mudanças de tamanho
        window.addEventListener('resize', () => {
            this.checkMobile();
        });

        // Menu mobile
        if (window.innerWidth <= 480) {
            this.setupMobileMenu();
        }
    }

    checkMobile() {
        const isMobile = window.innerWidth <= 768;
        const isSmallMobile = window.innerWidth <= 480;
        
        if (isSmallMobile) {
            this.sidebar.classList.add('mobile');
            this.setupMobileMenu();
        } else if (isMobile) {
            this.collapseSidebar();
        } else {
            this.sidebar.classList.remove('mobile');
        }
    }

    setupMobileMenu() {
        // Overlay para mobile
        if (!document.querySelector('.mobile-overlay')) {
            const overlay = document.createElement('div');
            overlay.className = 'mobile-overlay';
            overlay.style.cssText = `
                position: fixed;
                top: 0;
                left: 0;
                width: 100%;
                height: 100%;
                background: rgba(0,0,0,0.5);
                z-index: 999;
                display: none;
            `;
            document.body.appendChild(overlay);

            overlay.addEventListener('click', () => {
                this.closeMobileMenu();
            });
        }

        // Botão mobile
        if (this.toggleBtn) {
            this.toggleBtn.addEventListener('click', (e) => {
                if (window.innerWidth <= 480) {
                    e.stopPropagation();
                    this.toggleMobileMenu();
                }
            });
        }
    }

    toggleMobileMenu() {
        const overlay = document.querySelector('.mobile-overlay');
        const isOpen = this.sidebar.classList.contains('mobile-open');
        
        if (isOpen) {
            this.closeMobileMenu();
        } else {
            this.sidebar.classList.add('mobile-open');
            overlay.style.display = 'block';
        }
    }

    closeMobileMenu() {
        this.sidebar.classList.remove('mobile-open');
        const overlay = document.querySelector('.mobile-overlay');
        if (overlay) {
            overlay.style.display = 'none';
        }
    }

    // Timer de Atividade
    startActivityTimer() {
        const activityItems = document.querySelectorAll('.activity-item');
        
        // Animar entrada dos itens
        activityItems.forEach((item, index) => {
            setTimeout(() => {
                item.style.opacity = '1';
                item.style.transform = 'translateX(0)';
            }, 600 + (index * 100));
        });

        // Atualizar horário do login a cada minuto
        setInterval(() => {
            this.updateLoginTime();
        }, 60000);
    }

    updateLoginTime() {
        const loginItem = document.querySelector('.activity-item span');
        if (loginItem && loginItem.textContent.includes('Login realizado')) {
            const now = new Date();
            const time = now.toLocaleTimeString('pt-BR', { 
                hour: '2-digit', 
                minute: '2-digit' 
            });
            loginItem.textContent = `Login realizado às ${time}`;
        }
    }

    // Métodos Utilitários
    showNotification(message, type = 'info') {
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `
            <i class="fas fa-${type === 'success' ? 'check' : type === 'error' ? 'times' : 'info'}-circle"></i>
            <span>${message}</span>
        `;
        
        notification.style.cssText = `
            position: fixed;
            top: 20px;
            right: 20px;
            background: ${type === 'success' ? '#48bb78' : type === 'error' ? '#f56565' : '#4299e1'};
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.2);
            z-index: 10000;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 500;
            transform: translateX(400px);
            transition: transform 0.3s ease;
        `;
        
        document.body.appendChild(notification);
        
        // Animar entrada
        setTimeout(() => {
            notification.style.transform = 'translateX(0)';
        }, 100);
        
        // Remover após 3 segundos
        setTimeout(() => {
            notification.style.transform = 'translateX(400px)';
            setTimeout(() => {
                document.body.removeChild(notification);
            }, 300);
        }, 3000);
    }

    // Navegação suave
    smoothScrollTo(element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

// Sistema de Loading
class LoadingManager {
    static show(text = 'Carregando...') {
        if (document.querySelector('.loading-overlay')) return;
        
        const overlay = document.createElement('div');
        overlay.className = 'loading-overlay';
        overlay.innerHTML = `
            <div class="loading-spinner">
                <div class="spinner"></div>
                <p>${text}</p>
            </div>
        `;
        
        overlay.style.cssText = `
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(26, 54, 93, 0.9);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        `;
        
        document.body.appendChild(overlay);
    }

    static hide() {
        const overlay = document.querySelector('.loading-overlay');
        if (overlay) {
            overlay.style.opacity = '0';
            setTimeout(() => {
                document.body.removeChild(overlay);
            }, 300);
        }
    }
}

// Inicialização quando DOM estiver pronto
document.addEventListener('DOMContentLoaded', () => {
    // Inicializar dashboard
    const dashboard = new KW24Dashboard();
    
    // Fazer disponível globalmente
    window.KW24 = {
        dashboard,
        LoadingManager,
        showNotification: (message, type) => dashboard.showNotification(message, type)
    };
    
    // Animação inicial de fade-in
    document.body.style.opacity = '0';
    setTimeout(() => {
        document.body.style.transition = 'opacity 0.5s ease';
        document.body.style.opacity = '1';
    }, 100);
    
    console.log('🚀 Sistema KW24 inicializado com sucesso!');
});

// Função para carregar páginas via AJAX e atualizar a main-content
function ajaxNavigate(url) {
    window.KW24.LoadingManager.show('Carregando...');
    fetch(url, {
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => {
        if (!response.ok) throw new Error('Erro ao carregar página');
        return response.text();
    })
    .then(html => {
        // Substitui só o conteúdo principal
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.innerHTML = html;
            document.body.scrollTop = 0;
            document.documentElement.scrollTop = 0;
        }
        window.KW24.LoadingManager.hide();

        // (Opcional) Atualiza URL e título
        window.history.pushState({}, '', url);
        document.title = document.querySelector('.main-content h1') 
            ? document.querySelector('.main-content h1').textContent + ' - Sistema KW24'
            : 'Sistema KW24';
    })
    .catch(err => {
        window.KW24.LoadingManager.hide();
        window.KW24.dashboard.showNotification('Erro ao carregar página.', 'error');
        console.error(err);
    });
}

// Intercepta cliques nos links da sidebar para usar AJAX
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.sidebar-link.ajax-link').forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const url = this.getAttribute('href');
            if (url && url !== '#') {
                ajaxNavigate(url);
            }
        });
    });

    // Permite navegação pelo botão Voltar/Avançar do navegador
    window.addEventListener('popstate', function() {
        ajaxNavigate(location.pathname);
    });
});

// CSS adicional via JavaScript para componentes dinâmicos
const additionalStyles = `
.loading-spinner {
    text-align: center;
    color: white;
}

.spinner {
    width: 40px;
    height: 40px;
    border: 4px solid rgba(255,255,255,0.3);
    border-top: 4px solid white;
    border-radius: 50%;
    animation: spin 1s linear infinite;
    margin: 0 auto 20px;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.mobile-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    z-index: 999;
    display: none;
}

@media (max-width: 480px) {
    .sidebar {
        transform: translateX(-100%);
        transition: transform 0.3s ease;
    }
    
    .sidebar.mobile-open {
        transform: translateX(0);
    }
    
    .toggle-btn {
        position: fixed !important;
        top: 20px !important;
        left: 20px !important;
        right: auto !important;
        z-index: 1001 !important;
    }
}
`;

// Adicionar estilos dinâmicos
const styleSheet = document.createElement('style');
styleSheet.textContent = additionalStyles;
document.head.appendChild(styleSheet);
