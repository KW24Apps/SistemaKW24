<?php 
/**
 * INDEX - Molde principal do sistema
 * Este arquivo é o template base que carrega as páginas específicas
 */

// LOGS E DEBUG DETALHADOS
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// Log de início do sistema
error_log("[INDEX] Iniciando sistema - " . date('Y-m-d H:i:s'));

session_start();
error_log("[INDEX] Sessão iniciada - " . date('Y-m-d H:i:s'));

// Integrar sistema de recuperação de senha
// require_once __DIR__ . '/password_recovery_integration.php'; // COMENTADO - Controller não existe

try {
    require_once __DIR__ . '/services/AuthenticationService.php';
    error_log("[INDEX] AuthenticationService carregado com sucesso");
} catch (Exception $e) {
    error_log("[INDEX] ERRO ao carregar AuthenticationService: " . $e->getMessage());
    die("Erro ao carregar sistema de autenticação: " . $e->getMessage());
}

try {
    error_log("[INDEX] Criando instância do AuthenticationService");
    $authService = new AuthenticationService();
    error_log("[INDEX] AuthenticationService instanciado com sucesso");
} catch (Exception $e) {
    error_log("[INDEX] ERRO ao instanciar AuthenticationService: " . $e->getMessage());
    die("Erro ao inicializar sistema de autenticação: " . $e->getMessage());
}

try {
    error_log("[INDEX] Validando sessão");
    $sessionValid = $authService->validateSession();
    error_log("[INDEX] Resultado da validação de sessão: " . ($sessionValid ? 'VÁLIDA' : 'INVÁLIDA'));
    
    if (!$sessionValid) {
        error_log("[INDEX] Redirecionando para login - sessão inválida");
        header('Location: public/login.php');
        exit;
    }
} catch (Exception $e) {
    error_log("[INDEX] ERRO ao validar sessão: " . $e->getMessage());
    die("Erro ao validar sessão: " . $e->getMessage());
}

$user_data = $authService->getCurrentUser();

if (!$user_data) {
    header('Location: public/login.php?error=session');
    exit;
}

// Determina qual página carregar
$page = $_GET['page'] ?? 'dashboard';
$allowed_pages = ['dashboard', 'cadastro', 'relatorio', 'logs', 'configuracoes'];

// PROTEÇÃO: Verifica se página configurações é acessível apenas para administradores
if ($page === 'configuracoes') {
    if (!isset($user_data['perfil']) || $user_data['perfil'] !== 'Administrador') {
        // Redireciona para dashboard se tentar acessar via URL sem ser admin
        header('Location: ?page=dashboard&error=access_denied');
        exit;
    }
}

if (!in_array($page, $allowed_pages)) {
    $page = 'dashboard';
}

$content_file = "public/{$page}.php";

// Flag de segurança para páginas incluídas
define('SYSTEM_ACCESS', true);
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KW24 - Sistemas Harmônicos</title>
    <link rel="stylesheet" href="/Apps/assets/css/layout.css">
    <link rel="stylesheet" href="/Apps/assets/css/components/sidebar.css">
    <link rel="stylesheet" href="/Apps/assets/css/components/topbar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <div class="app-layout">
        
        <div class="sidebar-area">
            <?php include 'views/layouts/sidebar.php'; ?>
        </div>
        
        <div class="main-area">
            
            <div class="topbar-area">
                <?php include 'views/components/topbar.php'; ?>
            </div>
            
            <main class="content-area">
                <?php 
                // Carrega o conteúdo específico da página
                if (file_exists($content_file)) {
                    include $content_file;
                } else {
                    // Fallback para dashboard se página não existir
                    include 'public/dashboard.php';
                }
                ?>
            </main>
            
        </div>
        
    </div>

    <script src="/Apps/assets/js/components/sidebar.js"></script>
    <script src="/Apps/assets/js/components/topbar.js"></script>
</body>
</html>
