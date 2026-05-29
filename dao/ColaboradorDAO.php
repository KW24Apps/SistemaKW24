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
    
    private function getBaseSelectQuery(): string {
        return "
            SELECT
                id, nome, username as usuario, senha, email,
                cpf, cargo, telefone, perfil, ativo, ultimo_acesso, tentativas_login,
                criado_em, atualizado_em
            FROM usuarios
        ";
    }

    public function findByUsername(string $username): ?array {
        $sql = $this->getBaseSelectQuery() . "WHERE username = :username AND ativo = TRUE LIMIT 1";
        return $this->db->fetchOne($sql, ['username' => $username]);
    }

    public function findById(int $id): ?array {
        $sql = $this->getBaseSelectQuery() . "WHERE id = :id AND ativo = TRUE LIMIT 1";
        return $this->db->fetchOne($sql, ['id' => $id]);
    }

    public function updateLastAccess(int $id): bool {
        $sql = "UPDATE usuarios SET ultimo_acesso = NOW(), tentativas_login = 0 WHERE id = :id";
        try {
            $this->db->execute($sql, ['id' => $id]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function incrementLoginAttempts(string $username): bool {
        $sql = "UPDATE usuarios SET tentativas_login = tentativas_login + 1 WHERE username = :username";
        try {
            $this->db->execute($sql, ['username' => $username]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    public function isBlocked(string $username): bool {
        $sql = "SELECT tentativas_login FROM usuarios WHERE username = :username LIMIT 1";
        $result = $this->db->fetchOne($sql, ['username' => $username]);
        if (!$result) return false;
        return $result['tentativas_login'] >= 5;
    }

    public function updatePassword(int $id, string $passwordHash): bool {
        $sql = "UPDATE usuarios SET senha = :password WHERE id = :id";
        try {
            $this->db->execute($sql, ['id' => $id, 'password' => $passwordHash]);
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
}
