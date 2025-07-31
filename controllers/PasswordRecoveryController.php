<?php

/**
 * PASSWORD RECOVERY CONTROLLER - SISTEMA KW24
 * Endpoints para recuperação de senha
 * Integração com PasswordRecoveryService
 */

class PasswordRecoveryController {
    
    private $recoveryService;
    
    public function __construct() {
        $this->recoveryService = new PasswordRecoveryService();
    }
    
    /**
     * Inicia processo de recuperação
     * POST /api/password-recovery/initiate
     */
    public function initiate() {
        try {
            // Verificar método
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return $this->errorResponse('Método não permitido', 405);
            }
            
            // Verificar Content-Type
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            if (strpos($contentType, 'application/json') === false) {
                return $this->errorResponse('Content-Type deve ser application/json', 400);
            }
            
            // Obter dados
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->errorResponse('JSON inválido', 400);
            }
            
            // Validar dados obrigatórios
            if (empty($input['identifier'])) {
                return $this->errorResponse('Email ou telefone é obrigatório', 400);
            }
            
            // Processar recuperação
            $result = $this->recoveryService->initiateRecovery($input['identifier']);
            
            if ($result['success']) {
                return $this->successResponse($result['message'], $result['data'] ?? []);
            } else {
                return $this->errorResponse($result['error'], 400);
            }
            
        } catch (Exception $e) {
            error_log("PasswordRecoveryController::initiate Error: " . $e->getMessage());
            return $this->errorResponse('Erro interno do servidor', 500);
        }
    }
    
    /**
     * Valida código de recuperação
     * POST /api/password-recovery/validate-code
     */
    public function validateCode() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return $this->errorResponse('Método não permitido', 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->errorResponse('JSON inválido', 400);
            }
            
            // Validar campos obrigatórios
            if (empty($input['identifier']) || empty($input['code'])) {
                return $this->errorResponse('Email/telefone e código são obrigatórios', 400);
            }
            
            // Validar código
            $result = $this->recoveryService->validateRecoveryCode(
                $input['identifier'],
                $input['code']
            );
            
            if ($result['success']) {
                return $this->successResponse($result['message'], $result['data'] ?? []);
            } else {
                return $this->errorResponse($result['error'], 400);
            }
            
        } catch (Exception $e) {
            error_log("PasswordRecoveryController::validateCode Error: " . $e->getMessage());
            return $this->errorResponse('Erro interno do servidor', 500);
        }
    }
    
    /**
     * Redefine a senha
     * POST /api/password-recovery/reset-password
     */
    public function resetPassword() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return $this->errorResponse('Método não permitido', 405);
            }
            
            $input = json_decode(file_get_contents('php://input'), true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $this->errorResponse('JSON inválido', 400);
            }
            
            // Validar campos obrigatórios
            $required = ['identifier', 'newPassword', 'confirmPassword', 'recoveryId'];
            foreach ($required as $field) {
                if (empty($input[$field])) {
                    return $this->errorResponse("Campo obrigatório: {$field}", 400);
                }
            }
            
            // Verificar se senhas coincidem
            if ($input['newPassword'] !== $input['confirmPassword']) {
                return $this->errorResponse('Senhas não coincidem', 400);
            }
            
            // Redefinir senha
            $result = $this->recoveryService->resetPassword(
                $input['identifier'],
                $input['newPassword'],
                $input['recoveryId']
            );
            
            if ($result['success']) {
                return $this->successResponse($result['message']);
            } else {
                return $this->errorResponse($result['error'], 400);
            }
            
        } catch (Exception $e) {
            error_log("PasswordRecoveryController::resetPassword Error: " . $e->getMessage());
            return $this->errorResponse('Erro interno do servidor', 500);
        }
    }
    
    /**
     * Verifica status do sistema de recuperação
     * GET /api/password-recovery/status
     */
    public function getStatus() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
                return $this->errorResponse('Método não permitido', 405);
            }
            
            // Verificar se serviços estão funcionais
            $status = [
                'service_active' => true,
                'email_service' => $this->checkEmailService(),
                'database' => $this->checkDatabase(),
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            return $this->successResponse('Status do sistema', $status);
            
        } catch (Exception $e) {
            error_log("PasswordRecoveryController::getStatus Error: " . $e->getMessage());
            return $this->errorResponse('Erro interno do servidor', 500);
        }
    }
    
    /**
     * Limpa códigos expirados (para uso administrativo)
     * POST /api/password-recovery/cleanup
     */
    public function cleanup() {
        try {
            if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
                return $this->errorResponse('Método não permitido', 405);
            }
            
            // Verificar se é admin (implementar conforme seu sistema de autenticação)
            if (!$this->isAdmin()) {
                return $this->errorResponse('Acesso negado', 403);
            }
            
            $cleaned = $this->recoveryService->cleanExpiredCodes();
            
            return $this->successResponse("Limpeza concluída. {$cleaned} códigos removidos.");
            
        } catch (Exception $e) {
            error_log("PasswordRecoveryController::cleanup Error: " . $e->getMessage());
            return $this->errorResponse('Erro interno do servidor', 500);
        }
    }
    
    /**
     * Métodos auxiliares
     */
    
    private function checkEmailService() {
        try {
            $emailService = new EmailService();
            $validation = $emailService->validateConfig();
            return $validation['valid'];
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function checkDatabase() {
        try {
            $db = Database::getInstance();
            $stmt = $db->query("SELECT 1");
            return $stmt !== false;
        } catch (Exception $e) {
            return false;
        }
    }
    
    private function isAdmin() {
        // Implementar verificação de admin conforme seu sistema
        // Exemplo básico:
        session_start();
        return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
    }
    
    /**
     * Métodos de resposta HTTP
     */
    
    private function successResponse($message, $data = [], $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('c')
        ];
        
        if (!empty($data)) {
            $response['data'] = $data;
        }
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    private function errorResponse($message, $statusCode = 400) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        $response = [
            'success' => false,
            'error' => $message,
            'timestamp' => date('c')
        ];
        
        echo json_encode($response, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    /**
     * Middleware para logs de acesso
     */
    public function logAccess($endpoint, $identifier = null) {
        try {
            $logData = [
                'endpoint' => $endpoint,
                'method' => $_SERVER['REQUEST_METHOD'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown',
                'identifier' => $identifier,
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            $logFile = __DIR__ . '/../logs/password_recovery_access.log';
            $logEntry = date('Y-m-d H:i:s') . " - " . json_encode($logData) . "\n";
            
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
            
        } catch (Exception $e) {
            error_log("PasswordRecoveryController::logAccess Error: " . $e->getMessage());
        }
    }
}

// Roteamento simples para uso direto
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    
    // Definir CORS se necessário
    header('Access-Control-Allow-Origin: *');
    header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
    header('Access-Control-Allow-Headers: Content-Type, Authorization');
    
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(200);
        exit;
    }
    
    $controller = new PasswordRecoveryController();
    
    // Determinar endpoint baseado na URL
    $path = $_SERVER['PATH_INFO'] ?? $_SERVER['REQUEST_URI'] ?? '';
    $path = parse_url($path, PHP_URL_PATH);
    
    switch ($path) {
        case '/initiate':
            $controller->logAccess('initiate');
            $controller->initiate();
            break;
            
        case '/validate-code':
            $controller->logAccess('validate-code');
            $controller->validateCode();
            break;
            
        case '/reset-password':
            $controller->logAccess('reset-password');
            $controller->resetPassword();
            break;
            
        case '/status':
            $controller->logAccess('status');
            $controller->getStatus();
            break;
            
        case '/cleanup':
            $controller->logAccess('cleanup');
            $controller->cleanup();
            break;
            
        default:
            http_response_code(404);
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Endpoint não encontrado',
                'available_endpoints' => [
                    '/initiate',
                    '/validate-code', 
                    '/reset-password',
                    '/status',
                    '/cleanup'
                ]
            ]);
            break;
    }
}
