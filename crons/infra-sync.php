<?php
/**
 * Cron: sincroniza produtos de infra (SPA 1130/cat 282 → SPA 1054/cat 284)
 * Uso: /usr/bin/php8.1 crons/infra-sync.php [YYYY-MM]
 */
define('SYSTEM_ACCESS', true);
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../dao/ConfiguracaoDAO.php';
require_once __DIR__ . '/../services/BitrixService.php';
require_once __DIR__ . '/../services/FinanceiroSync.php';

$period = $argv[1] ?? null;

$sync   = new FinanceiroSync();
$result = $sync->syncInfra($period);

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";

$exitCode = ($result['errors'] > 0 && $result['created'] === 0 && $result['skipped'] === 0) ? 1 : 0;
exit($exitCode);
