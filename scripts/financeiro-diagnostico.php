<?php
define('SYSTEM_ACCESS', true);
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../dao/ConfiguracaoDAO.php';
require_once __DIR__ . '/../services/BitrixService.php';

$bitrix = new BitrixService();
$F_CONTROLE    = 'ufCrm41_1742082168';
$F_COMPETENCIA = 'ufCrm41_1742081702';
$PERIODO_REF   = '06/2026';

echo "=== Diagnóstico de cards financeiros — {$PERIODO_REF} ===\n\n";

// 1. Buscar por F_CONTROLE = '06/2026' (novo lookup)
$byControle = $bitrix->listItems(1054, [
    'categoryId'  => 210,
    $F_CONTROLE   => $PERIODO_REF,
], ['id', 'title', 'companyId', 'stageId', $F_CONTROLE, $F_COMPETENCIA]);

echo "Por {$F_CONTROLE}='{$PERIODO_REF}': " . count($byControle) . " cards\n";
foreach ($byControle as $c) {
    printf("  id=%-6s cid=%-6s stage=%-30s controle=%s\n  title=%s\n",
        $c['id'], $c['companyId'] ?? '?', $c['stageId'] ?? '?', $c[$F_CONTROLE] ?? '',
        $c['title'] ?? ''
    );
}

// 2. Últimos 20 cards cat 210 (para ver estado atual)
echo "\nÚltimos 20 ids em cat 210:\n";
$all = $bitrix->listItems(1054, ['categoryId' => 210], ['id', 'title', 'companyId', 'stageId', $F_CONTROLE], 20);
usort($all, fn($a, $b) => (int)$b['id'] - (int)$a['id']);
foreach (array_slice($all, 0, 20) as $c) {
    printf("  id=%-6s cid=%-6s stage=%-25s controle=%-10s title=%s\n",
        $c['id'], $c['companyId'] ?? '?', $c['stageId'] ?? '?',
        $c[$F_CONTROLE] ?? '', substr($c['title'] ?? '', 0, 45)
    );
}

// 3. Empresas conhecidas do período 06/2026
$empresas = [156044, 267, 156040, 145452, 7865];
echo "\nCards em cat 210 para empresas do período 06/2026:\n";
foreach ($empresas as $cid) {
    $cards = $bitrix->listItems(1054, ['categoryId' => 210, 'companyId' => $cid],
        ['id', 'title', 'stageId', $F_CONTROLE], 50);
    usort($cards, fn($a, $b) => (int)$b['id'] - (int)$a['id']);
    echo "  Empresa {$cid}: " . count($cards) . " card(s)\n";
    foreach (array_slice($cards, 0, 5) as $c) {
        printf("    id=%-6s stage=%-25s controle=%-10s title=%s\n",
            $c['id'], $c['stageId'] ?? '?', $c[$F_CONTROLE] ?? '', substr($c['title'] ?? '', 0, 45)
        );
    }
}
