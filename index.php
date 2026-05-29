<?php 
/**
 * INDEX - Molde principal do sistema
 * Este arquivo é o template base que carrega as páginas específicas
 */

session_start();

// Integrar sistema de recuperação de senha
// require_once __DIR__ . '/password_recovery_integration.php'; // COMENTADO - Controller não existe

require_once __DIR__ . '/services/AuthenticationService.php';

$authService = new AuthenticationService();

if (!$authService->validateSession()) {
    header('Location: public/login.php');
    exit;
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
    if (!isset($user_data['perfil']) || $user_data['perfil'] !== 'admin_interno') {
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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/layout.css">
    <link rel="stylesheet" href="/assets/css/components/sidebar.css">
    <link rel="stylesheet" href="/assets/css/components/topbar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <canvas id="kw24-bg"></canvas>
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

    <script src="/assets/js/components/sidebar.js"></script>
    <script src="/assets/js/components/topbar.js"></script>
    <script src="/assets/js/bg-dashboard.js"></script>
</body>
</html>
