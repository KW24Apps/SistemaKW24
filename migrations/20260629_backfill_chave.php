<?php
/**
 * Migration 1.4 + UNIQUE: backfill chave em cliente_aplicacoes
 * Executar via PHP runner após 20260629_organizacoes_org_id_chave.sql
 */
define('SYSTEM_ACCESS', true);
require_once __DIR__ . '/../helpers/Database.php';

$db  = Database::getInstance();
$pdo = $db->getConnection();

// Linhas que ainda não têm chave
$rows = $pdo->query("
    SELECT ca.id, c.chave_acesso
    FROM   cliente_aplicacoes ca
    JOIN   clientes c ON c.id = ca.cliente_id
    WHERE  ca.chave IS NULL
    ORDER  BY ca.id
")->fetchAll(PDO::FETCH_ASSOC);

echo "Linhas para backfill: " . count($rows) . "\n";

// Carrega chaves já existentes para evitar colisão
$existing = $pdo->query("SELECT chave FROM cliente_aplicacoes WHERE chave IS NOT NULL")
               ->fetchAll(PDO::FETCH_COLUMN);
$usados = array_fill_keys($existing, true);

$chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
$stmt  = $pdo->prepare("UPDATE cliente_aplicacoes SET chave = :chave WHERE id = :id");

$updated = 0;
foreach ($rows as $row) {
    $base     = $row['chave_acesso'];
    $attempts = 0;
    do {
        $suffix = '';
        for ($i = 0; $i < 5; $i++) {
            $suffix .= $chars[random_int(0, 35)];
        }
        $chave = $base . $suffix;
        $attempts++;
        if ($attempts > 200) {
            echo "ERRO: Não foi possível gerar chave única para ca.id={$row['id']} após 200 tentativas\n";
            exit(1);
        }
    } while (isset($usados[$chave]));

    $usados[$chave] = true;
    $stmt->execute(['chave' => $chave, 'id' => $row['id']]);
    echo "  ca.id={$row['id']}: chave definida (len=" . strlen($chave) . ")\n";
    $updated++;
}

echo "Backfill concluído: {$updated} linhas\n";

// Adiciona UNIQUE em chave (todas as linhas têm chave agora)
try {
    $pdo->exec("ALTER TABLE cliente_aplicacoes ADD CONSTRAINT cliente_aplicacoes_chave_key UNIQUE (chave)");
    echo "UNIQUE em chave: criado\n";
} catch (Exception $e) {
    // Pode já existir se rodado duas vezes
    echo "UNIQUE em chave: " . $e->getMessage() . "\n";
}

echo "Concluído.\n";
