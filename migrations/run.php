<?php
/**
 * CLI migration runner. Executar: php migrations/run.php
 * Aplica todas as migrations pendentes em ordem.
 */
define('SYSTEM_ACCESS', true);
require_once __DIR__ . '/../helpers/Database.php';

$migrations = [
    '001_configuracoes_sistema.sql',
    '20260702_mcp_clients.sql',
];

try {
    $db = Database::getInstance();
    echo "Conectado ao banco.\n";

    foreach ($migrations as $file) {
        $path = __DIR__ . '/' . $file;
        if (!file_exists($path)) {
            echo "SKIP: {$file} (arquivo não encontrado)\n";
            continue;
        }
        $sql = file_get_contents($path);
        $db->exec($sql);
        echo "OK:   {$file}\n";
    }

    $rows = $db->fetchAll('SELECT chave, valor FROM configuracoes_sistema ORDER BY chave');
    echo "\nTabela configuracoes_sistema:\n";
    foreach ($rows as $r) {
        $val = ($r['chave'] === 'financeiro_webhook_bitrix' && strlen($r['valor']) > 4) ? '(configured)' : ($r['valor'] ?: '(empty)');
        echo "  {$r['chave']} = {$val}\n";
    }
    echo "\nConcluído.\n";

} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage() . "\n";
    exit(1);
}
