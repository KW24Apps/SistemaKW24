<?php
/**
 * DEBUG INTERCEPT - CAPTURA EXATA DO PROCESSO DE LOGIN
 * Coloque este código no início do arquivo de login para debuggar
 */

// Inicia buffer de output para capturar tudo
ob_start();

// Função de log específica para login
function loginDebugLog($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s.u');
    $sessionId = session_id() ?: 'NO_SESSION';
    $logMessage = "[$timestamp] [SID:$sessionId] $message";
    
    if ($data !== null) {
        $logMessage .= " | Data: " . (is_array($data) || is_object($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data);
    }
    
    $logMessage .= "\n";
    file_put_contents(__DIR__ . '/login_debug.log', $logMessage, FILE_APPEND | LOCK_EX);
}

// Log de início
loginDebugLog("=== LOGIN ATTEMPT STARTED ===");
loginDebugLog("REQUEST_METHOD", $_SERVER['REQUEST_METHOD']);
loginDebugLog("REQUEST_URI", $_SERVER['REQUEST_URI']);
loginDebugLog("HTTP_REFERER", $_SERVER['HTTP_REFERER'] ?? 'NONE');
loginDebugLog("POST_DATA", $_POST);
loginDebugLog("SESSION_DATA", $_SESSION ?? []);
loginDebugLog("COOKIES", $_COOKIE);

// Se é POST (tentativa de login)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    loginDebugLog("POST LOGIN ATTEMPT");
    loginDebugLog("Username submitted", $_POST['username'] ?? 'NOT_SET');
    loginDebugLog("Password length", isset($_POST['password']) ? strlen($_POST['password']) : 'NOT_SET');
    
    // Verifica se sessão já existe
    if (session_status() === PHP_SESSION_NONE) {
        loginDebugLog("Starting new session");
        session_start();
    } else {
        loginDebugLog("Session already active", session_id());
    }
    
    loginDebugLog("Session after start", $_SESSION);
}

// Log de final
loginDebugLog("=== INITIAL CAPTURE COMPLETE ===");

// Esta função deve ser chamada no final do processo
function loginDebugLogEnd($success = false, $redirectUrl = null) {
    loginDebugLog("=== LOGIN PROCESS ENDED ===");
    loginDebugLog("Login successful", $success ? 'YES' : 'NO');
    loginDebugLog("Redirect URL", $redirectUrl ?: 'NONE');
    loginDebugLog("Final session", $_SESSION ?? []);
    loginDebugLog("Headers sent", headers_sent() ? 'YES' : 'NO');
    loginDebugLog("Output buffer length", ob_get_length());
    loginDebugLog("=== END OF LOGIN DEBUG ===\n");
}

// Registra função para capturar o final
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error) {
        loginDebugLog("PHP ERROR", $error);
    }
    loginDebugLog("SHUTDOWN FUNCTION CALLED");
});

?>

<!-- 
INSTRUÇÕES DE USO:

1. Copie este código e coloque no INÍCIO do seu arquivo de login (login.php ou similar)

2. No final do processo de login (onde faz redirect ou exibe erro), adicione:
   loginDebugLogEnd(true, 'dashboard.php'); // para sucesso
   ou
   loginDebugLogEnd(false); // para erro

3. Faça algumas tentativas de login e me envie o conteúdo do arquivo login_debug.log

EXEMPLO DE INTEGRAÇÃO:

<?php
// COLE O CÓDIGO DE DEBUG AQUI NO INÍCIO

// ... seu código de login existente ...

if ($loginSuccess) {
    loginDebugLogEnd(true, 'dashboard.php');
    header('Location: dashboard.php');
    exit;
} else {
    loginDebugLogEnd(false);
    $error = "Credenciais inválidas";
}
?>
-->
