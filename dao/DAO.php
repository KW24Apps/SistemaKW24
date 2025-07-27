<?php
// DAO genérico para consultas simples
require_once __DIR__ . '/../helpers/Database.php';

class DAO {
    protected $db;

    public function __construct() {
        $this->db = (new Database())->getConnection();
    }

    // Exemplo: buscar todos os registros de uma tabela
    public function getAll($table) {
        $sql = "SELECT * FROM `$table`";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }

    // Exemplo: buscar por id
    public function getById($table, $id) {
        $sql = "SELECT * FROM `$table` WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    // Outros métodos podem ser adicionados conforme necessidade
    // Buscar apenas campos específicos da tabela clientes
    public function getClientesCampos() {
        $sql = "SELECT id, nome, cnpj, link_bitrix, telefone, email FROM clientes";
        $stmt = $this->db->query($sql);
        return $stmt->fetchAll();
    }
}
