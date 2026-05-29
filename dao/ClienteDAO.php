<?php
require_once __DIR__ . '/../helpers/Database.php';

class ClienteDAO {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function findAll(string $busca = ''): array {
        if ($busca) {
            return $this->db->fetchAll("
                SELECT id, nome, cnpj, telefone, email, chave_acesso, link_bitrix, ativo
                FROM clientes
                WHERE nome ILIKE :busca OR cnpj ILIKE :busca OR email ILIKE :busca
                ORDER BY nome ASC
            ", ['busca' => "%{$busca}%"]);
        }
        return $this->db->fetchAll("
            SELECT id, nome, cnpj, telefone, email, chave_acesso, link_bitrix, ativo
            FROM clientes
            ORDER BY nome ASC
        ");
    }

    public function findById(int $id): ?array {
        return $this->db->fetchOne("SELECT * FROM clientes WHERE id = :id", ['id' => $id]);
    }

    public function count(): int {
        $row = $this->db->fetchOne("SELECT COUNT(*) as total FROM clientes");
        return (int)($row['total'] ?? 0);
    }

    public function insert(array $data): int {
        $this->db->execute("
            INSERT INTO clientes (nome, cnpj, chave_acesso, link_bitrix, telefone, email, endereco, id_bitrix)
            VALUES (:nome, :cnpj, :chave_acesso, :link_bitrix, :telefone, :email, :endereco, :id_bitrix)
        ", [
            'nome'         => $data['nome'],
            'cnpj'         => $data['cnpj']         ?? null,
            'chave_acesso' => $data['chave_acesso'],
            'link_bitrix'  => $data['link_bitrix']  ?? null,
            'telefone'     => $data['telefone']      ?? null,
            'email'        => $data['email']         ?? null,
            'endereco'     => $data['endereco']      ?? null,
            'id_bitrix'    => $data['id_bitrix']     ?? null,
        ]);
        return (int)$this->db->getLastInsertId('clientes_id_seq');
    }

    public function update(int $id, array $data): void {
        $this->db->execute("
            UPDATE clientes SET
                nome         = :nome,
                cnpj         = :cnpj,
                chave_acesso = :chave_acesso,
                link_bitrix  = :link_bitrix,
                telefone     = :telefone,
                email        = :email,
                endereco     = :endereco,
                id_bitrix    = :id_bitrix
            WHERE id = :id
        ", array_merge($data, ['id' => $id]));
    }

    public function toggleAtivo(int $id, bool $ativo): void {
        $this->db->execute("UPDATE clientes SET ativo = :ativo WHERE id = :id", [
            'id'    => $id,
            'ativo' => $ativo,
        ]);
    }
}
