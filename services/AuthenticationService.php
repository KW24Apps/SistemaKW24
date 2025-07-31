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
     * Debug log específico para autenticação
     */
    private function authDebugLog($message, $data = null) {
        $timestamp = date('Y-m-d H:i:s.u');
        $sessionId = session_id() ?: 'NO_SESSION';
        $logMessage = "[$timestamp] [AUTH] [SID:$sessionId] $message";
        
        if ($data !== null) {
            $logMessage .= " | Data: " . (is_array($data) || is_object($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data);
        }
        
        $logMessage .= "\n";
        file_put_contents(__DIR__ . '/../login_debug.log', $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Autentica usuário com credenciais
     */
    public function authenticate(string $username, string $password): array {
        // Log para debug
        $this->authDebugLog("=== AUTHENTICATE METHOD CALLED ===");
        $this->authDebugLog("Username", $username);
        $this->authDebugLog("Password length", strlen($password));
        
        $response = [
            'success' => false,
            'message' => '',
            'user' => null,
            'blocked_until' => null
        ];
        
        try {
            // Validação básica
            if (empty($username) || empty($password)) {
                $this->authDebugLog("VALIDATION FAILED - empty credentials");
                $response['message'] = 'Usuário e senha são obrigatórios';
                return $response;
            }
            
            // Verifica se usuário está bloqueado
            $isBlocked = $this->colaboradorDAO->isBlocked($username);
            $this->authDebugLog("User blocked check", $isBlocked ? 'BLOCKED' : 'NOT_BLOCKED');
            
            if ($isBlocked) {
                $response['message'] = 'Usuário temporariamente bloqueado por muitas tentativas';
                $response['blocked_until'] = $this->getBlockedUntil($username);
                $this->authDebugLog("User is blocked", $response['blocked_until']);
                return $response;
            }
            
            // Busca usuário no banco
            $this->authDebugLog("Searching user in database");
            $user = $this->colaboradorDAO->findByUsername($username);
            
            if (!$user) {
                $this->authDebugLog("USER NOT FOUND in database");
                $this->colaboradorDAO->incrementLoginAttempts($username);
                $this->logError("Tentativa de login - usuário não encontrado: {$username}");
                $response['message'] = 'Usuário ou senha inválidos';
                return $response;
            }
            
            $this->authDebugLog("User found in database", [
                'id' => $user['id'],
                'usuario' => $user['usuario'],
                'ativo' => $user['ativo']
            ]);
            
            // Verifica senha
            $this->authDebugLog("Starting password verification");
            $this->authDebugLog("Stored password hash", $user['senha']);
            
            $passwordVerified = $this->verifyPassword($password, $user['senha']);
            $this->authDebugLog("Password verification result", $passwordVerified ? 'SUCCESS' : 'FAILED');
            
            if (!$passwordVerified) {
                $this->authDebugLog("PASSWORD VERIFICATION FAILED - incrementing attempts");
                $this->colaboradorDAO->incrementLoginAttempts($username);
                $this->logError("Tentativa de login - senha incorreta: {$username}");
                $response['message'] = 'Usuário ou senha inválidos';
                return $response;
            }
            
            $this->authDebugLog("Password verification SUCCESS - checking for migration");
            
            // ✅ MIGRAÇÃO AUTOMÁTICA: Se senha é MD5/texto plano, converte para Argon2ID
            $isLegacy = $this->isLegacyPassword($user['senha']);
            $this->authDebugLog("Legacy password check", $isLegacy ? 'IS_LEGACY' : 'IS_MODERN');
            
            if ($isLegacy) {
                $this->authDebugLog("Starting password migration");
                // DEBUG: Log da senha que está sendo migrada
                $this->logError("🔧 MIGRAÇÃO DEBUG - Senha recebida: '" . $password . "' (tamanho: " . strlen($password) . ")");
                $migrationResult = $this->migrateUserPassword($user['id'], $password);
                $this->authDebugLog("Password migration result", $migrationResult ? 'SUCCESS' : 'FAILED');
            }
            
            // Login bem-sucedido
            $this->authDebugLog("LOGIN SUCCESS - updating last access");
            $updateResult = $this->colaboradorDAO->updateLastAccess($user['id']);
            $this->authDebugLog("Update last access result", $updateResult ? 'SUCCESS' : 'FAILED');
            
            $this->logError("Login bem-sucedido: {$username}");
            
            $response['success'] = true;
            $response['message'] = 'Login realizado com sucesso';
            $response['user'] = $this->sanitizeUserData($user);
            
            $this->authDebugLog("Authentication completed successfully", $response['user']);
            
            return $response;
            
        } catch (Exception $e) {
            $this->authDebugLog("EXCEPTION in authenticate", $e->getMessage());
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
        $this->authDebugLog("=== CREATE SESSION METHOD CALLED ===");
        $this->authDebugLog("User data for session", $user);
        
        try {
            // Regenera ID da sessão para segurança
            $oldSessionId = session_id();
            $this->authDebugLog("Old session ID", $oldSessionId);
            
            session_regenerate_id(true);
            $newSessionId = session_id();
            $this->authDebugLog("New session ID", $newSessionId);
            
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
            
            $this->authDebugLog("Session data set", $_SESSION);
            $this->authDebugLog("Session creation SUCCESS");
            
            return true;
            
        } catch (Exception $e) {
            $this->authDebugLog("EXCEPTION in createSession", $e->getMessage());
            $this->logError('Erro ao criar sessão: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Valida sessão ativa
     */
    public function validateSession(): bool {
        $this->authDebugLog("=== VALIDATE SESSION METHOD CALLED ===");
        $this->authDebugLog("Current session data", $_SESSION ?? []);
        
        if (!isset($_SESSION['user_authenticated']) || !$_SESSION['user_authenticated']) {
            $this->authDebugLog("Session validation FAILED - not authenticated");
            return false;
        }
        
        $sessionLifetime = $this->config['security']['session_lifetime'];
        $lastActivity = $_SESSION['last_activity'] ?? 0;
        $currentTime = time();
        $timeDiff = $currentTime - $lastActivity;
        
        $this->authDebugLog("Session lifetime check", [
            'session_lifetime' => $sessionLifetime,
            'last_activity' => $lastActivity,
            'current_time' => $currentTime,
            'time_diff' => $timeDiff
        ]);
        
        // Verifica timeout da sessão
        if ($timeDiff > $sessionLifetime) {
            $this->authDebugLog("Session validation FAILED - timeout exceeded");
            $this->destroySession();
            return false;
        }
        
        // Atualiza última atividade
        $_SESSION['last_activity'] = $currentTime;
        $this->authDebugLog("Session validation SUCCESS - updated last activity");
        
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
        // ========== DEBUG ESPECÍFICO PARA MIGRAÇÃO ==========
        $debugFile = __DIR__ . '/../migration_debug.log';
        $timestamp = date('Y-m-d H:i:s.u');
        
        $migrationLog = function($message, $data = null) use ($debugFile, $timestamp) {
            $logMessage = "[$timestamp] MIGRATION: $message";
            if ($data !== null) {
                $logMessage .= " | Data: " . (is_array($data) || is_object($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data);
            }
            $logMessage .= "\n";
            file_put_contents($debugFile, $logMessage, FILE_APPEND | LOCK_EX);
        };
        
        $migrationLog("=== MIGRATION STARTED ===");
        $migrationLog("User ID", $userId);
        $migrationLog("Plain password received", $plainPassword);
        $migrationLog("Password length", strlen($plainPassword));
        $migrationLog("Password as hex", bin2hex($plainPassword));
        $migrationLog("Password char by char", implode(',', str_split($plainPassword)));
        
        // Busca usuário atual para ver senha no banco
        $currentUser = $this->colaboradorDAO->findById($userId);
        if ($currentUser) {
            $currentPass = $currentUser['senha'] ?? '';
            $migrationLog("Current password in DB", $currentPass);
            $migrationLog("Current password length", strlen($currentPass));
            $migrationLog("Current password as hex", bin2hex($currentPass));
        }
        // ========== FIM DEBUG INICIAL ==========
        
        try {
            // DEBUG: Log mais detalhado
            $this->logError("🔧 MIGRAÇÃO - Iniciando para User ID: {$userId}");
            $this->logError("🔧 MIGRAÇÃO - Senha original: '" . $plainPassword . "'");
            
            // Gera hash seguro
            $migrationLog("Generating new hash with Argon2ID");
            $secureHash = password_hash(
                $plainPassword, 
                $this->config['security']['password_algorithm']
            );
            
            $migrationLog("Generated hash", $secureHash);
            $migrationLog("Generated hash length", strlen($secureHash));
            
            $this->logError("🔧 MIGRAÇÃO - Hash gerado: " . substr($secureHash, 0, 30) . "...");
            
            // Testa se o hash confere ANTES de salvar
            $testVerify = password_verify($plainPassword, $secureHash);
            $migrationLog("Immediate hash test", $testVerify ? 'SUCCESS' : 'FAILED');
            $this->logError("🔧 MIGRAÇÃO - Teste hash: " . ($testVerify ? 'OK' : 'FALHA'));
            
            // Se falhou, testa várias variações da senha
            if (!$testVerify) {
                $migrationLog("HASH TEST FAILED - Testing variations");
                $variations = [
                    'original' => $plainPassword,
                    'trimmed' => trim($plainPassword),
                    'rtrimmed' => rtrim($plainPassword),
                    'ltrimmed' => ltrim($plainPassword),
                    'no_cr' => str_replace("\r", "", $plainPassword),
                    'no_lf' => str_replace("\n", "", $plainPassword),
                    'no_crlf' => str_replace("\r\n", "", $plainPassword),
                ];
                
                foreach ($variations as $name => $variation) {
                    $testVar = password_verify($variation, $secureHash);
                    $migrationLog("Variation '$name' test", $testVar ? 'SUCCESS' : 'FAILED');
                    $migrationLog("Variation '$name' value", $variation);
                    $migrationLog("Variation '$name' hex", bin2hex($variation));
                }
            }
            
            // Atualiza no banco
            $migrationLog("Updating password in database");
            $updated = $this->colaboradorDAO->updatePassword($userId, $secureHash);
            $migrationLog("Database update result", $updated ? 'SUCCESS' : 'FAILED');
            
            // Verifica se foi salvo corretamente
            $updatedUser = $this->colaboradorDAO->findById($userId);
            if ($updatedUser) {
                $storedHash = $updatedUser['senha'] ?? '';
                $migrationLog("Password after update", $storedHash);
                $migrationLog("Stored hash matches generated", ($storedHash === $secureHash) ? 'MATCH' : 'NO_MATCH');
                
                // Testa a senha original contra o hash salvo
                $finalTest = password_verify($plainPassword, $storedHash);
                $migrationLog("Final test - original password vs stored hash", $finalTest ? 'SUCCESS' : 'FAILED');
                
                // Se ainda falha, testa variações contra o hash salvo
                if (!$finalTest) {
                    $migrationLog("FINAL TEST FAILED - Testing variations against stored hash");
                    $variations = [
                        'original' => $plainPassword,
                        'trimmed' => trim($plainPassword),
                        'rtrimmed' => rtrim($plainPassword),
                        'ltrimmed' => ltrim($plainPassword),
                    ];
                    
                    foreach ($variations as $name => $variation) {
                        $testVar = password_verify($variation, $storedHash);
                        $migrationLog("Final variation '$name' test", $testVar ? 'SUCCESS' : 'FAILED');
                    }
                }
            }
            
            $migrationLog("=== MIGRATION COMPLETED ===\n");
            
            if ($updated) {
                $this->logError("✅ Senha migrada para Argon2ID - User ID: {$userId}");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            $migrationLog("EXCEPTION during migration", $e->getMessage());
            $this->logError("❌ Erro na migração de senha - User ID: {$userId} - " . $e->getMessage());
            return false;
        }
    }
}
