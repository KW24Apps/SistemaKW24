<?php
/**
 * TENANT DAO - Painel de Clientes SaaS
 * Operações de banco para a tabela tenants (MySQL)
 */

require_once __DIR__ . '/../helpers/Database.php';

class TenantDAO
{
    private $db;
    private string $encKey;

    public function __construct()
    {
        $this->db = Database::getInstance();

        $config = require __DIR__ . '/../config/config.php';
        $this->encKey = $config['encryption_key'] ?? 'default-key-change-this-in-prod!';

        $this->ensureTableExists();
    }

    // ─── Criação da tabela ────────────────────────────────────────────────────

    private function ensureTableExists(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS tenants (
                id            INT AUTO_INCREMENT PRIMARY KEY,
                nome          VARCHAR(255)  NOT NULL,
                plano         VARCHAR(50)   DEFAULT 'starter',
                status        VARCHAR(20)   DEFAULT 'trial',
                db_host       VARCHAR(255)  DEFAULT 'localhost',
                db_port       VARCHAR(10)   DEFAULT '5432',
                db_name       VARCHAR(255),
                db_user       VARCHAR(255),
                db_password   TEXT,
                criado_em     TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                atualizado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
        ");
    }

    // ─── CRUD ─────────────────────────────────────────────────────────────────

    public function findAll(): array
    {
        return $this->db->fetchAll("
            SELECT id, nome, plano, status, db_host, db_port, db_name, db_user,
                   criado_em, atualizado_em
            FROM tenants
            ORDER BY nome ASC
        ");
    }

    public function findById(int $id): ?array
    {
        $row = $this->db->fetchOne(
            "SELECT * FROM tenants WHERE id = :id LIMIT 1",
            ['id' => $id]
        );

        if ($row && !empty($row['db_password'])) {
            $row['db_password'] = $this->decrypt($row['db_password']);
        }

        return $row ?: null;
    }

    public function insert(array $data): int
    {
        $this->db->execute("
            INSERT INTO tenants (nome, plano, status, db_host, db_port, db_name, db_user, db_password)
            VALUES (:nome, :plano, :status, :db_host, :db_port, :db_name, :db_user, :db_password)
        ", [
            'nome'        => $data['nome'],
            'plano'       => $data['plano']       ?? 'starter',
            'status'      => $data['status']      ?? 'trial',
            'db_host'     => $data['db_host']     ?? 'localhost',
            'db_port'     => $data['db_port']     ?? '5432',
            'db_name'     => $data['db_name']     ?? '',
            'db_user'     => $data['db_user']     ?? '',
            'db_password' => $this->encrypt($data['db_password'] ?? ''),
        ]);

        return (int)$this->db->getLastInsertId();
    }

    public function update(int $id, array $data): void
    {
        // Se a senha foi enviada em branco, mantém a existente
        $passwordSql = '';
        $params = [
            'id'      => $id,
            'nome'    => $data['nome'],
            'plano'   => $data['plano']   ?? 'starter',
            'status'  => $data['status']  ?? 'ativo',
            'db_host' => $data['db_host'] ?? 'localhost',
            'db_port' => $data['db_port'] ?? '5432',
            'db_name' => $data['db_name'] ?? '',
            'db_user' => $data['db_user'] ?? '',
        ];

        if (!empty($data['db_password'])) {
            $passwordSql = ', db_password = :db_password';
            $params['db_password'] = $this->encrypt($data['db_password']);
        }

        $this->db->execute("
            UPDATE tenants
            SET nome = :nome, plano = :plano, status = :status,
                db_host = :db_host, db_port = :db_port,
                db_name = :db_name, db_user = :db_user
                {$passwordSql}
            WHERE id = :id
        ", $params);
    }

    public function delete(int $id): void
    {
        $this->db->execute("DELETE FROM tenants WHERE id = :id", ['id' => $id]);
    }

    public function toggleStatus(int $id, string $status): void
    {
        $this->db->execute(
            "UPDATE tenants SET status = :status WHERE id = :id",
            ['id' => $id, 'status' => $status]
        );
    }

    // ─── Criptografia ─────────────────────────────────────────────────────────

    private function encrypt(string $value): string
    {
        if (empty($value)) return '';
        $iv        = random_bytes(16);
        $encrypted = openssl_encrypt($value, 'AES-256-CBC', $this->encKey, 0, $iv);
        return base64_encode($iv . $encrypted);
    }

    private function decrypt(string $value): string
    {
        if (empty($value)) return '';
        $data      = base64_decode($value);
        $iv        = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $this->encKey, 0, $iv) ?: '';
    }
}
