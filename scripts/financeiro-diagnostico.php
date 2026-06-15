<?php
define('SYSTEM_ACCESS', true);
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../dao/ConfiguracaoDAO.php';
require_once __DIR__ . '/../services/BitrixService.php';

$bitrix = new BitrixService();
$F_CONTROLE    = 'ufCrm41_1742082168';
$F_COMPETENCIA = 'ufCrm41_1742081702';

echo "=== Diagnóstico de cards financeiros ===\n\n";

// 1. Buscar por competência = 2026-06-01 (refDate setado na criação)
$byCompetencia = $bitrix->listItems(1054, [
    'categoryId'      => 210,
    $F_COMPETENCIA    => '2026-06-01',
], ['id', 'title', 'companyId', $F_CONTROLE, $F_COMPETENCIA]);

echo "Por {$F_COMPETENCIA}='2026-06-01': " . count($byCompetencia) . " cards\n";
foreach ($byCompetencia as $c) {
    printf("  id=%-6s cid=%-6s controle=%-10s title=%s\n",
        $c['id'],
        $c['companyId'] ?? '?',
        $c[$F_CONTROLE]    ?? '(vazio)',
        substr($c['title'] ?? '', 0, 60)
    );
}

// 2. Fetch direto dos 5 cards originais da primeira rodada
echo "\nFetch direto (ids originais):\n";
foreach ([54920, 54924, 54928, 54932, 54936] as $id) {
    $item = $bitrix->getItem(1054, $id);
    if ($item) {
        printf("  id=%-6d OK  cid=%-6s controle=%-10s title=%s\n",
            $id,
            $item['companyId'] ?? '?',
            $item[$F_CONTROLE]    ?? '(vazio)',
            substr($item['title'] ?? '', 0, 60)
        );
    } else {
        echo "  id={$id} NOT FOUND\n";
    }
}

// 3. Listar últimos 20 cards da categoria 210 (sem filtro extra)
echo "\nÚltimos cards cat 210 (sem filtro, max 20):\n";
$all = $bitrix->listItems(1054, ['categoryId' => 210], ['id', 'title', 'companyId', $F_CONTROLE, $F_COMPETENCIA], 20);
// Sort by id desc
usort($all, fn($a, $b) => (int)$b['id'] - (int)$a['id']);
foreach (array_slice($all, 0, 20) as $c) {
    printf("  id=%-6s cid=%-6s controle=%-10s comp=%-12s title=%s\n",
        $c['id'],
        $c['companyId'] ?? '?',
        $c[$F_CONTROLE]    ?? '',
        $c[$F_COMPETENCIA] ?? '',
        substr($c['title'] ?? '', 0, 50)
    );
}
