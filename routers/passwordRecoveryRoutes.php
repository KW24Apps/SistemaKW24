<?php

/**
 * ROTAS DE RECUPERAÇÃO DE SENHA - SISTEMA KW24
 * Define endpoints para o sistema de recuperação
 */

// Incluir dependências
require_once __DIR__ . '/../controllers/PasswordRecoveryController.php';
require_once __DIR__ . '/../services/PasswordRecoveryService.php';
require_once __DIR__ . '/../services/EmailService.php';
require_once __DIR__ . '/../helpers/Database.php';

// Configurar cabeçalhos CORS e segurança
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

// Responder a preflight OPTIONS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Instanciar controller
$controller = new PasswordRecoveryController();

// Obter rota da URL
$requestUri = $_SERVER['REQUEST_URI'];
$basePath = '/Apps/routers/passwordRecoveryRoutes.php';

// Extrair path após o arquivo
$path = str_replace($basePath, '', $requestUri);
$path = parse_url($path, PHP_URL_PATH);
$path = trim($path, '/');

// Roteamento
switch ($path) {
    
    /**
     * POST /initiate
     * Inicia processo de recuperação
     * Body: { "identifier": "email@exemplo.com" }
     */
    case 'initiate':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }
        
        $controller->logAccess('initiate');
        $controller->initiate();
        break;
    
    /**
     * POST /validate-code
     * Valida código de recuperação
     * Body: { "identifier": "email@exemplo.com", "code": "123456" }
     */
    case 'validate-code':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }
        
        $controller->logAccess('validate-code');
        $controller->validateCode();
        break;
    
    /**
     * POST /reset-password
     * Redefine a senha
     * Body: { 
     *   "identifier": "email@exemplo.com", 
     *   "newPassword": "nova123", 
     *   "confirmPassword": "nova123",
     *   "recoveryId": 123
     * }
     */
    case 'reset-password':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }
        
        $controller->logAccess('reset-password');
        $controller->resetPassword();
        break;
    
    /**
     * GET /status
     * Verifica status do sistema
     */
    case 'status':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }
        
        $controller->logAccess('status');
        $controller->getStatus();
        break;
    
    /**
     * POST /cleanup (Admin apenas)
     * Limpa códigos expirados
     */
    case 'cleanup':
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }
        
        $controller->logAccess('cleanup');
        $controller->cleanup();
        break;
    
    /**
     * GET /test-email (Dev apenas)
     * Testa configuração de email
     */
    case 'test-email':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }
        
        // Verificar se é ambiente de desenvolvimento
        if (!defined('DEBUG_MODE') || !DEBUG_MODE) {
            http_response_code(403);
            echo json_encode(['success' => false, 'error' => 'Endpoint disponível apenas em desenvolvimento']);
            exit;
        }
        
        try {
            $emailService = new EmailService();
            $validation = $emailService->validateConfig();
            
            if (!$validation['valid']) {
                echo json_encode([
                    'success' => false, 
                    'error' => 'Configuração inválida: ' . $validation['error']
                ]);
                exit;
            }
            
            $testResult = $emailService->testConnection();
            
            echo json_encode([
                'success' => $testResult,
                'message' => $testResult ? 'Email configurado corretamente' : 'Falha na configuração de email',
                'config_valid' => true
            ]);
            
        } catch (Exception $e) {
            echo json_encode([
                'success' => false,
                'error' => 'Erro ao testar email: ' . $e->getMessage()
            ]);
        }
        break;
    
    /**
     * GET / ou GET /help
     * Documentação da API
     */
    case '':
    case 'help':
        if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
            http_response_code(405);
            echo json_encode(['success' => false, 'error' => 'Método não permitido']);
            exit;
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'API de Recuperação de Senha - Sistema KW24',
            'version' => '1.0.0',
            'endpoints' => [
                'POST /initiate' => [
                    'description' => 'Inicia processo de recuperação',
                    'body' => ['identifier' => 'Email ou telefone do usuário'],
                    'response' => 'Envia código por email'
                ],
                'POST /validate-code' => [
                    'description' => 'Valida código de recuperação',
                    'body' => ['identifier' => 'Email/telefone', 'code' => 'Código de 6 dígitos'],
                    'response' => 'Confirma se código é válido'
                ],
                'POST /reset-password' => [
                    'description' => 'Redefine a senha',
                    'body' => ['identifier', 'newPassword', 'confirmPassword', 'recoveryId'],
                    'response' => 'Confirma alteração da senha'
                ],
                'GET /status' => [
                    'description' => 'Status do sistema',
                    'response' => 'Informações sobre saúde dos serviços'
                ],
                'POST /cleanup' => [
                    'description' => 'Limpa códigos expirados (Admin)',
                    'auth' => 'Requer permissão de administrador'
                ]
            ],
            'examples' => [
                'initiate' => 'POST /initiate {"identifier": "usuario@email.com"}',
                'validate' => 'POST /validate-code {"identifier": "usuario@email.com", "code": "123456"}',
                'reset' => 'POST /reset-password {"identifier": "usuario@email.com", "newPassword": "nova123", "confirmPassword": "nova123", "recoveryId": 123}'
            ]
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;
    
    /**
     * Endpoint não encontrado
     */
    default:
        http_response_code(404);
        echo json_encode([
            'success' => false,
            'error' => 'Endpoint não encontrado: ' . $path,
            'available_endpoints' => [
                '/initiate',
                '/validate-code',
                '/reset-password', 
                '/status',
                '/cleanup',
                '/help'
            ],
            'documentation' => 'Acesse /help para ver a documentação completa'
        ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        break;
}

/**
 * Função para log de erro global
 */
function logError($message, $context = []) {
    $logEntry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'error' => $message,
        'context' => $context,
        'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
        'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
    ];
    
    $logFile = __DIR__ . '/../logs/password_recovery_errors.log';
    file_put_contents($logFile, json_encode($logEntry) . "\n", FILE_APPEND | LOCK_EX);
}

/**
 * Handler de erros PHP
 */
set_error_handler(function($severity, $message, $file, $line) {
    logError("PHP Error: {$message}", [
        'file' => $file,
        'line' => $line,
        'severity' => $severity
    ]);
});

/**
 * Handler de exceções não capturadas
 */
set_exception_handler(function($exception) {
    logError("Uncaught Exception: " . $exception->getMessage(), [
        'file' => $exception->getFile(),
        'line' => $exception->getLine(),
        'trace' => $exception->getTraceAsString()
    ]);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Erro interno do servidor',
        'timestamp' => date('c')
    ]);
});
