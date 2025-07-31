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
            
            <div class="remember-me">
                <input type="checkbox" id="remember" name="remember">
                <label for="remember">Lembrar-me</label>
            </div>
            
            <button type="submit" class="login-button">
                <span>Entrar</span>
            </button>
        </form>
        
        <div class="login-footer">
            <p>&copy; 2024 KW24 - Sistemas Harmônicos</p>
        </div>
    </div>

    <script src="/Apps/assets/js/login.js"></script>
</body>
</html>
