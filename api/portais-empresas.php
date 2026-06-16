<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../services/BitrixService.php';

header('Content-Type: application/json');

$auth = new AuthenticationService();
if (!$auth->validateSession()) {
    http_response_code(401);
    echo json_encode(['empresas' => []]);
    exit;
}
$user = $auth->getCurrentUser();
if (($user['perfil'] ?? '') !== 'admin_interno') {
    http_response_code(403);
    echo json_encode(['empresas' => []]);
    exit;
}

try {
    $bitrix = new BitrixService();
    if (!$bitrix->isConfigured()) {
        echo json_encode(['empresas' => []]);
        exit;
    }

    // Busca faturas da cat/210 (SPA 1054) com companyId e período
    $items = $bitrix->listItems(
        1054,
        ['categoryId' => 210],
        ['id', 'companyId', 'ufCrm41_1742082168'],
        0
    );

    // Coleta companyIds únicos com período >= 06/2026 (comparação PHP-side para MM/YYYY)
    $companyIds = [];
    foreach ($items as $item) {
        $cid    = (int)($item['companyId'] ?? 0);
        $period = trim((string)($item['ufCrm41_1742082168'] ?? ''));
        if (!$cid) continue;
        if ($period !== '' && preg_match('/^(\d{2})\/(\d{4})$/', $period, $m)) {
            // Converte para YYYYMM para comparação numérica correta
            if ((int)($m[2] . $m[1]) < 202606) continue;
        }
        $companyIds[$cid] = true;
    }
    $companyIds = array_keys($companyIds);

    if (!$companyIds) {
        echo json_encode(['empresas' => []]);
        exit;
    }

    // Resolve nomes via batch (mesmo padrão de rlBatchCompanyNames)
    $empresas = [];
    foreach (array_chunk($companyIds, 50) as $chunk) {
        $cmd = [];
        foreach ($chunk as $i => $cid) {
            $cmd["co{$i}"] = 'crm.company.get?' . http_build_query(['id' => $cid], '', '&', PHP_QUERY_RFC3986);
        }
        $resp = $bitrix->call('batch', ['halt' => 0, 'cmd' => $cmd]);
        foreach ($chunk as $i => $cid) {
            $co = ($resp['result'] ?? [])["co{$i}"] ?? null;
            if ($co && !empty($co['TITLE'])) {
                $empresas[] = ['id' => $cid, 'name' => $co['TITLE']];
            }
        }
    }

    usort($empresas, fn($a, $b) => strcmp($a['name'], $b['name']));
    echo json_encode(['empresas' => $empresas]);

} catch (Throwable $e) {
    error_log('[portais-empresas] ' . $e->getMessage());
    echo json_encode(['empresas' => []]);
}
