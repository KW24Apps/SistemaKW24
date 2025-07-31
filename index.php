<?php 
/**
 * INDEX - Página principal do sistema
 */

session_start();

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
                <h1>Dashboard - KW24 Sistema</h1>
                
                <div class="welcome-section">
                    <p><strong>Usuário:</strong> <?php echo htmlspecialchars($user_data['nome']); ?></p>
                    <p><strong>Perfil:</strong> <?php echo htmlspecialchars($user_data['perfil']); ?></p>
                    <p><strong>Último Login:</strong> <?php echo date('d/m/Y H:i:s', $user_data['login_time']); ?></p>
                </div>

                <div class="dashboard-content">
                    <h2>Bem-vindo ao Sistema KW24</h2>
                    <p>Utilize o menu lateral para navegar pelos módulos do sistema.</p>
                    
                    <div class="quick-actions">
                        <h3>Ações Rápidas</h3>
                        <ul>
                            <li><a href="/Apps/public/cadastro.php">Novo Cadastro</a></li>
                            <li><a href="/Apps/public/relatorio.php">Relatórios</a></li>
                            <li><a href="/Apps/public/logs.php">Logs do Sistema</a></li>
                        </ul>
                    </div>
                </div>
                
            </main>
            
        </div>
        
    </div>

    <script src="/Apps/assets/js/components/sidebar.js"></script>
    <script src="/Apps/assets/js/components/topbar.js"></script>
</body>
</html>
