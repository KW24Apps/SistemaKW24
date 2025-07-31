/**
 * LOGIN V2 JAVASCRIPT - SISTEMA MODERNO
 * Funcionalidades: Toggle senha, animações, validação, acessibilidade
 */

class LoginManager {
    constructor() {
        this.form = null;
        this.toggleButton = null;
        this.passwordInput = null;
        this.submitButton = null;
        this.alertElement = null;
        
        this.init();
    }
    
    /**
     * Inicialização do sistema de login
     */
    init() {
        try {
            this.bindElements();
            this.setupEventListeners();
            this.setupAccessibility();
            this.handleAlert();
            this.setupFormValidation();
            
            // Log apenas em desenvolvimento
            if (window.location.hostname === 'localhost') {
                console.log('[Login V2] Sistema inicializado');
            }
        } catch (error) {
            // Silently handle errors in production
            if (window.location.hostname === 'localhost') {
                console.error('[Login V2] Erro na inicialização:', error);
            }
        }
    }
    
    /**
     * Vinculação de elementos DOM
     */
    bindElements() {
        this.form = document.querySelector('.login-form');
        this.toggleButton = document.getElementById('toggleSenha');
        this.passwordInput = document.getElementById('senha');
        this.submitButton = document.querySelector('.login-button');
        this.alertElement = document.getElementById('loginErrorAlert');
    }
    
    /**
     * Configuração de event listeners
     */
    setupEventListeners() {
        // Toggle de senha
        if (this.toggleButton && this.passwordInput) {
            this.toggleButton.addEventListener('click', () => {
                this.togglePasswordVisibility();
            });
        }
        
        // Submissão do formulário
        if (this.form) {
            this.form.addEventListener('submit', (e) => {
                this.handleFormSubmit(e);
            });
        }
        
        // Animações de entrada
        window.addEventListener('load', () => {
            this.triggerEntranceAnimations();
        });
        
        // Teclas de atalho
        document.addEventListener('keydown', (e) => {
            this.handleKeyboardShortcuts(e);
        });
    }
    
    /**
     * Toggle de visibilidade da senha
     */
    togglePasswordVisibility() {
        try {
            if (!this.passwordInput || !this.toggleButton) return;
            
            const isPassword = this.passwordInput.type === 'password';
            const eyeIcon = this.toggleButton.querySelector('i');
            
            if (!eyeIcon) return;
            
            if (isPassword) {
                this.passwordInput.type = 'text';
                eyeIcon.className = 'fas fa-eye-slash';
                this.toggleButton.setAttribute('aria-label', 'Ocultar senha');
            } else {
                this.passwordInput.type = 'password';
                eyeIcon.className = 'fas fa-eye';
                this.toggleButton.setAttribute('aria-label', 'Mostrar senha');
            }
            
            // Força o reposicionamento do botão
            this.toggleButton.style.top = '50%';
            this.toggleButton.style.transform = 'translateY(-50%)';
            
            // Foco no input após toggle
            this.passwordInput.focus();
            
            // Log apenas em desenvolvimento
            if (window.location.hostname === 'localhost') {
                console.log('[Login] Password visibility toggled:', !isPassword);
            }
        } catch (error) {
            // Handle error silently in production
            if (window.location.hostname === 'localhost') {
                console.error('[Login] Erro no toggle da senha:', error);
            }
        }
    }
    
    /**
     * Manipulação do submit do formulário
     */
    handleFormSubmit(event) {
        const formData = new FormData(this.form);
        const usuario = formData.get('usuario')?.trim();
        const senha = formData.get('senha')?.trim();
        
        // Validação básica
        if (!usuario || !senha) {
            event.preventDefault();
            this.showError('Por favor, preencha todos os campos');
            return false;
        }
        
        // Adiciona estado de loading
        this.setLoadingState(true);
        
        console.log('[Login] Form submitted for user:', usuario);
        
        // Permite o submit normal (PHP processará)
        return true;
    }
    
    /**
     * Estado de loading no botão
     */
    setLoadingState(loading) {
        if (!this.submitButton) return;
        
        if (loading) {
            this.submitButton.classList.add('loading');
            this.submitButton.disabled = true;
        } else {
            this.submitButton.classList.remove('loading');
            this.submitButton.disabled = false;
        }
    }
    
    /**
     * Manipulação de alertas de erro
     */
    handleAlert() {
        if (!this.alertElement) return;
        
        // Animação de entrada
        setTimeout(() => {
            this.alertElement.style.opacity = '1';
            this.alertElement.style.transform = 'translateX(-50%) translateY(0)';
        }, 100);
        
        // Auto-esconder após 5 segundos
        setTimeout(() => {
            this.hideAlert();
        }, 5000);
        
        // Clique para fechar
        this.alertElement.addEventListener('click', () => {
            this.hideAlert();
        });
    }
    
    /**
     * Esconder alert
     */
    hideAlert() {
        if (!this.alertElement) return;
        
        this.alertElement.style.opacity = '0';
        this.alertElement.style.transform = 'translateX(-50%) translateY(-20px)';
        
        setTimeout(() => {
            if (this.alertElement.parentNode) {
                this.alertElement.parentNode.removeChild(this.alertElement);
            }
        }, 300);
    }
    
    /**
     * Mostrar erro personalizado
     */
    showError(message) {
        // Remove alert existente
        const existingAlert = document.getElementById('loginErrorAlert');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        // Criar novo alert
        const alert = document.createElement('div');
        alert.className = 'alert-top';
        alert.id = 'loginErrorAlert';
        alert.innerHTML = `
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            ${message}
        `;
        
        document.body.appendChild(alert);
        
        // Configurar alert
        this.alertElement = alert;
        this.handleAlert();
    }
    
    /**
     * Configuração de acessibilidade
     */
    setupAccessibility() {
        // ARIA labels dinâmicos
        const inputs = document.querySelectorAll('input[type="text"], input[type="password"]');
        inputs.forEach(input => {
            input.addEventListener('focus', () => {
                input.setAttribute('aria-describedby', input.id + '-help');
            });
        });
        
        // Navegação por Tab aprimorada
        const focusableElements = document.querySelectorAll(
            'input, button, [tabindex]:not([tabindex="-1"])'
        );
        
        focusableElements.forEach((element, index) => {
            element.addEventListener('keydown', (e) => {
                if (e.key === 'Tab') {
                    this.handleTabNavigation(e, index, focusableElements);
                }
            });
        });
    }
    
    /**
     * Navegação por Tab customizada
     */
    handleTabNavigation(event, currentIndex, elements) {
        const isShiftTab = event.shiftKey;
        const lastIndex = elements.length - 1;
        
        if (!isShiftTab && currentIndex === lastIndex) {
            // Último elemento - volta para o primeiro
            event.preventDefault();
            elements[0].focus();
        } else if (isShiftTab && currentIndex === 0) {
            // Primeiro elemento - vai para o último
            event.preventDefault();
            elements[lastIndex].focus();
        }
    }
    
    /**
     * Atalhos de teclado
     */
    handleKeyboardShortcuts(event) {
        // ESC para fechar alert
        if (event.key === 'Escape' && this.alertElement) {
            this.hideAlert();
        }
        
        // Enter no campo usuário -> foca senha
        if (event.key === 'Enter' && event.target.id === 'usuario') {
            event.preventDefault();
            this.passwordInput?.focus();
        }
    }
    
    /**
     * Animações de entrada
     */
    triggerEntranceAnimations() {
        document.body.classList.add('loaded');
        
        // Animação escalonada dos elementos
        const animatedElements = [
            '.login-header img',
            '.login-title', 
            '.login-subtitle',
            '.login-form',
            '.login-footer'
        ];
        
        animatedElements.forEach((selector, index) => {
            const element = document.querySelector(selector);
            if (element) {
                setTimeout(() => {
                    element.style.opacity = '1';
                    element.style.transform = 'translateY(0)';
                }, index * 100);
            }
        });
        
        console.log('[Login] Entrance animations triggered');
    }
    
    /**
     * Configuração de validação do formulário
     */
    setupFormValidation() {
        // VALIDAÇÃO DESABILITADA - estava causando problemas de layout
        // A validação será feita apenas no PHP
        console.log('[Login] Client-side validation disabled for layout stability');
    }
    
    /**
     * Validação de campo individual
     */
    validateField(field) {
        const value = field.value.trim();
        let isValid = true;
        let errorMessage = '';
        
        // Validações básicas - REMOVIDA validação de 6 caracteres que quebrava o layout
        if (!value) {
            isValid = false;
            errorMessage = 'Este campo é obrigatório';
        } else if (field.type === 'text' && value.length < 2) {
            isValid = false;
            errorMessage = 'Mínimo 2 caracteres';
        }
        
        // Aplicar estado visual
        if (!isValid) {
            this.showFieldError(field, errorMessage);
        } else {
            this.clearFieldError(field);
        }
        
        return isValid;
    }
    
    /**
     * Mostrar erro em campo - DESABILITADO
     */
    showFieldError(field, message) {
        // Função desabilitada para evitar problemas de layout
        console.log('[Login] Field error disabled:', message);
    }
    
    /**
     * Limpar erro de campo - DESABILITADO  
     */
    clearFieldError(field) {
        // Função desabilitada para evitar problemas de layout
        // Remove qualquer erro existente
        const errorElement = field.parentNode.querySelector('.field-error');
        if (errorElement) {
            errorElement.remove();
        }
    }
    
    /**
     * Destruir instância (cleanup)
     */
    destroy() {
        // Remove event listeners se necessário
        console.log('[Login] LoginManager destroyed');
    }
}

// =================== INICIALIZAÇÃO =================== //

// Aguarda DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Inicializa o sistema de login
    window.loginManager = new LoginManager();
    
    console.log('[Login V2] Sistema carregado e pronto');
});

// =================== UTILITÁRIOS GLOBAIS =================== //

/**
 * Utilitário para debug (desenvolvimento)
 */
window.loginDebug = {
    getFormData: () => {
        const form = document.querySelector('.login-form');
        if (form) {
            const formData = new FormData(form);
            return Object.fromEntries(formData);
        }
        return null;
    },
    
    simulateError: (message = 'Erro simulado') => {
        if (window.loginManager) {
            window.loginManager.showError(message);
        }
    },
    
    toggleLoading: () => {
        if (window.loginManager) {
            const isLoading = document.querySelector('.login-button').classList.contains('loading');
            window.loginManager.setLoadingState(!isLoading);
        }
    }
};
