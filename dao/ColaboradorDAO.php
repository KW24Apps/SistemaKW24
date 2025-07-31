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
                tentativas_login,
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
     * Incrementa tentativas de login (adaptado para tabela Colaboradores)
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
        // Se já é um hash, usa direto; senão, faz hash
        $hashedPassword = (strlen($newPassword) > 60 && str_contains($newPassword, '$')) 
            ? $newPassword  // Já é hash Argon2ID
            : password_hash($newPassword, $this->config['security']['password_algorithm']);
        
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
                ativo,
                ultimo_acesso,
                tentativas_login,
                criado_em,
                atualizado_em
            FROM Colaboradores 
            WHERE ativo = 1
            ORDER BY Nome ASC
        ";
        
        return $this->db->fetchAll($sql);
    }
    
    /**
     * Atualiza dados do colaborador (adaptado para tabela Colaboradores)
     */
    public function update(int $id, array $data): bool {
        $allowedFields = ['Nome', 'Email', 'CPF', 'Cargo', 'Telefone', 'perfil'];
        
        $setFields = [];
        $params = ['id' => $id];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $setFields[] = "{$field} = :{$field}";
                $params[$field] = $value;
            }
        }
        
        if (empty($setFields)) {
            return false;
        }
        
        $sql = "
            UPDATE Colaboradores 
            SET " . implode(', ', $setFields) . ",
                atualizado_em = NOW()
            WHERE id = :id
        ";
        
        try {
            $this->db->execute($sql, $params);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Desativa colaborador (soft delete - adaptado para tabela Colaboradores)
     */
    public function deactivate(int $id): bool {
        $sql = "
            UPDATE Colaboradores 
            SET 
                ativo = 0,
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
     * Busca colaboradores com filtros (adaptado para tabela Colaboradores)
     */
    public function search(array $filters = []): array {
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
                tentativas_login,
                criado_em,
                atualizado_em
            FROM Colaboradores 
            WHERE 1=1
        ";
        
        $params = [];
        
        if (!empty($filters['nome'])) {
            $sql .= " AND Nome LIKE :nome";
            $params['nome'] = '%' . $filters['nome'] . '%';
        }
        
        if (!empty($filters['email'])) {
            $sql .= " AND Email LIKE :email";
            $params['email'] = '%' . $filters['email'] . '%';
        }
        
        if (!empty($filters['perfil'])) {
            $sql .= " AND perfil = :perfil";
            $params['perfil'] = $filters['perfil'];
        }
        
        if (isset($filters['ativo'])) {
            $sql .= " AND ativo = :ativo";
            $params['ativo'] = $filters['ativo'];
        }
        
        $sql .= " ORDER BY Nome ASC";
        
        return $this->db->fetchAll($sql, $params);
    }
}
