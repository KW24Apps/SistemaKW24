<?php
require_once __DIR__ . '/../helpers/Database.php';

class ConfiguracaoDAO {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function get(string $chave): ?string {
        $row = $this->db->fetchOne(
            "SELECT valor FROM configuracoes_sistema WHERE chave = :chave",
            ['chave' => $chave]
        );
        return $row ? $row['valor'] : null;
    }

    public function set(string $chave, string $valor): void {
        $this->db->execute("
            INSERT INTO configuracoes_sistema (chave, valor, atualizado_em)
            VALUES (:chave, :valor, NOW())
            ON CONFLICT (chave) DO UPDATE SET valor = :valor2, atualizado_em = NOW()
        ", ['chave' => $chave, 'valor' => $valor, 'valor2' => $valor]);
    }

    public function getAll(): array {
        $rows = $this->db->fetchAll("SELECT chave, valor FROM configuracoes_sistema ORDER BY chave");
        $result = [];
        foreach ($rows as $row) {
            $result[$row['chave']] = $row['valor'];
        }
        return $result;
    }
}
