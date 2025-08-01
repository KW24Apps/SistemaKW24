<?php
/**
 * AUTHENTICATION SERVICE - KW24 APPS V2
 */

require_once __DIR__ . '/../dao/ColaboradorDAO.php';

class AuthenticationService {
    private $colaboradorDAO;
    private $config;
    
    public function __construct() {
        $this->colaboradorDAO = new ColaboradorDAO();
        $this->loadConfig();
    }
    
    private function loadConfig() {
        if (!$this->config) {
            $this->config = require_once __DIR__ . '/../config/config.php';
        }
    }
    
    /**
     * Autentica usuário com credenciais
     */
    public function authenticate(string $username, string $password): array {
        $response = [
            'success' => false,
            'message' => '',
            'user' => null
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
                return $response;
            }
            
            // Busca usuário no banco
            $user = $this->colaboradorDAO->findByUsername($username);
            
            if (!$user) {
                $this->colaboradorDAO->incrementLoginAttempts($username);
                $response['message'] = 'Usuário ou senha inválidos';
                return $response;
            }
            
            // Verifica senha
            $passwordVerified = $this->verifyPassword($password, $user['senha']);
            
            if (!$passwordVerified) {
                $this->colaboradorDAO->incrementLoginAttempts($username);
                $response['message'] = 'Usuário ou senha inválidos';
                return $response;
            }
            
            // Migração automática de senha se necessário
            if ($this->isLegacyPassword($user['senha'])) {
                $this->migrateUserPassword($user['id'], $password);
            }
            
            // Login bem-sucedido
            $this->colaboradorDAO->updateLastAccess($user['id']);
            
            $response['success'] = true;
            $response['message'] = 'Login realizado com sucesso';
            $response['user'] = $this->sanitizeUserData($user);
            
            return $response;
            
        } catch (Exception $e) {
            $response['message'] = 'Erro interno do sistema';
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
        
        $this->loadConfig();
        
        $sessionLifetime = $this->config['security']['session_lifetime'] ?? 3600;
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        $currentTime = time();
        $timeDiff = $currentTime - $lastActivity;
        
        // Verifica timeout da sessão
        if ($sessionLifetime > 0 && $timeDiff > $sessionLifetime) {
            $this->destroySession();
            return false;
        }
        
        // Atualiza última atividade
        $_SESSION['last_activity'] = $currentTime;
        
        return true;
    }
    
    /**
     * Destrói sessão
     */
    public function destroySession(): bool {
        try {
            $_SESSION = [];
            
            if (isset($_COOKIE[session_name()])) {
                setcookie(session_name(), '', time() - 3600, '/');
            }
            
            session_destroy();
            return true;
            
        } catch (Exception $e) {
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
        return $user;
    }
    
    /**
     * Verifica se senha é do formato legado (MD5/texto plano)
     */
    private function isLegacyPassword(string $hash): bool {
        $hashInfo = password_get_info($hash);
        return $hashInfo['algo'] === null;
    }
    
    /**
     * Converte senha legado
     */
    private function migrateUserPassword(int $userId, string $plainPassword): bool {
        try {
            $this->loadConfig();
            
            $secureHash = password_hash(
                $plainPassword, 
                $this->config['security']['password_algorithm'] ?? PASSWORD_DEFAULT
            );
            
            return $this->colaboradorDAO->updatePassword($userId, $secureHash);
            
        } catch (Exception $e) {
            return false;
        }
    }
}
