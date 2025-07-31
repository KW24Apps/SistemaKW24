<?php
/**
 * LOGIN - KW24 APPS V2
 * Sistema de autenticação com migração automática de senhas
 */

session_start();

require_once __DIR__ . '/../services/AuthenticationService.php';

$authService = new AuthenticationService();
$loginError = false;
$usuarioDigitado = '';
$errorMessage = '';

// Verifica se há erro na sessão
if (isset($_SESSION['login_erro'])) {
    $loginError = true;
    $errorMessage = $_SESSION['login_erro_msg'] ?? 'Usuário ou senha inválidos!';
    unset($_SESSION['login_erro'], $_SESSION['login_erro_msg']);
}

// Recupera usuário digitado em caso de erro
if (isset($_SESSION['usuario_digitado'])) {
    $usuarioDigitado = $_SESSION['usuario_digitado'];
    unset($_SESSION['usuario_digitado']);
}

// Verifica se já está logado
if ($authService->validateSession()) {
    header('Location: ../index.php');
    exit;
}

// Processa login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $usuario = trim($_POST['usuario'] ?? '');
    $senha = $_POST['senha'] ?? '';
    
    if (!empty($usuario) && !empty($senha)) {
        // Tenta autenticar
        $authResult = $authService->authenticate($usuario, $senha);
        
        if ($authResult['success']) {
            // Cria sessão
            if ($authService->createSession($authResult['user'])) {
                header('Location: ../index.php');
                exit;
            } else {
                $_SESSION['login_erro'] = true;
                $_SESSION['login_erro_msg'] = 'Erro ao criar sessão';
                $_SESSION['usuario_digitado'] = $usuario;
            }
        } else {
            // Falha na autenticação
            $_SESSION['login_erro'] = true;
            $_SESSION['login_erro_msg'] = $authResult['message'];
            $_SESSION['usuario_digitado'] = $usuario;
        }
        
        // Redireciona para evitar resubmissão
        header('Location: login.php');
        exit;
    } else {
        $loginError = true;
        $errorMessage = 'Preencha todos os campos';
    }
}
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KW24 Apps</title>
    <link rel="stylesheet" href="/Apps/assets/css/login.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <?php if ($loginError): ?>
        <div class="alert-top">
            <i class="fas fa-exclamation-circle"></i>
            <?= htmlspecialchars($errorMessage) ?>
        </div>
    <?php endif; ?>
    
    <div class="login-container">
        <div class="login-header">
            <img src="/Apps/assets/img/03_KW24_BRANCO1.png" alt="KW24 - Sistemas Harmônicos">
        </div>
        
        <form method="POST" action="" class="login-form">
            <div class="input-group">
                <input 
                    type="text" 
                    id="usuario" 
                    name="usuario" 
                    placeholder="Usuário"
                    value="<?= htmlspecialchars($usuarioDigitado) ?>"
                    required 
                    autocomplete="username"
                >
                <i class="fas fa-user input-icon"></i>
            </div>
            
            <div class="input-group">
                <input 
                    type="password" 
                    id="senha" 
                    name="senha" 
                    placeholder="Senha"
                    required 
                    autocomplete="current-password"
                >
                <i class="fas fa-lock input-icon"></i>
                <button type="button" class="toggle-password" aria-label="Mostrar/Ocultar senha">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            
            <button type="submit" class="login-button">
                <span>Entrar</span>
            </button>
            
            <button type="button" class="forgot-password-button" onclick="showRecoveryStep1()">
                <i class="fas fa-key"></i>
                <span>Esqueci minha senha</span>
            </button>
        </form>
        
        <div class="login-footer">
            <p>&copy; 2024 KW24 - Sistemas Harmônicos</p>
        </div>
    </div>

    <script src="/Apps/assets/js/login.js"></script>
    
    <script>
        // =================== SISTEMA DE RECUPERAÇÃO DE SENHA =================== //
        // Troca conteúdo do mesmo container - Sistema Global
        
        // Aguarda o DOM carregar completamente
        document.addEventListener('DOMContentLoaded', function() {
            console.log('[Recovery] Sistema de recuperação de senha carregado');
        });
        
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
            
            const form = document.querySelector('.login-form');
            form.innerHTML = `
                <h3 style="text-align: center; color: #033140; margin-bottom: 20px;">Recuperar Senha</h3>
                <p style="text-align: center; color: #6B7280; margin-bottom: 25px; font-size: 14px;">Digite seu email ou telefone para receber o código</p>
                
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
            `;
            
            console.log('[Recovery] Etapa 1: Solicitar email');
        }
        
        // ETAPA 2: Digitar código
        window.showRecoveryStep2 = function(email) {
            const form = document.querySelector('.login-form');
            const maskedEmail = maskEmail(email);
            
            form.innerHTML = \`
                <h3 style="text-align: center; color: #033140; margin-bottom: 15px;">Digite o Código</h3>
                <p style="text-align: center; color: #6B7280; margin-bottom: 25px; font-size: 14px;">
                    Código enviado para <strong>\${maskedEmail}</strong>
                </p>
                
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
            \`;
            
            console.log('[Recovery] Etapa 2: Digitar código');
        }
        
        // ETAPA 3: Nova senha
        window.showRecoveryStep3 = function() {
            const form = document.querySelector('.login-form');
            
            form.innerHTML = \`
                <h3 style="text-align: center; color: #033140; margin-bottom: 15px;">Nova Senha</h3>
                <p style="text-align: center; color: #6B7280; margin-bottom: 25px; font-size: 14px;">Digite sua nova senha</p>
                
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
            \`;
            
            console.log('[Recovery] Etapa 3: Nova senha');
        }
        
        // ETAPA 4: Sucesso
        window.showRecoveryStep4 = function() {
            const form = document.querySelector('.login-form');
            
            form.innerHTML = \`
                <div style="text-align: center; padding: 20px 0;">
                    <i class="fas fa-check-circle" style="font-size: 48px; color: #00bf74; margin-bottom: 20px;"></i>
                    <h3 style="color: #033140; margin-bottom: 15px;">Senha Alterada!</h3>
                    <p style="color: #6B7280; margin-bottom: 30px; font-size: 14px;">
                        Sua senha foi alterada com sucesso.<br>Você já pode fazer login.
                    </p>
                    
                    <button type="button" class="login-button" onclick="backToLogin()">
                        <span>Fazer Login</span>
                    </button>
                </div>
            \`;
            
            console.log('[Recovery] Etapa 4: Sucesso');
        }
        
        // Voltar ao login original
        window.backToLogin = function() {
            if (originalLoginForm) {
                document.querySelector('.login-form').innerHTML = originalLoginForm;
            }
            console.log('[Recovery] Voltou ao login');
        }
        
        // Funções de submit (placeholder por enquanto)
        window.submitRecoveryStep1 = function() {
            const identifier = document.getElementById('recoveryIdentifier').value.trim();
            if (identifier) {
                userEmail = identifier;
                console.log('[Recovery] Email/telefone:', identifier);
                // TODO: Chamar API
                showRecoveryStep2(identifier);
            }
        }
        
        window.submitRecoveryStep2 = function() {
            const code = document.getElementById('recoveryCode').value.trim();
            if (code) {
                console.log('[Recovery] Código:', code);
                // TODO: Validar código
                showRecoveryStep3();
            }
        }
        
        window.submitRecoveryStep3 = function() {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            
            if (newPassword === confirmPassword && newPassword.length >= 6) {
                console.log('[Recovery] Nova senha definida');
                // TODO: Salvar nova senha
                showRecoveryStep4();
            } else {
                alert('Senhas não conferem ou são muito curtas');
            }
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
    </script>
</body>
</html>
