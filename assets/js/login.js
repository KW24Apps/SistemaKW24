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
        this.currentMode = 'login'; // 'login' ou 'recovery'
        
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
        this.toggleButton = document.querySelector('.toggle-password');
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
        // Se não está no modo login, não processa
        if (this.currentMode !== 'login') {
            event.preventDefault();
            return false;
        }
        
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
        
        // Auto-esconder após 10 segundos
        setTimeout(() => {
            this.hideAlert();
        }, 10000); // Mudei para 10 segundos para consistência
        
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
            if (this.alertElement && this.alertElement.parentNode) {
                this.alertElement.parentNode.removeChild(this.alertElement);
            }
        }, 500); // Aumentei para 500ms para consistência
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
        // Validação será feita apenas no PHP para estabilidade
        console.log('[Login] Client-side validation disabled for layout stability');
    }
    
    /**
     * Destruir instância (cleanup)
     */
    destroy() {
        // Remove event listeners se necessário
        console.log('[Login] LoginManager destroyed');
    }
    
    /**
     * Mudar para modo de recuperação
     */
    setRecoveryMode() {
        this.currentMode = 'recovery';
        console.log('[Login] Switched to recovery mode');
    }
    
    /**
     * Voltar para modo de login
     */
    setLoginMode() {
        this.currentMode = 'login';
        console.log('[Login] Switched to login mode');
    }
}

// =================== SISTEMA UNIFICADO DE MENSAGENS =================== //

/**
 * Sistema global para mostrar mensagens de erro/sucesso
 */
window.showSystemMessage = function(message, type = 'error') {
    // Remove mensagem existente
    const existingAlert = document.querySelector('.alert-top');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // Criar nova mensagem
    const alert = document.createElement('div');
    alert.className = 'alert-top';
    alert.innerHTML = `
        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
        ${message}
    `;
    
    document.body.appendChild(alert);
    
    // Função para remover o alert
    function removeAlert() {
        if (alert && alert.parentNode) {
            console.log('🎯 Iniciando animação de saída do alerta...');
            
            // Adiciona classe de animação de saída
            alert.classList.add('alert-exit');
            
            // Remove após a animação completar
            setTimeout(() => {
                if (alert && alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                    console.log('✅ Alerta removido com sucesso');
                }
            }, 300); // Duração da animação de saída
        }
    }
    
    // Auto-esconder após 10 segundos
    const autoHideTimer = setTimeout(() => {
        removeAlert();
    }, 10000);
    
    // Clique para fechar
    alert.addEventListener('click', () => {
        clearTimeout(autoHideTimer); // Cancela o timer automático
        removeAlert();
    });
    
    // ESC para fechar
    const escHandler = function(e) {
        if (e.key === 'Escape') {
            clearTimeout(autoHideTimer);
            removeAlert();
            document.removeEventListener('keydown', escHandler);
        }
    };
    document.addEventListener('keydown', escHandler);
    
    console.log('[SystemMessage] Mensagem exibida:', message);
};

// =================== INICIALIZAÇÃO =================== //

// Aguarda DOM ready
document.addEventListener('DOMContentLoaded', function() {
    // Inicializa o sistema de login
    window.loginManager = new LoginManager();
    
    console.log('[Login V2] Sistema carregado e pronto');
});

// =================== SISTEMA DE RECUPERAÇÃO DE SENHA =================== //

// Estado da recuperação
let recoveryState = {
    originalForm: null,
    userEmail: '',
    currentStep: 1
};

// Captura o form original na primeira execução
function saveOriginalForm() {
    if (!recoveryState.originalForm) {
        recoveryState.originalForm = document.querySelector('.login-form').innerHTML;
    }
}

// ETAPA 1: Solicitar email/telefone
window.showRecoveryStep1 = function() {
    saveOriginalForm();
    
    // Ativar modo recovery no LoginManager
    if (window.loginManager) {
        window.loginManager.setRecoveryMode();
    }
    
    showLoader();
    
    setTimeout(() => {
        const container = document.querySelector('.login-container');
        container.classList.add('recovery-mode');
        
        const form = document.querySelector('.login-form');
        form.innerHTML = `
            <div class="recovery-step">
                <h3>Recuperar Senha</h3>
                <p>Digite seu email ou telefone para receber o código</p>
                
                <div class="input-group">
                    <input 
                        type="text" 
                        id="recoveryIdentifier" 
                        placeholder="Email ou telefone"
                        required 
                    >
                    <i class="fas fa-envelope input-icon"></i>
                </div>
                
                <button type="button" class="login-button" onclick="submitRecoveryStep1()">
                    <span>Enviar Código</span>
                </button>
                
                <button type="button" class="forgot-password-button" onclick="backToLogin()">
                    <i class="fas fa-arrow-left"></i>
                    <span>Voltar ao Login</span>
                </button>
            </div>
        `;
        
        hideLoader();
        
        // Adicionar listener de Enter
        const emailInput = document.getElementById('recoveryIdentifier');
        if (emailInput) {
            emailInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    submitRecoveryStep1();
                }
            });
            emailInput.focus();
        }
        
        recoveryState.currentStep = 1;
        console.log('[Recovery] Etapa 1: Solicitar email');
    }, 400);
}

// ETAPA 2: Digitar código
window.showRecoveryStep2 = function(email) {
    const form = document.querySelector('.login-form');
    const maskedEmail = maskEmail(email);
    
    form.innerHTML = `
        <div class="recovery-step">
            <h3>Digite o Código</h3>
            <p>Código enviado para <strong>${maskedEmail}</strong></p>
            
            <div class="input-group">
                <input 
                    type="text" 
                    id="recoveryCode" 
                    placeholder="000000"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    required 
                >
                <i class="fas fa-key input-icon"></i>
            </div>
            
            <button type="button" class="login-button" onclick="submitRecoveryStep2()">
                <span>Validar Código</span>
            </button>
            
            <button type="button" class="forgot-password-button" onclick="showRecoveryStep1()">
                <i class="fas fa-arrow-left"></i>
                <span>Voltar</span>
            </button>
        </div>
    `;
    
    // Adicionar listener de Enter
    const codeInput = document.getElementById('recoveryCode');
    if (codeInput) {
        codeInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitRecoveryStep2();
            }
        });
        codeInput.focus();
    }
    
    recoveryState.currentStep = 2;
    console.log('[Recovery] Etapa 2: Digitar código');
}

// ETAPA 3: Nova senha
window.showRecoveryStep3 = function() {
    const form = document.querySelector('.login-form');
    
    form.innerHTML = `
        <div class="recovery-step">
            <h3>Nova Senha</h3>
            <p>Digite sua nova senha</p>
            
            <div class="input-group">
                <input 
                    type="password" 
                    id="newPassword" 
                    placeholder="Nova senha"
                    required 
                    minlength="6"
                >
                <i class="fas fa-lock input-icon"></i>
            </div>
            
            <div class="input-group">
                <input 
                    type="password" 
                    id="confirmPassword" 
                    placeholder="Confirmar senha"
                    required 
                    minlength="6"
                >
                <i class="fas fa-lock input-icon"></i>
            </div>
            
            <button type="button" class="login-button" onclick="submitRecoveryStep3()">
                <span>Salvar Nova Senha</span>
            </button>
            
            <button type="button" class="forgot-password-button" onclick="showRecoveryStep2(recoveryState.userEmail)">
                <i class="fas fa-arrow-left"></i>
                <span>Voltar</span>
            </button>
        </div>
    `;
    
    // Adicionar listeners de Enter
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    
    if (newPasswordInput) {
        newPasswordInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                confirmPasswordInput.focus();
            }
        });
        newPasswordInput.focus();
    }
    
    if (confirmPasswordInput) {
        confirmPasswordInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitRecoveryStep3();
            }
        });
    }
    
    recoveryState.currentStep = 3;
    console.log('[Recovery] Etapa 3: Nova senha');
}

// ETAPA 4: Sucesso
window.showRecoveryStep4 = function() {
    const form = document.querySelector('.login-form');
    
    form.innerHTML = `
        <div class="recovery-step" style="text-align: center; padding: 20px 0;">
            <i class="fas fa-check-circle" style="font-size: 48px; color: #00bf74; margin-bottom: 15px;"></i>
            <h3>Senha Alterada!</h3>
            <p>Sua senha foi alterada com sucesso.<br>Você já pode fazer login.</p>
            
            <button type="button" class="login-button" onclick="backToLogin()">
                <span>Fazer Login</span>
            </button>
        </div>
    `;
    
    recoveryState.currentStep = 4;
    console.log('[Recovery] Etapa 4: Sucesso');
}

// Voltar ao login original
window.backToLogin = function() {
    showLoader();
    
    setTimeout(() => {
        if (recoveryState.originalForm) {
            document.querySelector('.login-form').innerHTML = recoveryState.originalForm;
            
            const container = document.querySelector('.login-container');
            container.classList.remove('recovery-mode');
        }
        
        // Voltar para modo login no LoginManager
        if (window.loginManager) {
            window.loginManager.setLoginMode();
        }
        
        hideLoader();
        
        // Reset do estado
        recoveryState.currentStep = 1;
        recoveryState.userEmail = '';
        
        console.log('[Recovery] Voltou ao login');
    }, 400);
}

// Funções de submit com integração ao backend
window.submitRecoveryStep1 = function() {
    const identifier = document.getElementById('recoveryIdentifier').value.trim();
    if (!identifier) {
        showSystemMessage('Por favor, digite seu usuário ou email');
        return;
    }
    
    recoveryState.userEmail = identifier;
    console.log('[Recovery] Enviando para:', identifier);
    
    showLoader();
    
    fetch('/Apps/api/password-recovery.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'send_code',
            identifier: identifier
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoader();
        
        if (data.success) {
            console.log('[Recovery] Código enviado:', data.debug_code);
            showRecoveryStep2(data.masked_email);
        } else {
            showSystemMessage(data.message || 'Erro ao enviar código');
        }
    })
    .catch(error => {
        hideLoader();
        console.error('[Recovery] Erro:', error);
        showSystemMessage('Erro de conexão. Tente novamente.');
    });
}

window.submitRecoveryStep2 = function() {
    const code = document.getElementById('recoveryCode').value.trim();
    if (!code) {
        showSystemMessage('Por favor, digite o código');
        return;
    }
    
    console.log('[Recovery] Verificando código:', code);
    
    showLoader();
    
    fetch('/Apps/api/password-recovery.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'verify_code',
            code: code
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoader();
        
        if (data.success) {
            console.log('[Recovery] Código verificado');
            showRecoveryStep3();
        } else {
            showSystemMessage(data.message || 'Código inválido');
        }
    })
    .catch(error => {
        hideLoader();
        console.error('[Recovery] Erro:', error);
        showSystemMessage('Erro de conexão. Tente novamente.');
    });
}

window.submitRecoveryStep3 = function() {
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (!newPassword || !confirmPassword) {
        showSystemMessage('Por favor, preencha ambas as senhas');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        showSystemMessage('Senhas não conferem');
        return;
    }
    
    if (newPassword.length < 6) {
        showSystemMessage('Senha deve ter pelo menos 6 caracteres');
        return;
    }
    
    console.log('[Recovery] Alterando senha');
    
    showLoader();
    
    fetch('/Apps/api/password-recovery.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'reset_password',
            password: newPassword,
            confirm_password: confirmPassword
        })
    })
    .then(response => response.json())
    .then(data => {
        hideLoader();
        
        if (data.success) {
            console.log('[Recovery] Senha alterada com sucesso');
            showRecoveryStep4();
        } else {
            showSystemMessage(data.message || 'Erro ao alterar senha');
        }
    })
    .catch(error => {
        hideLoader();
        console.error('[Recovery] Erro:', error);
        showSystemMessage('Erro de conexão. Tente novamente.');
    });
}

// Utilitário para mascarar email
window.maskEmail = function(email) {
    if (email.includes('@')) {
        const [user, domain] = email.split('@');
        const maskedUser = user.length > 2 ? user.substring(0, 2) + '*'.repeat(user.length - 2) : user;
        return maskedUser + '@' + domain;
    }
    return email; // Para telefone
}

// Sistema de loader com blur
window.showLoader = function() {
    const container = document.querySelector('.login-container');
    
    // Remove loader existente se houver
    const existingLoader = document.getElementById('recovery-loader');
    if (existingLoader) {
        existingLoader.remove();
    }
    
    // Cria overlay com blur
    const loader = document.createElement('div');
    loader.id = 'recovery-loader';
    loader.innerHTML = `
        <div class="loader-content">
            <div class="spinner"></div>
            <span class="loader-text">Processando...</span>
        </div>
    `;
    
    container.appendChild(loader);
}

window.hideLoader = function() {
    const loader = document.getElementById('recovery-loader');
    if (loader) {
        loader.remove();
    }
}
