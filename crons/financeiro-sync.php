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
require_once __DIR__ . '/../helpers/SyncLock.php';

$inicio = date('Y-m-d H:i:s');
echo "[{$inicio}] Iniciando sync financeiro...\n";

if (!SyncLock::acquire()) {
    echo "[" . date('H:i:s') . "] sync already running, skipping\n";
    exit(0);
}

$exitCode = 0;
try {
    $sync = new FinanceiroSync();

    $result = $sync->run();
    echo "[{$result['periodo']}] Demandas: {$result['demandas_total']} demand. / {$result['empresas']} empresas";
    echo " | Atualizados: {$result['atualizados']} | Erros: {$result['erros']}\n";
    foreach ($result['log'] as $linha) {
        echo $linha . "\n";
    }

    $infra = $sync->syncInfra();
    echo "[{$infra['periodo']}] Infra: {$infra['total_source']} fonte / {$infra['created']} criados";
    echo " / {$infra['skipped']} ignorados | Erros: {$infra['errors']}\n";
    foreach ($infra['log'] as $linha) {
        echo $linha . "\n";
    }

    $financeiro = $sync->syncFinanceiro();
    echo "[{$financeiro['periodo']}] Financeiro: {$financeiro['empresas']} empresas";
    echo " | Atualizados: {$financeiro['updated']} | Criados: {$financeiro['created']} | Erros: {$financeiro['errors']}\n";
    foreach ($financeiro['log'] as $linha) {
        echo $linha . "\n";
    }

} catch (Exception $e) {
    echo "ERRO FATAL: " . $e->getMessage() . "\n";
    $exitCode = 1;
} finally {
    SyncLock::release();
}

echo "[" . date('H:i:s') . "] Concluído.\n";
exit($exitCode);
