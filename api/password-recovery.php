<?php
/**
 * API RECUPERAÇÃO DE SENHA - KW24 APPS
 * Endpoints para sistema de recuperação de senha
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Permitir OPTIONS para CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../services/EmailService.php';

session_start();

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    
    switch ($action) {
        case 'send_code':
            handleSendCode($input);
            break;
            
        case 'verify_code':
            handleVerifyCode($input);
            break;
            
        case 'reset_password':
            handleResetPassword($input);
            break;
            
        default:
            throw new Exception('Ação não válida');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

/**
 * ETAPA 1: Enviar código de recuperação
 */
function handleSendCode($input) {
    $identifier = trim($input['identifier'] ?? '');
    
    if (empty($identifier)) {
        throw new Exception('Usuário/email é obrigatório');
    }
    
    $db = Database::getInstance();
    
    // Buscar usuário por username ou email
    $sql = "SELECT id, UserName as usuario, Email as email, Nome as nome FROM Colaboradores 
            WHERE UserName = ? OR Email = ? 
            LIMIT 1";
    $user = $db->fetchOne($sql, [$identifier, $identifier]);
    
    if (!$user) {
        throw new Exception('Usuário não encontrado');
    }
    
    // Gerar código de 6 dígitos
    $code = sprintf('%06d', random_int(100000, 999999));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+15 minutes'));
    
    // Salvar código na sessão temporariamente (depois implementar tabela)
    $_SESSION['recovery_data'] = [
        'user_id' => $user['id'],
        'code' => $code,
        'expires_at' => $expiresAt,
        'verified' => false
    ];
    
    // Enviar email de recuperação
    $emailService = new EmailService();
    $emailSent = $emailService->sendPasswordRecovery(
        $user['email'],
        $user['nome'],
        $code
    );
    
    // Resposta
    if ($emailSent) {
        echo json_encode([
            'success' => true,
            'message' => 'Código enviado com sucesso',
            'masked_email' => maskEmail($user['email'])
            // Removido debug_code para produção
        ]);
    } else {
        // Se falhou o envio, remover dados da sessão
        unset($_SESSION['recovery_data']);
        throw new Exception('Erro ao enviar email. Tente novamente.');
    }
}

/**
 * ETAPA 2: Verificar código
 */
function handleVerifyCode($input) {
    $code = trim($input['code'] ?? '');
    
    if (empty($code)) {
        throw new Exception('Código é obrigatório');
    }
    
    if (!isset($_SESSION['recovery_data'])) {
        throw new Exception('Sessão de recuperação expirada');
    }
    
    $recoveryData = $_SESSION['recovery_data'];
    
    // Verificar se expirou
    if (strtotime($recoveryData['expires_at']) < time()) {
        unset($_SESSION['recovery_data']);
        throw new Exception('Código expirado');
    }
    
    // Verificar código
    if ($code !== $recoveryData['code']) {
        throw new Exception('Código inválido');
    }
    
    // Marcar como verificado
    $_SESSION['recovery_data']['verified'] = true;
    
    echo json_encode([
        'success' => true,
        'message' => 'Código verificado com sucesso'
    ]);
}

/**
 * ETAPA 3: Redefinir senha
 */
function handleResetPassword($input) {
    $newPassword = $input['password'] ?? '';
    $confirmPassword = $input['confirm_password'] ?? '';
    
    if (empty($newPassword) || empty($confirmPassword)) {
        throw new Exception('Senha e confirmação são obrigatórias');
    }
    
    if ($newPassword !== $confirmPassword) {
        throw new Exception('Senhas não conferem');
    }
    
    if (strlen($newPassword) < 6) {
        throw new Exception('Senha deve ter pelo menos 6 caracteres');
    }
    
    if (!isset($_SESSION['recovery_data']) || !$_SESSION['recovery_data']['verified']) {
        throw new Exception('Código não verificado');
    }
    
    $recoveryData = $_SESSION['recovery_data'];
    $userId = $recoveryData['user_id'];
    
    $db = Database::getInstance();
    
    // Hash da nova senha (Argon2ID como no sistema atual)
    $hashedPassword = password_hash($newPassword, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3
    ]);
    
    // Atualizar senha no banco
    $sql = "UPDATE Colaboradores SET senha = ? WHERE id = ?";
    $db->execute($sql, [$hashedPassword, $userId]);
    
    // Limpar dados de recuperação
    unset($_SESSION['recovery_data']);
    
    echo json_encode([
        'success' => true,
        'message' => 'Senha alterada com sucesso'
    ]);
}

/**
 * Mascarar email para exibição
 */
function maskEmail($email) {
    if (strpos($email, '@') === false) {
        return $email;
    }
    
    list($user, $domain) = explode('@', $email);
    $maskedUser = strlen($user) > 2 ? 
        substr($user, 0, 2) . str_repeat('*', strlen($user) - 2) : 
        $user;
        
    return $maskedUser . '@' . $domain;
}
?>
