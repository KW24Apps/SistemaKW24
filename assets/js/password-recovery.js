/**
 * PASSWORD RECOVERY - SISTEMA KW24
 * Gerencia todo o fluxo de recuperação de senha no modal
 */

class PasswordRecovery {
    constructor() {
        this.currentStep = 1;
        this.identifier = '';
        this.recoveryId = '';
        this.timer = null;
        this.timeLeft = 900; // 15 minutos em segundos
        
        this.init();
    }
    
    init() {
        this.bindEvents();
        this.setupPasswordStrength();
    }
    
    bindEvents() {
        // Formulário etapa 1 - Enviar email/telefone
        document.getElementById('recoveryForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.sendRecoveryCode();
        });
        
        // Formulário etapa 2 - Validar código
        document.getElementById('codeForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.validateCode();
        });
        
        // Formulário etapa 3 - Nova senha
        document.getElementById('newPasswordForm').addEventListener('submit', (e) => {
            e.preventDefault();
            this.resetPassword();
        });
        
        // Input do código - permitir apenas números
        document.getElementById('recoveryCode').addEventListener('input', (e) => {
            e.target.value = e.target.value.replace(/[^0-9]/g, '').substring(0, 6);
        });
        
        // Fechar modal com ESC
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && document.getElementById('recoveryModal').classList.contains('active')) {
                this.closeModal();
            }
        });
        
        // Fechar modal clicando fora
        document.getElementById('recoveryModal').addEventListener('click', (e) => {
            if (e.target === e.currentTarget) {
                this.closeModal();
            }
        });
    }
    
    setupPasswordStrength() {
        const newPassword = document.getElementById('newPassword');
        const confirmPassword = document.getElementById('confirmPassword');
        
        newPassword.addEventListener('input', () => {
            this.updatePasswordStrength(newPassword.value);
            this.validatePasswordMatch();
        });
        
        confirmPassword.addEventListener('input', () => {
            this.validatePasswordMatch();
        });
    }
    
    // =================== CONTROLE DO MODAL ===================
    
    openModal() {
        document.getElementById('recoveryModal').classList.add('active');
        document.body.style.overflow = 'hidden';
        this.resetModal();
    }
    
    closeModal() {
        document.getElementById('recoveryModal').classList.remove('active');
        document.body.style.overflow = '';
        this.stopTimer();
        this.resetModal();
    }
    
    resetModal() {
        this.currentStep = 1;
        this.showStep(1);
        this.clearForms();
        this.stopTimer();
    }
    
    showStep(step) {
        // Ocultar todas as etapas
        document.querySelectorAll('.recovery-step').forEach(el => {
            el.classList.remove('active');
        });
        
        // Mostrar etapa atual
        document.getElementById(`step${step}`).classList.add('active');
        this.currentStep = step;
    }
    
    clearForms() {
        document.querySelectorAll('.recovery-form input').forEach(input => {
            input.value = '';
        });
        this.resetPasswordStrength();
    }
    
    // =================== ETAPA 1: ENVIAR CÓDIGO ===================
    
    async sendRecoveryCode() {
        const form = document.getElementById('recoveryForm');
        const button = form.querySelector('.recovery-button');
        const identifier = document.getElementById('identifier').value.trim();
        
        if (!identifier) {
            this.showError('Digite seu email ou telefone');
            return;
        }
        
        this.setLoading(button, true);
        
        try {
            // MODO DEMO - Simula resposta da API sem banco de dados
            await this.simulateDelay(1500);
            
            // Valida formato básico
            const isEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(identifier);
            const isPhone = /^\(\d{2}\)\s\d{4,5}-\d{4}$/.test(identifier) || /^\d{10,11}$/.test(identifier);
            
            if (!isEmail && !isPhone) {
                this.showError('Digite um email válido ou telefone no formato (11) 99999-9999');
                return;
            }

            // Simula sucesso
            this.identifier = identifier;
            this.recoveryId = 'demo-recovery-' + Date.now();
            
            // Atualizar texto de destino
            document.querySelector('#sentToText strong').textContent = this.maskIdentifier(identifier);
            
            this.showStep(2);
            this.startTimer();
            this.showSuccess('Código enviado com sucesso! (DEMO - Use código: 123456)');
        } catch (error) {
            console.error('Erro:', error);
            this.showError('Erro de conexão. Tente novamente.');
        } finally {
            this.setLoading(button, false);
        }
    }
    
    // =================== ETAPA 2: VALIDAR CÓDIGO ===================
    
    async validateCode() {
        const form = document.getElementById('codeForm');
        const button = form.querySelector('.recovery-button');
        const code = document.getElementById('recoveryCode').value.trim();
        
        if (!code || code.length !== 6) {
            this.showError('Digite o código de 6 dígitos');
            return;
        }
        
        this.setLoading(button, true);
        
        try {
            // MODO DEMO - Simula validação do código
            await this.simulateDelay(1000);
            
            // Aceita código 123456 ou qualquer código de 6 dígitos para demo
            if (code === '123456' || (code.length === 6 && /^\d{6}$/.test(code))) {
                this.stopTimer();
                this.showStep(3);
                this.showSuccess('Código validado com sucesso!');
            } else {
                this.showError('Código inválido. Para DEMO, use: 123456');
            }
        } catch (error) {
            console.error('Erro:', error);
            this.showError('Erro de conexão. Tente novamente.');
        } finally {
            this.setLoading(button, false);
        }
    }
    
    async resendCode() {
        const button = document.querySelector('.resend-button');
        this.setLoading(button, true);
        
        try {
            await this.sendRecoveryCode();
            this.startTimer();
        } finally {
            this.setLoading(button, false);
        }
    }
    
    // =================== ETAPA 3: NOVA SENHA ===================
    
    async resetPassword() {
        const form = document.getElementById('newPasswordForm');
        const button = form.querySelector('.recovery-button');
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const code = document.getElementById('recoveryCode').value;
        
        if (!this.validateNewPassword(newPassword, confirmPassword)) {
            return;
        }
        
        this.setLoading(button, true);
        
        try {
            // MODO DEMO - Simula alteração de senha
            await this.simulateDelay(1500);
            
            // Em modo demo, sempre simula sucesso
            this.showStep(4);
            this.showSuccess('Senha alterada com sucesso! (DEMO)');
        } catch (error) {
            console.error('Erro:', error);
            this.showError('Erro de conexão. Tente novamente.');
        } finally {
            this.setLoading(button, false);
        }
    }
    
    validateNewPassword(newPassword, confirmPassword) {
        if (!newPassword) {
            this.showError('Digite a nova senha');
            return false;
        }
        
        if (newPassword.length < 6) {
            this.showError('A senha deve ter pelo menos 6 caracteres');
            return false;
        }
        
        if (newPassword !== confirmPassword) {
            this.showError('As senhas não coincidem');
            return false;
        }
        
        return true;
    }
    
    // =================== TIMER ===================
    
    startTimer() {
        this.timeLeft = 900; // 15 minutos
        this.updateTimerDisplay();
        
        this.timer = setInterval(() => {
            this.timeLeft--;
            this.updateTimerDisplay();
            
            if (this.timeLeft <= 0) {
                this.stopTimer();
                this.showError('Código expirado. Solicite um novo código.');
            }
        }, 1000);
    }
    
    stopTimer() {
        if (this.timer) {
            clearInterval(this.timer);
            this.timer = null;
        }
    }
    
    updateTimerDisplay() {
        const minutes = Math.floor(this.timeLeft / 60);
        const seconds = this.timeLeft % 60;
        const display = `${minutes.toString().padStart(2, '0')}:${seconds.toString().padStart(2, '0')}`;
        
        const timerElement = document.getElementById('timer');
        if (timerElement) {
            timerElement.textContent = display;
            
            // Mudar cor quando restam menos de 5 minutos
            if (this.timeLeft <= 300) {
                timerElement.style.color = '#e74c3c';
            } else {
                timerElement.style.color = '#00bf74';
            }
        }
    }
    
    // =================== FORÇA DA SENHA ===================
    
    updatePasswordStrength(password) {
        const strengthBar = document.querySelector('.strength-fill');
        const strengthText = document.getElementById('strengthText');
        
        if (!password) {
            strengthBar.className = 'strength-fill';
            strengthText.textContent = 'Digite uma senha';
            return;
        }
        
        const strength = this.calculatePasswordStrength(password);
        
        strengthBar.className = `strength-fill ${strength.class}`;
        strengthText.textContent = strength.text;
    }
    
    calculatePasswordStrength(password) {
        let score = 0;
        
        if (password.length >= 8) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^a-zA-Z0-9]/.test(password)) score++;
        
        if (score <= 1) return { class: 'weak', text: 'Muito fraca' };
        if (score <= 2) return { class: 'fair', text: 'Fraca' };
        if (score <= 3) return { class: 'good', text: 'Boa' };
        if (score <= 4) return { class: 'strong', text: 'Forte' };
        return { class: 'strong', text: 'Muito forte' };
    }
    
    validatePasswordMatch() {
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const confirmInput = document.getElementById('confirmPassword');
        
        if (confirmPassword && newPassword !== confirmPassword) {
            confirmInput.style.borderColor = '#e74c3c';
        } else {
            confirmInput.style.borderColor = '';
        }
    }
    
    resetPasswordStrength() {
        const strengthBar = document.querySelector('.strength-fill');
        const strengthText = document.getElementById('strengthText');
        
        strengthBar.className = 'strength-fill';
        strengthText.textContent = 'Digite uma senha';
    }
    
    // =================== UTILITÁRIOS ===================
    
    setLoading(button, isLoading) {
        const span = button.querySelector('span');
        const spinner = button.querySelector('.fa-spinner');
        
        if (isLoading) {
            button.disabled = true;
            span.style.opacity = '0.7';
            if (spinner) spinner.style.display = 'inline-block';
        } else {
            button.disabled = false;
            span.style.opacity = '1';
            if (spinner) spinner.style.display = 'none';
        }
    }
    
    showError(message) {
        // Usar o mesmo sistema de alert do login
        this.showAlert(message, 'error');
    }
    
    showSuccess(message) {
        this.showAlert(message, 'success');
    }
    
    showAlert(message, type = 'error') {
        // Remover alerta anterior se existir
        const existingAlert = document.querySelector('.alert-recovery');
        if (existingAlert) {
            existingAlert.remove();
        }
        
        // Criar novo alerta
        const alert = document.createElement('div');
        alert.className = `alert-recovery alert-${type}`;
        alert.innerHTML = `
            <i class="fas ${type === 'error' ? 'fa-exclamation-circle' : 'fa-check-circle'}"></i>
            ${message}
        `;
        
        // Adicionar ao modal
        const container = document.querySelector('.recovery-container');
        container.insertBefore(alert, container.firstChild);
        
        // Remover após 5 segundos
        setTimeout(() => {
            if (alert.parentNode) {
                alert.remove();
            }
        }, 5000);
    }
    
    maskIdentifier(identifier) {
        if (identifier.includes('@')) {
            // Email
            const [user, domain] = identifier.split('@');
            const maskedUser = user.charAt(0) + '*'.repeat(user.length - 2) + user.charAt(user.length - 1);
            return `${maskedUser}@${domain}`;
        } else {
            // Telefone
            return identifier.replace(/(\d{2})(\d{4,5})(\d{4})/, '($1) $2-****');
        }
    }
    
    // =================== MÉTODO DE SIMULAÇÃO ===================
    
    simulateDelay(ms) {
        return new Promise(resolve => setTimeout(resolve, ms));
    }
}

// =================== FUNÇÕES GLOBAIS ===================

function openRecoveryModal() {
    if (!window.passwordRecovery) {
        window.passwordRecovery = new PasswordRecovery();
    }
    window.passwordRecovery.openModal();
}

function closeRecoveryModal() {
    if (window.passwordRecovery) {
        window.passwordRecovery.closeModal();
    }
}

function resendCode() {
    if (window.passwordRecovery) {
        window.passwordRecovery.resendCode();
    }
}

function togglePasswordVisibility(inputId) {
    const input = document.getElementById(inputId);
    const button = input.nextElementSibling.nextElementSibling; // Pula o ícone
    const icon = button.querySelector('i');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}

// =================== ESTILOS DOS ALERTAS ===================
const alertStyles = `
    .alert-recovery {
        padding: 12px 16px;
        border-radius: 8px;
        margin-bottom: 20px;
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 14px;
        font-weight: 500;
        animation: slideDown 0.3s ease-out;
    }
    
    .alert-recovery.alert-error {
        background: rgba(231, 76, 60, 0.1);
        border: 1px solid rgba(231, 76, 60, 0.3);
        color: #e74c3c;
    }
    
    .alert-recovery.alert-success {
        background: rgba(0, 191, 116, 0.1);
        border: 1px solid rgba(0, 191, 116, 0.3);
        color: #00bf74;
    }
    
    @keyframes slideDown {
        from {
            opacity: 0;
            transform: translateY(-10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
`;

// Adicionar estilos ao documento
const styleSheet = document.createElement('style');
styleSheet.textContent = alertStyles;
document.head.appendChild(styleSheet);
