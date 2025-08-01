<?php
/**
 * COLABORADOR DAO - KW24 APPS V2
 */

require_once __DIR__ . '/../helpers/Database.php';

class ColaboradorDAO {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    // Query base para seleção de colaborador
    private function getBaseSelectQuery(): string {
        return "
            SELECT 
                id, Nome as nome, UserName as usuario, senha, Email as email,
                CPF, Cargo, Telefone, perfil, ativo, ultimo_acesso, tentativas_login,
                criado_em, atualizado_em
            FROM Colaboradores 
        ";
    }
    
    public function findByUsername(string $username): ?array {
        $sql = $this->getBaseSelectQuery() . "WHERE UserName = :username AND ativo = 1 LIMIT 1";
        return $this->db->fetchOne($sql, ['username' => $username]);
    }
    
    public function findById(int $id): ?array {
        $sql = $this->getBaseSelectQuery() . "WHERE id = :id AND ativo = 1 LIMIT 1";
        return $this->db->fetchOne($sql, ['id' => $id]);
    }
    
    /**
     * Atualiza último acesso
     */
    public function updateLastAccess(int $id): bool {
        $sql = "
            UPDATE Colaboradores 
            SET ultimo_acesso = NOW(), tentativas_login = 0, atualizado_em = NOW()
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
     * Incrementa tentativas
     */
    public function incrementLoginAttempts(string $username): bool {
        $sql = "
            UPDATE Colaboradores 
            SET tentativas_login = tentativas_login + 1, atualizado_em = NOW()
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
     * Verifica se está bloqueado
     */
    public function isBlocked(string $username): bool {
        $sql = "SELECT tentativas_login FROM Colaboradores WHERE UserName = :username LIMIT 1";
        $result = $this->db->fetchOne($sql, ['username' => $username]);
        
        if (!$result) {
            return false;
        }
        
        return $result['tentativas_login'] >= 5;
    }
    
    /**
     * Atualiza senha
     */
    public function updatePassword(int $id, string $passwordHash): bool {
        $sql = "
            UPDATE Colaboradores 
            SET senha = :password, atualizado_em = NOW()
            WHERE id = :id
        ";
        
        try {
            $this->db->execute($sql, [
                'id' => $id,
                'password' => $passwordHash
            ]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
