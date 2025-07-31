<?php
/**
 * INTEGRAÇÃO PASSWORD RECOVERY NO INDEX PRINCIPAL
 * Adicione este código ao seu index.php principal para habilitar as rotas
 */

// Verificar se é uma requisição para recuperação de senha
$requestUri = $_SERVER['REQUEST_URI'];
$pathInfo = parse_url($requestUri, PHP_URL_PATH);

if (strpos($pathInfo, '/api/password-recovery/') === 0) {
    require_once __DIR__ . '/controllers/PasswordRecoveryController.php';
    
    $controller = new PasswordRecoveryController();
    $endpoint = str_replace('/api/password-recovery/', '', $pathInfo);
    
    // Log da requisição
    $controller->logAccess($endpoint);
    
    switch ($endpoint) {
        case 'initiate':
            $controller->initiate();
            break;
            
        case 'validate-code':
            $controller->validateCode();
            break;
            
        case 'reset-password':
            $controller->resetPassword();
            break;
            
        case 'status':
            $controller->getStatus();
            break;
            
        default:
            http_response_code(404);
            echo json_encode([
                'success' => false,
                'error' => 'Endpoint não encontrado',
                'available_endpoints' => [
                    '/api/password-recovery/initiate',
                    '/api/password-recovery/validate-code',
                    '/api/password-recovery/reset-password',
                    '/api/password-recovery/status'
                ]
            ]);
            break;
    }
    exit;
}
?>
