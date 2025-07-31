<?php

/**
 * PASSWORD RECOVERY SERVICE - SISTEMA KW24
 * Gerencia todo o processo de recuperação de senha
 * Integração com EmailService e validações de segurança
 */

// Incluir dependências
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/EmailService.php';

class PasswordRecoveryService {
    
    private $db;
    private $emailService;
    
    // Configurações de segurança
    private const CODE_LENGTH = 6;
    private const CODE_EXPIRY_MINUTES = 15;
    private const MAX_ATTEMPTS_PER_HOUR = 3;
    private const MAX_ATTEMPTS_PER_DAY = 10;
    
    public function __construct($database = null) {
        $this->db = $database ?: Database::getInstance();
        $this->emailService = new EmailService();
        $this->createRecoveryTable();
    }
    
    /**
     * Cria tabela de recuperação se não existir
     */
    private function createRecoveryTable() {
        $sql = "CREATE TABLE IF NOT EXISTS password_recovery (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_identifier VARCHAR(255) NOT NULL,
            recovery_code VARCHAR(6) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP NOT NULL,
            used_at TIMESTAMP NULL,
            attempts INT DEFAULT 0,
            ip_address VARCHAR(45) NOT NULL,
            user_agent TEXT,
            is_active BOOLEAN DEFAULT TRUE,
            INDEX idx_code_active (recovery_code, is_active),
            INDEX idx_identifier_active (user_identifier, is_active),
            INDEX idx_expires (expires_at)
        )";
        
        $this->db->exec($sql);
    }
    
    /**
     * Inicia processo de recuperação de senha
     * @param string $identifier Email ou telefone do usuário
     * @return array Resultado da operação
     */
    public function initiateRecovery($identifier) {
        try {
            // Validar entrada
            $validation = $this->validateIdentifier($identifier);
            if (!$validation['valid']) {
                return $this->errorResponse($validation['message']);
            }
            
            // Verificar rate limiting
            if (!$this->checkRateLimit($identifier)) {
                return $this->errorResponse('Muitas tentativas. Tente novamente em 1 hora.');
            }
            
            // Buscar usuário
            $user = $this->findUserByIdentifier($identifier);
            if (!$user) {
                // Por segurança, não revelar se o usuário existe
                return $this->successResponse('Se o email/telefone existir, você receberá um código de recuperação.');
            }
            
            // Invalidar códigos antigos
            $this->invalidateOldCodes($identifier);
            
            // Gerar novo código
            $code = $this->generateRecoveryCode();
            $expiresAt = date('Y-m-d H:i:s', time() + (self::CODE_EXPIRY_MINUTES * 60));
            
            // Salvar no banco
            $recoveryId = $this->saveRecoveryCode($identifier, $code, $expiresAt);
            
            if (!$recoveryId) {
                return $this->errorResponse('Erro interno. Tente novamente.');
            }
            
            // Enviar email
            $emailResult = $this->emailService->sendPasswordRecoveryEmail(
                $user['email'], 
                $user['nome'], 
                $code
            );
            
            if (!$emailResult['success']) {
                return $this->errorResponse('Erro ao enviar email. Tente novamente.');
            }
            
            return $this->successResponse(
                'Código enviado para ' . $this->maskEmail($user['email']),
                ['masked_email' => $this->maskEmail($user['email'])]
            );
            
        } catch (Exception $e) {
            error_log("PasswordRecovery Error: " . $e->getMessage());
            return $this->errorResponse('Erro interno do sistema.');
        }
    }
    
    /**
     * Valida código de recuperação
     * @param string $identifier Email/telefone
     * @param string $code Código de 6 dígitos
     * @return array Resultado da validação
     */
    public function validateRecoveryCode($identifier, $code) {
        try {
            // Validar formato do código
            if (!$this->isValidCodeFormat($code)) {
                return $this->errorResponse('Código deve ter 6 dígitos.');
            }
            
            // Buscar código ativo
            $recovery = $this->getActiveRecoveryCode($identifier, $code);
            
            if (!$recovery) {
                $this->incrementAttempts($identifier, $code);
                return $this->errorResponse('Código inválido ou expirado.');
            }
            
            // Verificar se expirou
            if (strtotime($recovery['expires_at']) < time()) {
                $this->invalidateCode($recovery['id']);
                return $this->errorResponse('Código expirado. Solicite um novo.');
            }
            
            // Marcar como usado
            $this->markCodeAsUsed($recovery['id']);
            
            return $this->successResponse('Código válido.', [
                'recovery_id' => $recovery['id'],
                'user_identifier' => $identifier
            ]);
            
        } catch (Exception $e) {
            error_log("PasswordRecovery Validation Error: " . $e->getMessage());
            return $this->errorResponse('Erro interno do sistema.');
        }
    }
    
    /**
     * Redefine a senha do usuário
     * @param string $identifier Email/telefone
     * @param string $newPassword Nova senha
     * @param int $recoveryId ID do processo de recuperação
     * @return array Resultado da operação
     */
    public function resetPassword($identifier, $newPassword, $recoveryId) {
        try {
            // Validar senha
            $passwordValidation = $this->validatePassword($newPassword);
            if (!$passwordValidation['valid']) {
                return $this->errorResponse($passwordValidation['message']);
            }
            
            // Verificar se o processo de recuperação é válido
            if (!$this->isValidRecoveryProcess($recoveryId, $identifier)) {
                return $this->errorResponse('Processo de recuperação inválido.');
            }
            
            // Buscar usuário
            $user = $this->findUserByIdentifier($identifier);
            if (!$user) {
                return $this->errorResponse('Usuário não encontrado.');
            }
            
            // Atualizar senha
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            $updated = $this->updateUserPassword($user['id'], $hashedPassword);
            
            if (!$updated) {
                return $this->errorResponse('Erro ao atualizar senha.');
            }
            
            // Invalidar todos os códigos do usuário
            $this->invalidateAllUserCodes($identifier);
            
            // Log da ação
            $this->logPasswordReset($user['id'], $identifier);
            
            return $this->successResponse('Senha redefinida com sucesso.');
            
        } catch (Exception $e) {
            error_log("PasswordRecovery Reset Error: " . $e->getMessage());
            return $this->errorResponse('Erro interno do sistema.');
        }
    }
    
    /**
     * Validadores e utilitários
     */
    
    private function validateIdentifier($identifier) {
        $identifier = trim($identifier);
        
        if (empty($identifier)) {
            return ['valid' => false, 'message' => 'Email ou telefone é obrigatório.'];
        }
        
        // Validar se é email
        if (filter_var($identifier, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => true, 'type' => 'email'];
        }
        
        // Validar se é telefone (formato brasileiro)
        $phone = preg_replace('/\D/', '', $identifier);
        if (preg_match('/^(\+55)?[1-9]{2}9?[0-9]{8}$/', $phone)) {
            return ['valid' => true, 'type' => 'phone'];
        }
        
        return ['valid' => false, 'message' => 'Email ou telefone inválido.'];
    }
    
    private function validatePassword($password) {
        if (strlen($password) < 6) {
            return ['valid' => false, 'message' => 'Senha deve ter pelo menos 6 caracteres.'];
        }
        
        if (strlen($password) > 128) {
            return ['valid' => false, 'message' => 'Senha muito longa.'];
        }
        
        // Adicionar outras validações conforme necessário
        return ['valid' => true];
    }
    
    private function checkRateLimit($identifier) {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        // Verificar tentativas por hora
        $hourlyAttempts = $this->getAttemptsCount($identifier, 1);
        if ($hourlyAttempts >= self::MAX_ATTEMPTS_PER_HOUR) {
            return false;
        }
        
        // Verificar tentativas por dia
        $dailyAttempts = $this->getAttemptsCount($identifier, 24);
        if ($dailyAttempts >= self::MAX_ATTEMPTS_PER_DAY) {
            return false;
        }
        
        return true;
    }
    
    private function generateRecoveryCode() {
        return sprintf('%06d', random_int(100000, 999999));
    }
    
    private function maskEmail($email) {
        $parts = explode('@', $email);
        if (count($parts) !== 2) return $email;
        
        $name = $parts[0];
        $domain = $parts[1];
        
        if (strlen($name) <= 2) {
            $maskedName = str_repeat('*', strlen($name));
        } else {
            $maskedName = substr($name, 0, 2) . str_repeat('*', strlen($name) - 2);
        }
        
        return $maskedName . '@' . $domain;
    }
    
    private function isValidCodeFormat($code) {
        return preg_match('/^\d{6}$/', $code);
    }
    
    /**
     * Métodos de banco de dados
     */
    
    private function findUserByIdentifier($identifier) {
        $sql = "SELECT id, nome, email, telefone FROM usuarios 
                WHERE email = ? OR telefone = ? 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$identifier, $identifier]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function saveRecoveryCode($identifier, $code, $expiresAt) {
        $sql = "INSERT INTO password_recovery 
                (user_identifier, recovery_code, expires_at, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $identifier,
            $code,
            $expiresAt,
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1',
            $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown'
        ]);
        
        return $this->db->lastInsertId();
    }
    
    private function getActiveRecoveryCode($identifier, $code) {
        $sql = "SELECT * FROM password_recovery 
                WHERE user_identifier = ? 
                AND recovery_code = ? 
                AND is_active = TRUE 
                AND used_at IS NULL 
                ORDER BY created_at DESC 
                LIMIT 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$identifier, $code]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    private function markCodeAsUsed($recoveryId) {
        $sql = "UPDATE password_recovery 
                SET used_at = CURRENT_TIMESTAMP, is_active = FALSE 
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$recoveryId]);
    }
    
    private function invalidateOldCodes($identifier) {
        $sql = "UPDATE password_recovery 
                SET is_active = FALSE 
                WHERE user_identifier = ? AND is_active = TRUE";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$identifier]);
    }
    
    private function invalidateCode($recoveryId) {
        $sql = "UPDATE password_recovery 
                SET is_active = FALSE 
                WHERE id = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$recoveryId]);
    }
    
    private function invalidateAllUserCodes($identifier) {
        $sql = "UPDATE password_recovery 
                SET is_active = FALSE 
                WHERE user_identifier = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$identifier]);
    }
    
    private function incrementAttempts($identifier, $code) {
        $sql = "UPDATE password_recovery 
                SET attempts = attempts + 1 
                WHERE user_identifier = ? AND recovery_code = ?";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$identifier, $code]);
    }
    
    private function getAttemptsCount($identifier, $hours) {
        $sql = "SELECT COUNT(*) FROM password_recovery 
                WHERE user_identifier = ? 
                AND created_at > DATE_SUB(NOW(), INTERVAL ? HOUR)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$identifier, $hours]);
        return $stmt->fetchColumn();
    }
    
    private function updateUserPassword($userId, $hashedPassword) {
        $sql = "UPDATE usuarios SET senha = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([$hashedPassword, $userId]);
    }
    
    private function isValidRecoveryProcess($recoveryId, $identifier) {
        $sql = "SELECT COUNT(*) FROM password_recovery 
                WHERE id = ? 
                AND user_identifier = ? 
                AND used_at IS NOT NULL 
                AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$recoveryId, $identifier]);
        return $stmt->fetchColumn() > 0;
    }
    
    private function logPasswordReset($userId, $identifier) {
        $sql = "INSERT INTO system_logs (user_id, action, details, ip_address, created_at) 
                VALUES (?, 'password_reset', ?, ?, NOW())";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $userId,
            "Password reset for: {$identifier}",
            $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
        ]);
    }
    
    /**
     * Métodos de resposta
     */
    
    private function successResponse($message, $data = []) {
        return [
            'success' => true,
            'message' => $message,
            'data' => $data
        ];
    }
    
    private function errorResponse($message) {
        return [
            'success' => false,
            'error' => $message
        ];
    }
    
    /**
     * Limpeza automática de códigos expirados
     */
    public function cleanExpiredCodes() {
        $sql = "DELETE FROM password_recovery 
                WHERE expires_at < DATE_SUB(NOW(), INTERVAL 1 DAY)";
        
        return $this->db->exec($sql);
    }
}
