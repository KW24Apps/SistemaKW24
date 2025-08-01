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
}

// =================== FUNÇÃO GLOBAL PARA ERROS DE RECUPERAÇÃO =================== //

/**
 * Função global para mostrar erros no estilo do sistema
 */
window.showRecoveryError = function(message) {
    // Remove alert existente
    const existingAlert = document.getElementById('recoveryErrorAlert');
    if (existingAlert) {
        existingAlert.remove();
    }
    
    // Criar novo alert
    const alert = document.createElement('div');
    alert.className = 'alert-top';
    alert.id = 'recoveryErrorAlert';
    alert.innerHTML = `
        <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
        ${message}
    `;
    
    document.body.appendChild(alert);
    
    // Animação de entrada
    setTimeout(() => {
        alert.style.opacity = '1';
        alert.style.transform = 'translateX(-50%) translateY(0)';
    }, 100);
    
    // Auto-esconder após 5 segundos
    setTimeout(() => {
        if (alert && alert.parentNode) {
            alert.style.opacity = '0';
            alert.style.transform = 'translateX(-50%) translateY(-20px)';
            
            setTimeout(() => {
                if (alert.parentNode) {
                    alert.parentNode.removeChild(alert);
                }
            }, 300);
        }
    }, 5000);
    
    // Clique para fechar
    alert.addEventListener('click', () => {
        alert.style.opacity = '0';
        alert.style.transform = 'translateX(-50%) translateY(-20px)';
        
        setTimeout(() => {
            if (alert.parentNode) {
                alert.parentNode.removeChild(alert);
            }
        }, 300);
    });
};

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

// =================== SISTEMA DE RECUPERAÇÃO DE SENHA =================== //

// Variáveis globais para o sistema de recuperação
var originalLoginForm = null;
var userEmail = '';

// Captura o form original na primeira execução
function saveOriginalForm() {
    if (!originalLoginForm) {
        originalLoginForm = document.querySelector('.login-form').innerHTML;
    }
}

// ETAPA 1: Solicitar email/telefone
window.showRecoveryStep1 = function() {
    saveOriginalForm();
    
    // Mostrar loader overlay completo
    showLoader();
    
    // Aguardar um pouco para mostrar o loader antes de trocar o conteúdo
    setTimeout(() => {
        // Adicionar classe recovery-mode ao container
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
        
        // Adiciona event listener para Enter no campo email
        const emailInput = document.getElementById('recoveryIdentifier');
        if (emailInput) {
            emailInput.addEventListener('keydown', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    submitRecoveryStep1();
                }
            });
            
            // Foco automático no input
            emailInput.focus();
        }
        
        // Remove loader após trocar o conteúdo
        hideLoader();
        
        console.log('[Recovery] Etapa 1: Solicitar email');
    }, 400); // Aumentei para 400ms para dar tempo de ver o loader
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
    
    // Adiciona event listener para Enter no campo código
    const codeInput = document.getElementById('recoveryCode');
    if (codeInput) {
        codeInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                submitRecoveryStep2();
            }
        });
        
        // Foco automático no input
        codeInput.focus();
    }
    
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
            
            <button type="button" class="forgot-password-button" onclick="showRecoveryStep2(userEmail)">
                <i class="fas fa-arrow-left"></i>
                <span>Voltar</span>
            </button>
        </div>
    `;
    
    // Adiciona event listeners para Enter nos campos de senha
    const newPasswordInput = document.getElementById('newPassword');
    const confirmPasswordInput = document.getElementById('confirmPassword');
    
    if (newPasswordInput) {
        newPasswordInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                confirmPasswordInput.focus();
            }
        });
        
        // Foco automático no primeiro input
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
    
    console.log('[Recovery] Etapa 4: Sucesso');
}

// Voltar ao login original
window.backToLogin = function() {
    // Mostrar loader overlay completo
    showLoader();
    
    setTimeout(() => {
        if (originalLoginForm) {
            document.querySelector('.login-form').innerHTML = originalLoginForm;
            
            // Remover classe recovery-mode do container
            const container = document.querySelector('.login-container');
            container.classList.remove('recovery-mode');
        }
        
        // Remove loader após restaurar o conteúdo
        hideLoader();
        
        console.log('[Recovery] Voltou ao login');
    }, 400); // 400ms para experiência consistente
}

// Funções de submit com integração ao backend
window.submitRecoveryStep1 = function() {
    const identifier = document.getElementById('recoveryIdentifier').value.trim();
    if (!identifier) {
        showRecoveryError('Por favor, digite seu usuário ou email');
        return;
    }
    
    userEmail = identifier;
    console.log('[Recovery] Enviando para:', identifier);
    
    // Mostra loader
    showLoader();
    
    // Chama API
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
            showRecoveryError(data.message || 'Erro ao enviar código');
        }
    })
    .catch(error => {
        hideLoader();
        console.error('[Recovery] Erro:', error);
        showRecoveryError('Erro de conexão. Tente novamente.');
    });
}

window.submitRecoveryStep2 = function() {
    const code = document.getElementById('recoveryCode').value.trim();
    if (!code) {
        showRecoveryError('Por favor, digite o código');
        return;
    }
    
    console.log('[Recovery] Verificando código:', code);
    
    // Mostra loader
    showLoader();
    
    // Chama API
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
            showRecoveryError(data.message || 'Código inválido');
        }
    })
    .catch(error => {
        hideLoader();
        console.error('[Recovery] Erro:', error);
        showRecoveryError('Erro de conexão. Tente novamente.');
    });
}

window.submitRecoveryStep3 = function() {
    const newPassword = document.getElementById('newPassword').value;
    const confirmPassword = document.getElementById('confirmPassword').value;
    
    if (!newPassword || !confirmPassword) {
        showRecoveryError('Por favor, preencha ambas as senhas');
        return;
    }
    
    if (newPassword !== confirmPassword) {
        showRecoveryError('Senhas não conferem');
        return;
    }
    
    if (newPassword.length < 6) {
        showRecoveryError('Senha deve ter pelo menos 6 caracteres');
        return;
    }
    
    console.log('[Recovery] Alterando senha');
    
    // Mostra loader
    showLoader();
    
    // Chama API
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
            showRecoveryError(data.message || 'Erro ao alterar senha');
        }
    })
    .catch(error => {
        hideLoader();
        console.error('[Recovery] Erro:', error);
        showRecoveryError('Erro de conexão. Tente novamente.');
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
