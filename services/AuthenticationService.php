<?php
/**
 * AUTHENTICATION SERVICE - KW24 APPS V2
 * Implementando melhorias do módulo 6 - Sistema de autenticação robusto
 */

require_once __DIR__ . '/../dao/ColaboradorDAO.php';

class AuthenticationService {
    private $colaboradorDAO;
    private $config;
    
    public function __construct() {
        $this->colaboradorDAO = new ColaboradorDAO();
        $this->config = require_once __DIR__ . '/../config/config.php';
    }
    
    /**
     * Autentica usuário com credenciais
     */
    public function authenticate(string $username, string $password): array {
        $response = [
            'success' => false,
            'message' => '',
            'user' => null,
            'blocked_until' => null
        ];
        
        try {
            // Validação básica
            if (empty($username) || empty($password)) {
                $response['message'] = 'Usuário e senha são obrigatórios';
                return $response;
            }
            
            // Verifica se usuário está bloqueado
            if ($this->colaboradorDAO->isBlocked($username)) {
                $response['message'] = 'Usuário temporariamente bloqueado por muitas tentativas';
                $response['blocked_until'] = $this->getBlockedUntil($username);
                return $response;
            }
            
            // Busca usuário no banco
            $user = $this->colaboradorDAO->findByUsername($username);
            
            if (!$user) {
                $this->colaboradorDAO->incrementLoginAttempts($username);
                $this->logLoginAttempt($username, false);
                $response['message'] = 'Usuário ou senha inválidos';
                return $response;
            }
            
            // Verifica senha
            if (!$this->verifyPassword($password, $user['senha'])) {
                $this->colaboradorDAO->incrementLoginAttempts($username);
                $this->logLoginAttempt($username, false);
                $response['message'] = 'Usuário ou senha inválidos';
                return $response;
            }
            
            // ✅ MIGRAÇÃO AUTOMÁTICA: Se senha é MD5/texto plano, converte para Argon2ID
            if ($this->isLegacyPassword($user['senha'])) {
                $this->migrateUserPassword($user['id'], $password);
            }
            
            // Login bem-sucedido
            $this->colaboradorDAO->updateLastAccess($user['id']);
            $this->logLoginAttempt($username, true);
            
            $response['success'] = true;
            $response['message'] = 'Login realizado com sucesso';
            $response['user'] = $this->sanitizeUserData($user);
            
            return $response;
            
        } catch (Exception $e) {
            $response['message'] = 'Erro interno do sistema';
            $this->logError('Erro na autenticação: ' . $e->getMessage());
            return $response;
        }
    }
    
    /**
     * Verifica senha (compatibilidade com senhas antigas)
     */
    private function verifyPassword(string $password, string $hash): bool {
        // Se é hash moderno (Argon2ID), usa password_verify
        if (password_get_info($hash)['algo'] !== null) {
            return password_verify($password, $hash);
        }
        
        // Compatibilidade com senhas antigas (MD5 ou texto plano)
        return $password === $hash || md5($password) === $hash;
    }
    
    /**
     * Cria sessão de usuário
     */
    public function createSession(array $user): bool {
        try {
            // Regenera ID da sessão para segurança
            session_regenerate_id(true);
            
            // Define dados da sessão
            $_SESSION['user_authenticated'] = true;
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['nome'];
            $_SESSION['user_username'] = $user['usuario'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['perfil'];
            $_SESSION['login_time'] = time();
            $_SESSION['last_activity'] = time();
            $_SESSION['csrf_token'] = $this->generateCSRFToken();
            
            return true;
            
        } catch (Exception $e) {
            $this->logError('Erro ao criar sessão: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Valida sessão ativa
     */
    public function validateSession(): bool {
        if (!isset($_SESSION['user_authenticated']) || !$_SESSION['user_authenticated']) {
            return false;
        }
        
        $sessionLifetime = $this->config['security']['session_lifetime'];
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        
        // Verifica timeout da sessão
        if (time() - $lastActivity > $sessionLifetime) {
            $this->destroySession();
            return false;
        }
        
        // Atualiza última atividade
        $_SESSION['last_activity'] = time();
        
        return true;
    }
    
    /**
     * Destrói sessão
     */
    public function destroySession(): bool {
        try {
            // Limpa dados da sessão
            $_SESSION = [];
            
            // Remove cookie da sessão
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/');
            }
            
            // Destrói sessão
            session_destroy();
            
            return true;
            
        } catch (Exception $e) {
            $this->logError('Erro ao destruir sessão: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Gera token CSRF
     */
    public function generateCSRFToken(): string {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Valida token CSRF
     */
    public function validateCSRFToken(string $token): bool {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Obtém dados do usuário logado
     */
    public function getCurrentUser(): ?array {
        if (!$this->validateSession()) {
            return null;
        }
        
        return [
            'id' => $_SESSION['user_id'],
            'nome' => $_SESSION['user_name'],
            'usuario' => $_SESSION['user_username'],
            'email' => $_SESSION['user_email'],
            'perfil' => $_SESSION['user_role'],
            'login_time' => $_SESSION['login_time'],
            'csrf_token' => $_SESSION['csrf_token']
        ];
    }
    
    /**
     * Sanitiza dados do usuário (remove senha)
     */
    private function sanitizeUserData(array $user): array {
        unset($user['senha']);
        unset($user['tentativas_login']);
        unset($user['bloqueado_ate']);
        return $user;
    }
    
    /**
     * Obtém data de bloqueio do usuário
     */
    private function getBlockedUntil(string $username): ?string {
        $user = $this->colaboradorDAO->findByUsername($username);
        return $user['bloqueado_ate'] ?? null;
    }
    
    /**
     * Registra tentativa de login
     */
    private function logLoginAttempt(string $username, bool $success): void {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        
        $this->colaboradorDAO->logLoginAttempt($username, $success, $ip, $userAgent);
    }
    
    /**
     * Log de erro
     */
    private function logError(string $message): void {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [AUTH ERROR] {$message}" . PHP_EOL;
        
        $logFile = __DIR__ . '/../logs/auth.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * ✅ MIGRAÇÃO AUTOMÁTICA: Verifica se senha é do formato legado (MD5/texto plano)
     */
    private function isLegacyPassword(string $hash): bool {
        // Se é hash moderno (Argon2ID, bcrypt, etc), password_get_info retorna info
        $hashInfo = password_get_info($hash);
        
        // Se não tem algoritmo definido, é MD5 ou texto plano
        return $hashInfo['algo'] === null;
    }
    
    /**
     * ✅ MIGRAÇÃO AUTOMÁTICA: Converte senha legado para Argon2ID
     */
    private function migrateUserPassword(int $userId, string $plainPassword): bool {
        try {
            // Gera hash seguro
            $secureHash = password_hash(
                $plainPassword, 
                $this->config['security']['password_algorithm']
            );
            
            // Atualiza no banco
            $updated = $this->colaboradorDAO->updatePassword($userId, $secureHash);
            
            if ($updated) {
                $this->logError("✅ Senha migrada para Argon2ID - User ID: {$userId}");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->logError("❌ Erro na migração de senha - User ID: {$userId} - " . $e->getMessage());
            return false;
        }
    }
}
