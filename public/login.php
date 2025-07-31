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
            
            <button type="button" class="forgot-password-button" onclick="openRecoveryModal()">
                <i class="fas fa-key"></i>
                Esqueci minha senha
            </button>
        </form>
        
        <div class="login-footer">
            <p>&copy; 2024 KW24 - Sistemas Harmônicos</p>
        </div>
    </div>

    <!-- Modal de Recuperação de Senha -->
    <div id="recoveryModal" class="recovery-modal">
        <div class="recovery-container">
            <!-- Header fixo com logo -->
            <div class="recovery-header">
                <img src="/Apps/assets/img/03_KW24_BRANCO1.png" alt="KW24 - Sistemas Harmônicos">
                <button type="button" class="close-modal" onclick="closeRecoveryModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            
            <!-- Etapa 1: Informar Email/Telefone -->
            <div id="step1" class="recovery-step active">
                <h2>Recuperar Senha</h2>
                <p>Digite seu email ou telefone para receber o código de recuperação</p>
                
                <form id="recoveryForm" class="recovery-form">
                    <div class="input-group">
                        <input 
                            type="text" 
                            id="identifier" 
                            name="identifier" 
                            placeholder="Email ou telefone"
                            required 
                        >
                        <i class="fas fa-envelope input-icon"></i>
                    </div>
                    
                    <button type="submit" class="recovery-button">
                        <span>Enviar Código</span>
                        <i class="fas fa-spinner fa-spin" style="display: none;"></i>
                    </button>
                </form>
            </div>
            
            <!-- Etapa 2: Informar Código -->
            <div id="step2" class="recovery-step">
                <h2>Digite o Código</h2>
                <p id="sentToText">Código enviado para <strong></strong></p>
                
                <form id="codeForm" class="recovery-form">
                    <div class="input-group">
                        <input 
                            type="text" 
                            id="recoveryCode" 
                            name="recoveryCode" 
                            placeholder="000000"
                            maxlength="6"
                            pattern="[0-9]{6}"
                            required 
                        >
                        <i class="fas fa-key input-icon"></i>
                    </div>
                    
                    <div class="code-timer">
                        <p>Código expira em: <span id="timer">15:00</span></p>
                    </div>
                    
                    <button type="submit" class="recovery-button">
                        <span>Validar Código</span>
                        <i class="fas fa-spinner fa-spin" style="display: none;"></i>
                    </button>
                    
                    <button type="button" class="resend-button" onclick="resendCode()">
                        <i class="fas fa-redo"></i>
                        Reenviar código
                    </button>
                </form>
            </div>
            
            <!-- Etapa 3: Nova Senha -->
            <div id="step3" class="recovery-step">
                <h2>Nova Senha</h2>
                <p>Digite sua nova senha duas vezes para confirmar</p>
                
                <form id="newPasswordForm" class="recovery-form">
                    <div class="input-group">
                        <input 
                            type="password" 
                            id="newPassword" 
                            name="newPassword" 
                            placeholder="Nova senha"
                            required 
                            minlength="6"
                        >
                        <i class="fas fa-lock input-icon"></i>
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility('newPassword')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <div class="input-group">
                        <input 
                            type="password" 
                            id="confirmPassword" 
                            name="confirmPassword" 
                            placeholder="Confirmar senha"
                            required 
                            minlength="6"
                        >
                        <i class="fas fa-lock input-icon"></i>
                        <button type="button" class="toggle-password" onclick="togglePasswordVisibility('confirmPassword')">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <div class="password-strength">
                        <div id="passwordStrength" class="strength-bar">
                            <div class="strength-fill"></div>
                        </div>
                        <p id="strengthText">Digite uma senha</p>
                    </div>
                    
                    <button type="submit" class="recovery-button">
                        <span>Salvar Nova Senha</span>
                        <i class="fas fa-spinner fa-spin" style="display: none;"></i>
                    </button>
                </form>
            </div>
            
            <!-- Etapa 4: Sucesso -->
            <div id="step4" class="recovery-step">
                <div class="success-message">
                    <i class="fas fa-check-circle"></i>
                    <h2>Senha Alterada!</h2>
                    <p>Sua senha foi alterada com sucesso. Você já pode fazer login com a nova senha.</p>
                    
                    <button type="button" class="recovery-button" onclick="closeRecoveryModal()">
                        <span>Fazer Login</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="/Apps/assets/js/login.js"></script>
    <script src="/Apps/assets/js/password-recovery.js"></script>
</body>
</html>
