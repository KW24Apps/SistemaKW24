<?php 
/**
 * INDEX V2 - PÁGINA PRINCIPAL COM AUTENTICAÇÃO
 * Sistema moderno CSS Grid com controle de sessão via AuthenticationService
 */

session_start();

// Importa serviço de autenticação
require_once __DIR__ . '/services/AuthenticationService.php';

$authService = new AuthenticationService();

// Verificação de autenticação
if (!$authService->validateSession()) {
    // Não está logado ou sessão expirou - redireciona para login
    header('Location: public/login.php');
    exit;
}

// Obtém dados do usuário logado
$user_data = $authService->getCurrentUser();

if (!$user_data) {
    // Erro ao obter dados do usuário - redireciona para login
    header('Location: public/login.php?error=session');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>KW24 - Sistema Moderno v2 (Layout Bitrix24)</title>
    <link rel="stylesheet" href="/Apps/assets/css/layout.css">
    <link rel="stylesheet" href="/Apps/assets/css/components/sidebar.css">
    <link rel="stylesheet" href="/Apps/assets/css/components/topbar.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
</head>
<body>
    <!-- Container Principal - Padrão Moderno -->
    <div class="app-layout">
        
        <!-- SIDEBAR - Navegação Principal -->
        <?php include 'views/layouts/sidebar.php'; ?>
        
        <!-- MAIN CONTENT - Área de Trabalho -->
        <div class="main-content">
            
            <!-- TOPBAR - Cabeçalho Superior -->
            <?php include 'views/layouts/topbar.php'; ?>
            
            <!-- CONTENT AREA - Conteúdo Dinâmico -->
            <main class="content-area">
                <h1>🎉️️ KW24 Sistema v2 - Layout Moderno Bitrix24!</h1>
                
                <div>
                    <p><strong>Status:</strong> Sistema com layout moderno tipo Bitrix24 - CSS Grid</p>
                    <p><strong>Usuário:</strong> <?php echo htmlspecialchars($user_data['nome']); ?></p>
                    <p><strong>Perfil:</strong> <?php echo htmlspecialchars($user_data['perfil']); ?></p>
                    <p><strong>Login:</strong> <?php echo date('d/m/Y H:i:s', $user_data['login_time']); ?></p>
                </div>

                <h2>🎯️ Arquitetura: CSS Grid + Componentes Modulares + Responsivo</h2>
                
                <h3>🔧 Recursos Implementados:</h3>
                <ul>
                    <li>✅ Layout CSS Grid moderno (sem margin hacks)</li>
                    <li>✅ Sidebar v2 integrada com Grid</li>
                    <li>✅ Topbar v2 com fogo posicionado corretamente</li>
                    <li>✅ Ativa central para submenus dinâmicos</li>
                    <li>✅ Profile dropdown funcionando</li>
                    <li>✅ Arquivos CSS organizados e modulares</li>
                    <li>✅ Sistema de Login v2 integrado</li>
                    <li>✅ Autenticação com banco de dados</li>
                    <li>✅ Controle de sessão com timeout</li>
                </ul>

                <h3>🎯 Demonstração de Scroll:</h3>
                <p>Este conteúdo demonstra que o layout CSS Grid mantém a sidebar fixo enquanto o conteúdo rola.</p>
                
                <p>Linha de teste 1 - Layout moderno CSS Grid funcionando perfeitamente</p>
                <p>Linha de teste 2 - Layout moderno CSS Grid funcionando perfeitamente</p>
                <p>Linha de teste 3 - Layout moderno CSS Grid funcionando perfeitamente</p>
                <p>Linha de teste 4 - Layout moderno CSS Grid funcionando perfeitamente</p>
                <p>Linha de teste 5 - Layout moderno CSS Grid funcionando perfeitamente</p>
                <p>Linha de teste 6 - Layout moderno CSS Grid funcionando perfeitamente</p>
                <p>Linha de teste 7 - Layout moderno CSS Grid funcionando perfeitamente</p>
                <p>Linha de teste 8 - Layout moderno CSS Grid funcionando perfeitamente</p>
                <p>Linha de teste 9 - Layout moderno CSS Grid funcionando perfeitamente</p>
                <p>Linha de teste 10 - Layout moderno CSS Grid funcionando perfeitamente</p>
                <p>Linha de teste 11 - Layout moderno CSS Grid funcionando perfeitamente</p>
                <p>Linha de teste 12 - Layout moderno CSS Grid funcionando perfeitamente</p>
                <p>Linha de teste 13 - Layout moderno CSS Grid funcionando perfeitamente</p>
                <p>Linha de teste 14 - Layout moderno CSS Grid funcionando perfeitamente</p>
                <p>Linha de teste 15 - Layout moderno CSS Grid funcionando perfeitamente</p>
                <p>Linha de teste 16 - Layout moderno CSS Grid funcionando perfeitamente</p>
                <p>Linha de teste 17 - Layout moderno CSS Grid funcionando perfeitamente</p>
                <p>Linha de teste 18 - Layout moderno CSS Grid funcionando perfeitamente</p>
            </main>
            
        </div>
        
    </div>

    <!-- Scripts -->
    <script src="/Apps/assets/js/components/sidebar.js"></script>
    <script src="/Apps/assets/js/components/topbar.js"></script>
    <script src="/Apps/assets/js/layout.js"></script>
</body>
</html>
