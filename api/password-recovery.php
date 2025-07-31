<?php
/**
 * API ENDPOINT - PASSWORD RECOVERY
 * Endpoint dedicado para recuperação de senha
 */

// Configurar headers
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Responder preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Incluir dependências
require_once __DIR__ . '/controllers/PasswordRecoveryController.php';

try {
    // Instanciar controller
    $controller = new PasswordRecoveryController();
    
    // Obter endpoint da URL
    $pathInfo = $_SERVER['PATH_INFO'] ?? '';
    $endpoint = trim($pathInfo, '/');
    
    // Log da requisição
    $controller->logAccess($endpoint);
    
    // Roteamento
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
                    'initiate',
                    'validate-code',
                    'reset-password',
                    'status'
                ]
            ]);
            break;
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor',
        'details' => $e->getMessage()
    ]);
}
?>
