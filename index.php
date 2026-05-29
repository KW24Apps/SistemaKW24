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

// Requisição AJAX — retorna só o conteúdo da página
if (isset($_GET['ajax'])) {
    $page          = $_GET['page'] ?? 'dashboard';
    $allowed_pages = ['dashboard', 'cadastro', 'usuarios', 'aplicacoes', 'relatorio', 'logs', 'configuracoes'];
    if (!in_array($page, $allowed_pages)) $page = 'dashboard';
    define('SYSTEM_ACCESS', true);
    $content_file = __DIR__ . "/public/{$page}.php";
    if (file_exists($content_file)) include $content_file;
    exit;
}

// Determina qual página carregar
$page = $_GET['page'] ?? 'dashboard';
$allowed_pages = ['dashboard', 'cadastro', 'usuarios', 'aplicacoes', 'relatorio', 'logs', 'configuracoes'];

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

$content_file = __DIR__ . "/public/{$page}.php";

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
    <link rel="stylesheet" href="/assets/css/clientes.css">
    <link rel="stylesheet" href="/assets/css/painel-cliente.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <canvas id="kw24-bg"></canvas>

    <!-- Modal de ativação de app com webhook -->
    <div id="kw-ativar-overlay" style="display:none;position:fixed;inset:0;background:rgba(6,25,32,.6);backdrop-filter:blur(4px);z-index:9999;align-items:center;justify-content:center">
        <div style="background:#fff;border-radius:16px;padding:2rem;width:420px;max-width:92vw;box-shadow:0 24px 60px rgba(0,0,0,.25);animation:kwPop .18s ease">
            <div style="width:52px;height:52px;border-radius:50%;background:#d1fae5;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.4rem;color:#065f46">
                <i class="fas fa-check"></i>
            </div>
            <h3 id="kw-ativar-title" style="text-align:center;font-family:'Rubik',sans-serif;font-size:1.05rem;font-weight:700;color:#1a202c;margin:0 0 .4rem"></h3>
            <p id="kw-ativar-msg" style="text-align:center;font-size:.875rem;color:#718096;margin:0 0 1.25rem;line-height:1.5"></p>
            <div style="margin-bottom:1.25rem">
                <label style="display:block;font-size:.75rem;font-weight:700;color:#4a5568;text-transform:uppercase;letter-spacing:.05em;margin-bottom:.4rem">Webhook Bitrix24 *</label>
                <input id="kw-ativar-webhook" type="url" placeholder="https://suaempresa.bitrix24.com.br/rest/..."
                    style="width:100%;border:1px solid #e2e8f0;border-radius:8px;padding:.6rem .75rem;font-size:.875rem;color:#2d3748;outline:none;font-family:inherit;box-sizing:border-box;transition:border-color .15s"
                    onfocus="this.style.borderColor='#0DC2FF'" onblur="this.style.borderColor='#e2e8f0'">
                <p id="kw-ativar-erro" style="color:#e53e3e;font-size:.78rem;margin:.4rem 0 0;display:none">Informe o webhook para continuar.</p>
            </div>
            <div style="display:flex;gap:.75rem">
                <button id="kw-ativar-cancel" style="flex:1;padding:.65rem;border:1px solid #e2e8f0;border-radius:8px;background:#fff;color:#718096;font-size:.875rem;cursor:pointer;font-weight:500">Cancelar</button>
                <button id="kw-ativar-ok" style="flex:1;padding:.65rem;border:none;border-radius:8px;background:#0DC2FF;color:#fff;font-size:.875rem;cursor:pointer;font-weight:700">Ativar</button>
            </div>
        </div>
    </div>

    <!-- Modal de confirmação customizado -->
    <div id="kw-confirm-overlay" style="display:none;position:fixed;inset:0;background:rgba(6,25,32,.6);backdrop-filter:blur(4px);z-index:9999;align-items:center;justify-content:center">
        <div id="kw-confirm-box" style="background:#fff;border-radius:16px;padding:2rem;width:360px;max-width:92vw;box-shadow:0 24px 60px rgba(0,0,0,.25);animation:kwPop .18s ease">
            <div id="kw-confirm-icon" style="width:52px;height:52px;border-radius:50%;background:#fee2e2;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem;font-size:1.4rem;color:#c53030">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            <h3 id="kw-confirm-title" style="text-align:center;font-family:'Rubik',sans-serif;font-size:1.05rem;font-weight:700;color:#1a202c;margin:0 0 .5rem"></h3>
            <p id="kw-confirm-msg" style="text-align:center;font-size:.875rem;color:#718096;margin:0 0 1.5rem;line-height:1.5"></p>
            <div style="display:flex;gap:.75rem;justify-content:center">
                <button id="kw-confirm-cancel" style="flex:1;padding:.65rem;border:1px solid #e2e8f0;border-radius:8px;background:#fff;color:#718096;font-size:.875rem;cursor:pointer;font-weight:500;transition:border-color .15s" onmouseover="this.style.borderColor='#a0aec0'" onmouseout="this.style.borderColor='#e2e8f0'">Cancelar</button>
                <button id="kw-confirm-ok" style="flex:1;padding:.65rem;border:none;border-radius:8px;background:#e53e3e;color:#fff;font-size:.875rem;cursor:pointer;font-weight:700;transition:background .15s" onmouseover="this.style.background='#c53030'" onmouseout="this.style.background='#e53e3e'">Confirmar</button>
            </div>
        </div>
    </div>
    <style>@keyframes kwPop { from { opacity:0; transform:scale(.9) } to { opacity:1; transform:scale(1) } }</style>

    <div class="app-layout">
        
        <div class="sidebar-area">
            <?php include __DIR__ . '/views/layouts/sidebar.php'; ?>
        </div>
        
        <div class="main-area">
            
            <div class="topbar-area">
                <?php include __DIR__ . '/views/components/topbar.php'; ?>
            </div>
            
            <main class="content-area">
                <?php 
                // Carrega o conteúdo específico da página
                if (file_exists($content_file)) {
                    include $content_file;
                } else {
                    // Fallback para dashboard se página não existir
                    include __DIR__ . '/public/dashboard.php';
                }
                ?>
            </main>
            
        </div>
        
    </div>

    <script src="/assets/js/components/sidebar.js"></script>
    <script src="/assets/js/components/topbar.js"></script>
    <script src="/assets/js/bg-dashboard.js"></script>
    <script src="/assets/js/painel-cliente.js"></script>
    <script src="/assets/js/painel-aplicacao.js"></script>
    <script src="/assets/js/painel-usuario.js"></script>
</body>
</html>
