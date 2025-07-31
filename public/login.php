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
        
        <!-- Sistema de Recuperação de Senha -->
        <div id="recovery-step-1" class="recovery-step" style="display: none;">
            <h3>Recuperar Senha</h3>
            <p>Digite seu usuário para receber o código de verificação</p>
            <div class="input-group">
                <input type="text" id="recovery-usuario" placeholder="Usuário" required>
                <i class="fas fa-user input-icon"></i>
            </div>
            <button type="button" class="login-button" onclick="sendRecoveryCode()">
                <span>Enviar Código</span>
            </button>
            <button type="button" class="forgot-password-button" onclick="backToLogin()">
                <span>Voltar ao Login</span>
            </button>
        </div>
        
        <div id="recovery-step-2" class="recovery-step" style="display: none;">
            <h3>Verificar Código</h3>
            <p>Digite o código de 6 dígitos enviado para seu email</p>
            <div class="input-group">
                <input type="text" id="recovery-code" placeholder="Código de 6 dígitos" maxlength="6" required>
                <i class="fas fa-key input-icon"></i>
            </div>
            <button type="button" class="login-button" onclick="verifyRecoveryCode()">
                <span>Verificar</span>
            </button>
            <button type="button" class="forgot-password-button" onclick="backToStep1()">
                <span>Voltar</span>
            </button>
        </div>
        
        <div id="recovery-step-3" class="recovery-step" style="display: none;">
            <h3>Nova Senha</h3>
            <p>Digite sua nova senha</p>
            <div class="input-group">
                <input type="password" id="recovery-new-password" placeholder="Nova senha" required>
                <i class="fas fa-lock input-icon"></i>
                <button type="button" class="toggle-password" aria-label="Mostrar/Ocultar senha">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <div class="input-group">
                <input type="password" id="recovery-confirm-password" placeholder="Confirmar nova senha" required>
                <i class="fas fa-lock input-icon"></i>
                <button type="button" class="toggle-password" aria-label="Mostrar/Ocultar senha">
                    <i class="fas fa-eye"></i>
                </button>
            </div>
            <button type="button" class="btn btn-primary" onclick="resetPassword()">
                <span>Alterar Senha</span>
            </button>
            <button type="button" class="forgot-password-button" onclick="backToLogin()">
                <span>Cancelar</span>
            </button>
        </div>
        
        <div id="recovery-step-4" class="recovery-step" style="display: none;">
            <h3>Senha Alterada!</h3>
            <p>Sua senha foi alterada com sucesso. Faça login com a nova senha.</p>
            <button type="button" class="btn btn-primary" onclick="backToLogin()">
                <span>Fazer Login</span>
            </button>
        </div>
        
        <!-- Loader overlay -->
        <div id="recovery-loader" style="display: none;">
            <div class="loader-content">
                <div class="spinner"></div>
                <div class="loader-text">Processando...</div>
            </div>
        </div>
        
        <div class="login-footer">
            <p>&copy; 2024 KW24 - Sistemas Harmônicos</p>
        </div>
    </div>

    <script src="/Apps/assets/js/login.js"></script>
</body>
</html>
