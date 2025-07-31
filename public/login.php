<?php
/**
 * LOGIN V2 - KW24 APPS
 * Sistema de autenticação com banco de dados
 * Implementando melhorias dos módulos 4 e 6
 */

// ============ DEBUG LOGIN - INÍCIO ============
function loginDebugLog($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s.u');
    $sessionId = session_id() ?: 'NO_SESSION';
    $logMessage = "[$timestamp] [SID:$sessionId] $message";
    
    if ($data !== null) {
        $logMessage .= " | Data: " . (is_array($data) || is_object($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data);
    }
    
    $logMessage .= "\n";
    file_put_contents(__DIR__ . '/../login_debug.log', $logMessage, FILE_APPEND | LOCK_EX);
}

loginDebugLog("=== LOGIN PAGE ACCESS ===");
loginDebugLog("REQUEST_METHOD", $_SERVER['REQUEST_METHOD']);
loginDebugLog("REQUEST_URI", $_SERVER['REQUEST_URI']);
loginDebugLog("HTTP_REFERER", $_SERVER['HTTP_REFERER'] ?? 'NONE');
loginDebugLog("Query String", $_SERVER['QUERY_STRING'] ?? 'NONE');
// ============ DEBUG LOGIN - FIM ============

session_start();

loginDebugLog("Session started", session_id());
loginDebugLog("Initial session data", $_SESSION ?? []);

// Importa serviços necessários
require_once __DIR__ . '/../services/AuthenticationService.php';

$authService = new AuthenticationService();
$loginError = false;
$usuarioDigitado = '';
$errorMessage = '';

// Verifica se há erro na sessão
if (isset($_SESSION['login_erro'])) {
    loginDebugLog("Login error found in session");
    $loginError = true;
    $errorMessage = $_SESSION['login_erro_msg'] ?? 'Usuário ou senha inválidos!';
    loginDebugLog("Error message", $errorMessage);
    unset($_SESSION['login_erro'], $_SESSION['login_erro_msg']);
}

// Recupera usuário digitado em caso de erro
if (isset($_SESSION['usuario_digitado'])) {
    $usuarioDigitado = $_SESSION['usuario_digitado'];
    loginDebugLog("Recovered username from session", $usuarioDigitado);
    unset($_SESSION['usuario_digitado']);
}

// Se já estiver logado, redireciona para dashboard
loginDebugLog("Checking existing session validation");
$sessionValid = $authService->validateSession();
loginDebugLog("Session validation result", $sessionValid ? 'VALID' : 'INVALID');

if ($sessionValid) {
    loginDebugLog("User already logged in - redirecting to dashboard");
    header('Location: ../index.php');
    exit;
}

// Processamento do login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    loginDebugLog("=== POST LOGIN PROCESSING ===");
    loginDebugLog("POST data received", $_POST);
    
    $username = trim($_POST['usuario'] ?? '');
    $password = $_POST['senha'] ?? '';
    
    loginDebugLog("Processed username", $username);
    loginDebugLog("Password length", strlen($password));
    
    if (!empty($username) && !empty($password)) {
        loginDebugLog("Credentials provided - attempting authentication");
        
        // Tenta autenticar com o banco
        $authResult = $authService->authenticate($username, $password);
        
        loginDebugLog("Authentication result", $authResult);
        
        if ($authResult['success']) {
            loginDebugLog("Authentication SUCCESS - creating session");
            
            // Cria sessão
            $sessionCreated = $authService->createSession($authResult['user']);
            loginDebugLog("Session creation result", $sessionCreated ? 'SUCCESS' : 'FAILED');
            
            if ($sessionCreated) {
                loginDebugLog("Session created successfully - redirecting to dashboard");
                // Redireciona para dashboard
                header('Location: ../index.php');
                exit;
            } else {
                loginDebugLog("Session creation FAILED");
                $_SESSION['login_erro'] = true;
                $_SESSION['login_erro_msg'] = 'Erro ao criar sessão';
                $_SESSION['usuario_digitado'] = $username;
            }
        } else {
            loginDebugLog("Authentication FAILED", $authResult['message']);
            // Login falhado
            $_SESSION['login_erro'] = true;
            $_SESSION['login_erro_msg'] = $authResult['message'];
            $_SESSION['usuario_digitado'] = $username;
        }
        
        loginDebugLog("Redirecting to login.php to show result");
        // Redireciona para evitar resubmissão
        header('Location: login.php');
        exit;
    } else {
        loginDebugLog("Missing credentials - username or password empty");
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
    <link rel="stylesheet" href="/Apps/assets/css/login.css">
    
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css" crossorigin="anonymous" referrerpolicy="no-referrer">
    
    <!-- Preload de recursos -->
    <link rel="preload" as="image" href="/Apps/assets/img/Fundo_Login.webp">
    <link rel="preload" as="image" href="/Apps/assets/img/03_KW24_BRANCO1.png">
    
    <!-- SEO e Meta -->
    <meta name="description" content="Sistema de autenticação KW24 Apps - Sistemas Harmônicos">
    <meta name="robots" content="noindex, nofollow">
    <meta name="theme-color" content="#086B8D">
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
            <img src="/Apps/assets/img/03_KW24_BRANCO1.png" 
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
    <script src="/Apps/assets/js/login.js"></script>
    
    <?php loginDebugLog("=== LOGIN PAGE RENDERED ==="); ?>
    
</body>
</html>
