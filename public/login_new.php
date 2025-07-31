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
    header('Location: dashboard.php');
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
                header('Location: dashboard.php');
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
    <link rel="stylesheet" href="../assets/css/login.css">
</head>
<body>
    <div class="login-container">
        <div class="login-box">
            <div class="logo">
                <img src="../assets/img/03_KW24_BRANCO1.png" alt="KW24" class="logo-img">
            </div>
            
            <h2>Login</h2>
            
            <?php if ($loginError): ?>
                <div class="error-message">
                    <?= htmlspecialchars($errorMessage) ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <div class="form-group">
                    <label for="usuario">Usuário:</label>
                    <input 
                        type="text" 
                        id="usuario" 
                        name="usuario" 
                        value="<?= htmlspecialchars($usuarioDigitado) ?>"
                        required 
                        autocomplete="username"
                    >
                </div>
                
                <div class="form-group">
                    <label for="senha">Senha:</label>
                    <input 
                        type="password" 
                        id="senha" 
                        name="senha" 
                        required 
                        autocomplete="current-password"
                    >
                </div>
                
                <button type="submit" class="login-btn">Entrar</button>
            </form>
        </div>
    </div>
</body>
</html>
