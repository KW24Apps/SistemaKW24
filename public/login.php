<?php
/**
 * LOGIN V2 - KW24 APPS
 * Sistema de autenticação com banco de dados
 * Implementando melhorias dos módulos 4 e 6
 */

session_start();

// Importa serviços necessários
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

// Se já estiver logado, redireciona para dashboard
if ($authService->validateSession()) {
    header('Location: ../index.php');
    exit;
}

// Processamento do login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['usuario'] ?? '');
    $password = $_POST['senha'] ?? '';
    
    if (!empty($username) && !empty($password)) {
        // Tenta autenticar com o banco
        $authResult = $authService->authenticate($username, $password);
        
        if ($authResult['success']) {
            // Cria sessão
            if ($authService->createSession($authResult['user'])) {
                // Redireciona para dashboard
                header('Location: ../index.php');
                exit;
            } else {
                $_SESSION['login_erro'] = true;
                $_SESSION['login_erro_msg'] = 'Erro ao criar sessão';
                $_SESSION['usuario_digitado'] = $username;
            }
        } else {
            // Login falhado
            $_SESSION['login_erro'] = true;
            $_SESSION['login_erro_msg'] = $authResult['message'];
            $_SESSION['usuario_digitado'] = $username;
        }
        
        // Redireciona para evitar resubmissão
        header('Location: login.php');
        exit;
    } else {
        $_SESSION['login_erro'] = true;
        $_SESSION['login_erro_msg'] = 'Por favor, preencha todos os campos';
        $_SESSION['usuario_digitado'] = $username;
        header('Location: login.php');
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - KW24 Apps</title>
    
    <!-- CSS V2 - Ordem de carregamento -->
    <link rel="stylesheet" href="../assets/css/login.css">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    
    <!-- Preload de recursos -->
    <link rel="preload" as="image" href="../assets/img/Fundo_Login.webp">
    <link rel="preload" as="image" href="../assets/img/03_KW24_BRANCO1.png">
    
    <!-- SEO e Meta -->
    <meta name="description" content="Sistema de autenticação KW24 Apps - Sistemas Harmônicos">
    <meta name="robots" content="noindex, nofollow">
</head>
<body>
    
    <!-- Alert de erro -->
    <?php if ($loginError): ?>
        <div class="alert-top" id="loginErrorAlert">
            <i class="fas fa-exclamation-triangle" aria-hidden="true"></i>
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
    <?php endif; ?>

    <!-- Container principal do login -->
    <div class="login-container">
        
        <!-- Header com logo -->
        <div class="login-header">
            <img src="../assets/img/03_KW24_BRANCO1.png" 
                 alt="Logo KW24" 
                 title="KW24 Apps">
        </div>
        
        <!-- Formulário de login -->
        <form method="post" action="login.php" autocomplete="off" class="login-form">
            
            <!-- Campo usuário -->
            <div class="input-group">
                <span class="input-icon">
                    <i class="fas fa-user" aria-hidden="true"></i>
                </span>
                <input type="text" 
                       name="usuario" 
                       id="usuario" 
                       placeholder="Usuário" 
                       required 
                       autocomplete="username"
                       value="<?php echo htmlspecialchars($usuarioDigitado); ?>"
                       aria-label="Nome de usuário">
            </div>
            
            <!-- Campo senha -->
            <div class="input-group">
                <span class="input-icon">
                    <i class="fas fa-lock" aria-hidden="true"></i>
                </span>
                <input type="password" 
                       name="senha" 
                       id="senha" 
                       placeholder="Senha" 
                       required
                       autocomplete="current-password"
                       aria-label="Senha">
                <button type="button" 
                        id="toggleSenha" 
                        class="toggle-password"
                        aria-label="Mostrar/ocultar senha">
                    <i class="fas fa-eye" aria-hidden="true"></i>
                </button>
            </div>
            
            <!-- Checkbox lembrar-me -->
            <div class="remember-me">
                <input type="checkbox" 
                       id="lembrar" 
                       name="lembrar"
                       aria-describedby="lembrar-desc">
                <label for="lembrar">Lembrar-me</label>
                <span id="lembrar-desc" class="sr-only">Manter sessão ativa por mais tempo</span>
            </div>
            
            <!-- Botão de submit -->
            <button type="submit" class="login-button">
                <span>Entrar</span>
                <i class="fas fa-arrow-right" aria-hidden="true"></i>
            </button>
            
        </form>
        
        <!-- Footer do login -->
        <div class="login-footer">
            <p>KW24 Apps - Sistemas Harmônicos</p>
        </div>
        
    </div>

    <!-- JavaScript -->
    <script src="../assets/js/login.js"></script>
    
</body>
</html>
