<?php
/**
 * Script de limpeza pontual: remove cards financeiros duplicados de 06/2026
 * criados pelo bug do title-based lookup, e define F_CONTROLE nos cards corretos.
 *
 * Execução: php /var/www/app.kw24.com.br/scripts/financeiro-cleanup.php
 * Idempotente: pode ser rodado mais de uma vez com segurança.
 */
define('SYSTEM_ACCESS', true);
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../dao/ConfiguracaoDAO.php';
require_once __DIR__ . '/../services/BitrixService.php';

$BX_ENTITY_TYPE = 1054;
$BX_CAT_FINANC  = 210;
$F_CONTROLE     = 'ufCrm41_1742082168';

$dao = new ConfiguracaoDAO();
if (!($dao->get('financeiro_dia_inicio'))) {
    echo "ERRO: tabela configuracoes_sistema não encontrada ou vazia.\n";
    exit(1);
}

$bitrix = new BitrixService();
if (!$bitrix->isConfigured()) {
    echo "ERRO: webhook Bitrix24 não configurado.\n";
    exit(1);
}

// Período afetado pelos cards duplicados
$PERIODO_REF = '06/2026';

echo "=== Cleanup de cards duplicados — {$PERIODO_REF} ===\n\n";

// 1. Buscar todos os cards da categoria 210 com o período no título
//    (title ainda é confiável para identificar os duplicados — ambas as rodadas criaram mesmo título)
$all = $bitrix->listItems($BX_ENTITY_TYPE, [
    'categoryId' => $BX_CAT_FINANC,
    '%title'     => $PERIODO_REF,
], ['id', 'title', 'companyId', $F_CONTROLE]);

echo "Cards encontrados (título LIKE '{$PERIODO_REF}'): " . count($all) . "\n\n";

if (empty($all)) {
    echo "Nenhum card encontrado. Nada a fazer.\n";
    exit(0);
}

// 2. Agrupar por empresa
$byCompany = [];
foreach ($all as $c) {
    $cid = (int)($c['companyId'] ?? 0);
    if (!$cid) {
        echo "AVISO: card id={$c['id']} sem companyId — ignorado\n";
        continue;
    }
    $byCompany[$cid][] = $c;
}

$totalDeletados  = 0;
$totalAtualizados = 0;
$idsDeleteados   = [];

// 3. Para cada empresa: manter o card com menor ID, deletar o resto
foreach ($byCompany as $cid => $cards) {
    usort($cards, fn($a, $b) => (int)$a['id'] - (int)$b['id']);

    $keeper   = $cards[0];
    $keeperId = (int)$keeper['id'];

    echo "Empresa {$cid} — {$keeperId} (keeper) ";
    if (count($cards) > 1) {
        $dupIds = array_map(fn($c) => (int)$c['id'], array_slice($cards, 1));
        echo "| duplicatas: " . implode(', ', $dupIds) . "\n";
    } else {
        echo "| sem duplicatas\n";
    }

    // Deletar duplicatas
    for ($i = 1; $i < count($cards); $i++) {
        $dupId = (int)$cards[$i]['id'];
        $ok    = $bitrix->deleteItem($BX_ENTITY_TYPE, $dupId);
        echo "  " . ($ok ? "DEL OK" : "DEL ERRO") . ": id={$dupId}\n";
        if ($ok) {
            $totalDeletados++;
            $idsDeleteados[] = $dupId;
        }
    }

    // Definir F_CONTROLE no keeper (se ainda não estiver definido)
    $controleAtual = $keeper[$F_CONTROLE] ?? '';
    if ($controleAtual !== $PERIODO_REF) {
        $ok = $bitrix->updateItem($BX_ENTITY_TYPE, $keeperId, [$F_CONTROLE => $PERIODO_REF]);
        echo "  " . ($ok ? "UPD OK" : "UPD ERRO") . ": id={$keeperId} {$F_CONTROLE}={$PERIODO_REF}\n";
        if ($ok) $totalAtualizados++;
    } else {
        echo "  OK: id={$keeperId} {$F_CONTROLE} já definido\n";
    }
}

echo "\n=== Resumo ===\n";
echo "Deletados:  {$totalDeletados}";
if ($idsDeleteados) echo " (IDs: " . implode(', ', $idsDeleteados) . ")";
echo "\nAtualizados: {$totalAtualizados}\n";
