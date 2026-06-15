<?php
/**
 * Discovery: stages de cat/284/ + amostra de source cards (SPA 1130 / cat 282).
 * Uso: php scripts/discover-infra-stages.php
 */
define('SYSTEM_ACCESS', true);
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../dao/ConfiguracaoDAO.php';
require_once __DIR__ . '/../services/BitrixService.php';

$bitrix = new BitrixService();

// ── 1. Stages para cat/284/ ──────────────────────────────────────────────────
echo "=== crm.status.list — DYNAMIC_1054_STAGE_284 ===\n\n";
$result = $bitrix->call('crm.status.list', ['filter' => ['ENTITY_ID' => 'DYNAMIC_1054_STAGE_284']]);
if (!$result) {
    echo "  Nenhum resultado (ou erro de API)\n\n";
} else {
    // crm.status.list retorna array diretamente
    $stages = is_array($result) ? $result : [];
    foreach ($stages as $s) {
        printf("  STATUS_ID=%-35s SORT=%-5s NAME=%s\n",
            $s['STATUS_ID'] ?? '?', $s['SORT'] ?? '?', $s['NAME'] ?? '?');
    }
    echo "\n";
}

// ── 2. Amostra de source cards SPA 1130 / cat 282 ────────────────────────────
echo "=== SPA 1130 / cat 282 — primeiros 10 cards ===\n\n";
$srcCards = $bitrix->listItems(1130, ['categoryId' => 282], [
    'id', 'title', 'companyId', 'opportunity', 'stageId',
    'ufCrm66_1773322225', // Produto Contratado
    'ufCrm66_1773325912', // Departamento
    'ufCrm66_1773337978', // Horas Dev
    'ufCrm66_1773338012', // Horas Suporte
    'ufCrm66_1773337676', // Valor Hora Dev (money)
    'ufCrm66_1773337956', // Valor Hora Suporte (money)
    'ufCrm66_1773340437', // Domínios
    'ufCrm66_1773350132', // Qtd Usuários RDP
], 10);

echo "Total retornado: " . count($srcCards) . "\n\n";
foreach (array_slice($srcCards, 0, 10) as $c) {
    printf("  id=%-6s cid=%-6s opp=%-12s produto=%-6s depto=%-6s\n",
        $c['id'], $c['companyId'] ?? '?',
        $c['opportunity'] ?? '0',
        $c['ufCrm66_1773322225'] ?? '?',
        $c['ufCrm66_1773325912'] ?? '?'
    );
    printf("    hDev=%-6s hSup=%-6s vhDev=%-14s vhSup=%-14s rdp=%s\n",
        $c['ufCrm66_1773337978'] ?? '?',
        $c['ufCrm66_1773338012'] ?? '?',
        $c['ufCrm66_1773337676'] ?? '?',
        $c['ufCrm66_1773337956'] ?? '?',
        $c['ufCrm66_1773350132'] ?? '?'
    );
    $doms = $c['ufCrm66_1773340437'] ?? [];
    if ($doms) printf("    dominios=%s\n", implode(',', (array)$doms));
}

// ── 3. Cards existentes em cat/284/ ─────────────────────────────────────────
echo "\n=== SPA 1054 / cat 284 — cards existentes (max 20) ===\n\n";
$existing = $bitrix->listItems(1054, ['categoryId' => 284], [
    'id', 'title', 'companyId', 'stageId', 'ufCrm41_1742082168',
], 20);
echo "Total retornado: " . count($existing) . "\n";
foreach ($existing as $c) {
    printf("  id=%-6s cid=%-6s stage=%-30s controle=%s\n  title=%s\n",
        $c['id'], $c['companyId'] ?? '?',
        $c['stageId'] ?? '?',
        $c['ufCrm41_1742082168'] ?? '',
        $c['title'] ?? ''
    );
}
