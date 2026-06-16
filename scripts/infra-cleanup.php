<?php
/**
 * Limpeza: apaga cards em cat/284/ com título "Infra " criados pelo sync com bug.
 * Mantém os cards de automação (título sem prefixo "Infra ").
 * Uso: php scripts/infra-cleanup.php [--dry-run]
 */
define('SYSTEM_ACCESS', true);
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../dao/ConfiguracaoDAO.php';
require_once __DIR__ . '/../services/BitrixService.php';

$dryRun  = in_array('--dry-run', $argv);
$bitrix  = new BitrixService();
$deleted = 0;
$iter    = 0;

echo $dryRun ? "=== DRY RUN ===\n\n" : "=== LIMPEZA REAL ===\n\n";

do {
    $cards = $bitrix->listItems(1054, [
        'categoryId'         => 284,
        'stageId'            => 'DT1054_284:NEW',
        'ufCrm41_1742082168' => '06/2026',
    ], ['id', 'title'], 0);

    $toDelete = array_values(array_filter(
        $cards,
        fn($c) => str_starts_with($c['title'] ?? '', 'Infra ')
    ));

    printf("Iteração %d: %d cards NO stage, %d com prefixo 'Infra '\n",
        ++$iter, count($cards), count($toDelete));

    foreach ($toDelete as $c) {
        if ($dryRun) {
            printf("  WOULD DELETE id=%s  %s\n", $c['id'], $c['title']);
        } else {
            $ok = $bitrix->deleteItem(1054, (int)$c['id']);
            printf("  DELETE id=%s [%s]  %s\n", $c['id'], $ok ? 'OK' : 'ERRO', $c['title']);
            if ($ok) $deleted++;
        }
    }

    if (count($toDelete) === 0) break;
} while ($iter < 30);

printf("\nTotal deletados: %d\n", $deleted);
