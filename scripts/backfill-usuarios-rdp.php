<?php
/**
 * Backfill: copia ufCrm66_1773344477 (Usuários RDP, cat/282/) para
 * ufCrm41_1773467182 (Usuários RDP, cat/284/) nos cards existentes
 * do período atual que ainda não têm o campo preenchido.
 *
 * Uso: php8.1 scripts/backfill-usuarios-rdp.php [--dry-run] [--period=YYYY-MM]
 */
define('SYSTEM_ACCESS', true);
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../dao/ConfiguracaoDAO.php';
require_once __DIR__ . '/../services/BitrixService.php';

$dryRun = in_array('--dry-run', $argv);
$period = null;
foreach ($argv as $arg) {
    if (preg_match('/^--period=(\d{4}-\d{2})$/', $arg, $m)) {
        $period = $m[1];
    }
}

// Calcular período de referência (MM/YYYY)
if ($period !== null && preg_match('/^(\d{4})-(\d{2})$/', $period, $m)) {
    $referencia = sprintf('%02d/%04d', (int)$m[2], (int)$m[1]);
} else {
    $referencia = date('m/Y');
}

$bitrix  = new BitrixService();
$updated = 0;
$skipped = 0;
$errors  = 0;

$I_PRODUTO_ORIG  = 'ufCrm41_1781576165'; // Produto Origem (link SPA 1130)
$I_USUARIOS_RDP  = 'ufCrm41_1773467182'; // Usuários RDP destino (employee[])
$S_USUARIOS_RDP  = 'ufCrm66_1773344477'; // Usuários RDP fonte (employee[])
$F_CONTROLE      = 'ufCrm41_1742082168'; // Controle de Fatura #
$STAGE_NEW       = 'DT1054_284:NEW';

printf("%s\nPeríodo: %s\n\n", $dryRun ? '=== DRY RUN ===' : '=== BACKFILL REAL ===', $referencia);

// 1. Buscar cards cat/284/ NEW do período com I_PRODUTO_ORIG
$destCards = $bitrix->listItems(1054, [
    'categoryId'  => 284,
    'stageId'     => $STAGE_NEW,
    $F_CONTROLE   => $referencia,
], ['id', $I_PRODUTO_ORIG, $I_USUARIOS_RDP], 0);

printf("Cards em cat/284/ (NEW, %s): %d\n", $referencia, count($destCards));

// Filtrar apenas os criados pelo sync (com I_PRODUTO_ORIG) e sem Usuários RDP
$toUpdate = [];
foreach ($destCards as $c) {
    $srcId = (int)($c[$I_PRODUTO_ORIG] ?? 0);
    // parseCrmLinkId inline: suporta int, "D1130_123", array
    if ($srcId === 0 && !empty($c[$I_PRODUTO_ORIG])) {
        $raw = is_array($c[$I_PRODUTO_ORIG]) ? ($c[$I_PRODUTO_ORIG][0] ?? '') : $c[$I_PRODUTO_ORIG];
        preg_match('/(\d+)$/', (string)$raw, $mx);
        $srcId = (int)($mx[1] ?? 0);
    }
    if ($srcId === 0) continue; // card de automação — ignorar

    $existing = $c[$I_USUARIOS_RDP] ?? [];
    if (!empty($existing)) {
        $skipped++;
        continue; // já tem o campo preenchido
    }

    $toUpdate[] = ['destId' => (int)$c['id'], 'srcId' => $srcId];
}

printf("Com I_PRODUTO_ORIG: %d | Já preenchidos (skip): %d | A atualizar: %d\n\n",
    count($toUpdate) + $skipped, $skipped, count($toUpdate));

if (empty($toUpdate)) {
    echo "Nada a fazer.\n";
    exit(0);
}

// 2. Batch fetch source cards de cat/282/
$srcIds = array_unique(array_column($toUpdate, 'srcId'));
$srcCache = [];

foreach (array_chunk($srcIds, 50) as $chunk) {
    $cmd = [];
    foreach ($chunk as $i => $sid) {
        $cmd["s{$i}"] = 'crm.item.get?' . http_build_query(
            ['entityTypeId' => 1130, 'id' => $sid],
            '', '&', PHP_QUERY_RFC3986
        );
    }
    $resp    = $bitrix->call('batch', ['halt' => 0, 'cmd' => $cmd]);
    $results = $resp['result'] ?? [];
    foreach ($chunk as $i => $sid) {
        $item = $results["s{$i}"]['item'] ?? null;
        if ($item) {
            $srcCache[$sid] = $item[$S_USUARIOS_RDP] ?? [];
        }
    }
}
printf("Source cards carregados: %d\n\n", count($srcCache));

// 3. Atualizar cards destino
foreach ($toUpdate as $entry) {
    $destId  = $entry['destId'];
    $srcId   = $entry['srcId'];
    $usuarios = $srcCache[$srcId] ?? [];

    if (empty($usuarios)) {
        printf("  SKIP (vazio na fonte): dest=%d src=%d\n", $destId, $srcId);
        $skipped++;
        continue;
    }

    if ($dryRun) {
        printf("  WOULD UPDATE dest=%d src=%d usuarios=%s\n",
            $destId, $srcId, json_encode($usuarios));
        $updated++;
    } else {
        $ok = $bitrix->updateItem(1054, $destId, [$I_USUARIOS_RDP => $usuarios]);
        printf("  %s dest=%d src=%d usuarios=%s\n",
            $ok ? 'UPDATED' : 'ERRO', $destId, $srcId, json_encode($usuarios));
        if ($ok) $updated++;
        else      $errors++;
        usleep(100000); // 100ms entre updates
    }
}

printf("\nTotal: updated=%d skipped=%d errors=%d\n", $updated, $skipped, $errors);
