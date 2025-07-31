<?php
/**
 * COLABORADOR DAO - KW24 APPS V2
 * Implementando melhorias do módulo 4 - DAO específico para colaboradores
 */

require_once __DIR__ . '/../helpers/Database.php';

class ColaboradorDAO {
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = require_once __DIR__ . '/../config/config.php';
    }
    
    /**
     * Busca colaborador por usuário para autenticação (adaptado para tabela Colaboradores)
     */
    public function findByUsername(string $username): ?array {
        $sql = "
            SELECT 
                id,
                Nome as nome,
                UserName as usuario,
                senha,
                Email as email,
                CPF,
                Cargo,
                Telefone,
                perfil,
                ativo,
                ultimo_acesso,
                tentativas_login,
                criado_em,
                atualizado_em
            FROM Colaboradores 
            WHERE UserName = :username 
            AND ativo = 1
            LIMIT 1
        ";
        
        return $this->db->fetchOne($sql, ['username' => $username]);
    }
    
    /**
     * Busca colaborador por ID (adaptado para tabela Colaboradores)
     */
    public function findById(int $id): ?array {
        $sql = "
            SELECT 
                id,
                Nome as nome,
                UserName as usuario,
                Email as email,
                CPF,
                Cargo,
                Telefone,
                perfil,
                ativo,
                ultimo_acesso,
                criado_em,
                atualizado_em
            FROM Colaboradores 
            WHERE id = :id 
            AND ativo = 1
            LIMIT 1
        ";
        
        return $this->db->fetchOne($sql, ['id' => $id]);
    }
    
    /**
     * Atualiza último acesso do colaborador (adaptado para tabela Colaboradores)
     */
    public function updateLastAccess(int $id): bool {
        $sql = "
            UPDATE Colaboradores 
            SET 
                ultimo_acesso = NOW(),
                tentativas_login = 0,
                atualizado_em = NOW()
            WHERE id = :id
        ";
        
        try {
            $this->db->execute($sql, ['id' => $id]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Incrementa tentativas de login (adaptado para tabela Colaboradores - sem bloqueio)
     */
    public function incrementLoginAttempts(string $username): bool {
        $sql = "
            UPDATE Colaboradores 
            SET 
                tentativas_login = tentativas_login + 1,
                atualizado_em = NOW()
            WHERE UserName = :username
        ";
        
        try {
            $this->db->execute($sql, ['username' => $username]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Verifica se colaborador tem muitas tentativas (adaptado - sem bloqueio automático)
     */
    public function isBlocked(string $username): bool {
        $sql = "
            SELECT tentativas_login
            FROM Colaboradores 
            WHERE UserName = :username
            LIMIT 1
        ";
        
        $result = $this->db->fetchOne($sql, ['username' => $username]);
        
        if (!$result) {
            return false;
        }
        
        // Considera "bloqueado" se passou de 5 tentativas (para controle manual)
        return $result['tentativas_login'] >= 5;
    }
    
    /**
     * Atualiza senha do colaborador (adaptado para tabela Colaboradores)
     */
    public function updatePassword(int $id, string $newPassword): bool {
        $hashedPassword = password_hash(
            $newPassword, 
            $this->config['security']['password_algorithm']
        );
        
        $sql = "
            UPDATE Colaboradores 
            SET 
                senha = :password,
                atualizado_em = NOW()
            WHERE id = :id
        ";
        
        try {
            $this->db->execute($sql, [
                'id' => $id,
                'password' => $hashedPassword
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Registra log de login
     */
    public function logLoginAttempt(string $username, bool $success, string $ip, string $userAgent): bool {
        $sql = "
            INSERT INTO login_log (
                usuario,
                sucesso,
                ip_address,
                user_agent,
                tentativa_em
            ) VALUES (
                :username,
                :success,
                :ip,
                :user_agent,
                NOW()
            )
        ";
        
        try {
            $this->db->execute($sql, [
                'username' => $username,
                'success' => $success ? 1 : 0,
                'ip' => $ip,
                'user_agent' => $userAgent
            ]);
            return true;
        } catch (Exception $e) {
            // Se tabela não existe, continua silenciosamente
            return false;
        }
    }
    
    /**
     * Lista todos os colaboradores ativos (adaptado para tabela Colaboradores)
     */
    public function findAllActive(): array {
        $sql = "
            SELECT 
                id,
                Nome as nome,
                UserName as usuario,
                Email as email,
                CPF,
                Cargo,
                Telefone,
                perfil,
                ultimo_acesso,
                criado_em
            FROM Colaboradores 
            WHERE ativo = 1
            ORDER BY Nome
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Valida se senha atende critérios de segurança
     */
    public function validatePassword(string $password): array {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Senha deve ter pelo menos 8 caracteres';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Senha deve conter pelo menos uma letra maiúscula';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Senha deve conter pelo menos uma letra minúscula';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Senha deve conter pelo menos um número';
        }
        
        return $errors;
    }
}
