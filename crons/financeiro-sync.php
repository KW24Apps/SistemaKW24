<?php
/**
 * Cron: sincronização financeira 4×/dia (10h, 12h, 16h, 18h server time).
 *
 * Instalar no servidor:
 *   crontab -e
 *   0 10,12,16,18 * * * php /var/www/app.kw24.com.br/crons/financeiro-sync.php >> /var/log/kw24-financeiro-sync.log 2>&1
 *
 * Execução manual:
 *   php /var/www/app.kw24.com.br/crons/financeiro-sync.php
 */

define('SYSTEM_ACCESS', true);

require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../dao/ConfiguracaoDAO.php';
require_once __DIR__ . '/../services/BitrixService.php';
require_once __DIR__ . '/../services/FinanceiroSync.php';

$inicio = date('Y-m-d H:i:s');
echo "[{$inicio}] Iniciando sync financeiro...\n";

try {
    $sync   = new FinanceiroSync();
    $result = $sync->run();

    echo "[{$result['periodo']}] {$result['demandas_total']} demandas / {$result['empresas']} empresas\n";
    echo "Atualizados: {$result['atualizados']} | Erros: {$result['erros']}\n";
    foreach ($result['log'] as $linha) {
        echo $linha . "\n";
    }

} catch (Exception $e) {
    echo "ERRO FATAL: " . $e->getMessage() . "\n";
    exit(1);
}

echo "[" . date('H:i:s') . "] Concluído.\n";
