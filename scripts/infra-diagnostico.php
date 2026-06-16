<?php
/**
 * Diagnóstico: verifica se F_CONTROLE está sendo gravado nos cards criados por syncInfra.
 * Verifica também quantos cards existem em cat/284/ para 06/2026.
 */
define('SYSTEM_ACCESS', true);
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../dao/ConfiguracaoDAO.php';
require_once __DIR__ . '/../services/BitrixService.php';

$bitrix = new BitrixService();

// ── 1. Verificar card específico criado pelo sync ────────────────────────────
echo "=== Card 55018 (criado na 1a rodada) ===\n";
$item = $bitrix->getItem(1054, 55018);
if ($item) {
    printf("  stageId   : %s\n", $item['stageId'] ?? '(nulo)');
    printf("  F_CONTROLE: %s\n", $item['ufCrm41_1742082168'] ?? '(nulo)');
    printf("  I_PRODUTO : %s\n", $item['ufCrm41_1773942147'] ?? '(nulo)');
    printf("  I_DEPTO   : %s\n", $item['ufCrm41_1737476922'] ?? '(nulo)');
    printf("  companyId : %s\n", $item['companyId'] ?? '(nulo)');
    printf("  title     : %s\n", $item['title'] ?? '(nulo)');
} else {
    echo "  CARD NÃO ENCONTRADO\n";
}

// ── 2. Contar cards cat/284/ via F_CONTROLE filter ───────────────────────────
echo "\n=== listItems cat/284/ com F_CONTROLE=06/2026 ===\n";
$filtered = $bitrix->listItems(1054, [
    'categoryId'          => 284,
    'ufCrm41_1742082168'  => '06/2026',
], ['id', 'ufCrm41_1742082168'], 0);
printf("  Total: %d\n", count($filtered));
foreach (array_slice($filtered, 0, 5) as $c) {
    printf("  id=%-6s controle=%s\n", $c['id'], $c['ufCrm41_1742082168'] ?? '?');
}

// ── 3. Contar TODOS cat/284/ cards (sem filtro) ───────────────────────────────
echo "\n=== listItems cat/284/ sem filtro ===\n";
$all = $bitrix->listItems(1054, ['categoryId' => 284], ['id', 'ufCrm41_1742082168'], 0);
printf("  Total: %d\n", count($all));
$byControle = [];
foreach ($all as $c) {
    $ctrl = $c['ufCrm41_1742082168'] ?? '';
    $byControle[$ctrl] = ($byControle[$ctrl] ?? 0) + 1;
}
arsort($byControle);
foreach ($byControle as $ctrl => $cnt) {
    printf("  controle=%-10s count=%d\n", $ctrl ?: '(vazio)', $cnt);
}

// ── 4. Verificar total via raw call (para ver next/total) ────────────────────
echo "\n=== raw crm.item.list total/next ===\n";
$raw = $bitrix->call('crm.item.list', [
    'entityTypeId' => 1054,
    'filter'       => ['categoryId' => 284, 'ufCrm41_1742082168' => '06/2026'],
    'select'       => ['id'],
    'start'        => 0,
]);
printf("  total=%s  next=%s  items_returned=%d\n",
    $raw['total']  ?? '(ausente)',
    $raw['next']   ?? '(ausente)',
    count($raw['items'] ?? [])
);

// ── 4b. Verificar um card de automação (primeiro 51xxx/52xxx) ────────────────
$firstAuto = $raw['items'][0] ?? null;
if ($firstAuto) {
    echo "\n=== Card automação id={$firstAuto['id']} — fields ===\n";
    $auto = $bitrix->getItem(1054, (int)$firstAuto['id']);
    if ($auto) {
        printf("  stageId   : %s\n", $auto['stageId'] ?? '(nulo)');
        printf("  F_CONTROLE: %s\n", $auto['ufCrm41_1742082168'] ?? '(nulo)');
        printf("  I_PRODUTO : %s\n", $auto['ufCrm41_1773942147'] ?? '(nulo)');
        printf("  I_DEPTO   : %s\n", $auto['ufCrm41_1737476922'] ?? '(nulo)');
        printf("  companyId : %s\n", $auto['companyId'] ?? '(nulo)');
        printf("  title     : %s\n", $auto['title'] ?? '(nulo)');
    }
}

// ── 5. Filtrar por stageId NEW ────────────────────────────────────────────────
echo "\n=== listItems cat/284/ stageId=DT1054_284:NEW + F_CONTROLE=06/2026 ===\n";
$newCards = $bitrix->listItems(1054, [
    'categoryId'         => 284,
    'stageId'            => 'DT1054_284:NEW',
    'ufCrm41_1742082168' => '06/2026',
], ['id', 'ufCrm41_1742082168'], 0);
printf("  Total: %d\n", count($newCards));
foreach (array_slice($newCards, 0, 5) as $c) {
    printf("  id=%-6s controle=%s\n", $c['id'], $c['ufCrm41_1742082168'] ?? '?');
}
