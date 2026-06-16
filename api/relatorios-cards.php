<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../dao/ConfiguracaoDAO.php';
require_once __DIR__ . '/../services/BitrixService.php';

header('Content-Type: application/json');

// Sessão de portal (cliente autenticado via portal-login.php)
$isPortalSession = !empty($_SESSION['portal_mode']) && !empty($_SESSION['portal_company_id']);

$auth = new AuthenticationService();
if (!$auth->validateSession() && !$isPortalSession) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autenticado']);
    exit;
}

// ── Entidades Bitrix ──────────────────────────────────────────────────────────
define('RL_ENTITY',      1054);
define('RL_CAT_FINANC',  210);
define('RL_CAT_DEMANDAS', 208);
define('RL_CAT_INFRA',   284);
define('RL_INFRA_STAGE', 'DT1054_284:NEW');

// Campos cat/210/
define('RL_CONTROLE',    'ufCrm41_1742082168'); // Período MM/YYYY (chave)
define('RL_TIPO_FAT',    'ufCrm41_1742299164'); // Tipo de Fatura (enum)
define('RL_VH_SUP',      'ufCrm41_1767928096'); // Valor/hora Suporte
define('RL_VH_DEV',      'ufCrm41_1767928073'); // Valor/hora Dev
define('RL_VCONT_SUP',   'ufCrm41_1773474286'); // Valor Contratado Suporte
define('RL_VCONT_DEV',   'ufCrm41_1773474261'); // Valor Contratado Dev
define('RL_VTOTAL_SUP',  'ufCrm41_1767901194'); // Valor Total Suporte
define('RL_VTOTAL_DEV',  'ufCrm41_1767901128'); // Valor Total Dev
define('RL_VTOTAL_INFRA','ufCrm41_1770316473'); // Valor Total Infra
define('RL_PROD284_LINK','ufCrm41_1773457133'); // Produtos Contratados (cat/284/ link)

// Campos cat/208/ (demandas)
define('RL_DATA_FIN',    'ufCrm41_1778777816');
define('RL_TIPO',        'ufCrm41_1737476320');
define('RL_TEMPO',       'ufCrm41_1751475675');
define('RL_DEPTO',       'ufCrm41_1737476922'); // Departamento (mesmo campo em cat/284/)

// Campos cat/284/ (infra execução)
define('RL_PRODUTO',     'ufCrm41_1773942147');

// Tipo de Fatura: Contrato Mensal
define('RL_FAT_CONTRATO', 21818);

// Tipos de chamado faturáveis
$TIPOS_FATURAVEL  = [21204, 21206, 21208, 21210];
$TIPO_LABELS      = [21204 => 'Suporte Bitrix24', 21206 => 'Suporte Técnico', 21208 => 'Dev - Melhoria', 21210 => 'Dev - Implementação'];
$TIPO_COL_MAP     = [21206 => 'suporteTI', 21204 => 'suporteB24', 21210 => 'devImpl', 21208 => 'devMelh'];
$IS_SUP_TYPE      = [21204 => true, 21206 => true, 21208 => false, 21210 => false];

// Cat/284/ produto enum → coluna infra
$PROD_COL_MAP = [
    28426 => 'rdp',         28428 => 'vm',          28430 => 'dados',
    28432 => 'sistemaDom',  28434 => 'hospedagem',  28436 => 'email',
    28438 => 'cnpj',        28440 => 'clicksign',   28442 => 'receita',
    28444 => 'whatsapp',
];
$INFRA_COLS = ['rdp','vm','dados','sistemaDom','hospedagem','email','cnpj','clicksign','receita','whatsapp'];

// Departamento enum (SPA 1054) → label
$DEPTO_LABELS = [
    21226 => 'Grupo Nimbus',        21234 => 'Nimbus Tax',
    21518 => 'GN - Financeiro',     21228 => 'GN - Controladoria',
    21230 => 'GN - Marketing',      21520 => 'GN - RH',
    21232 => 'GN - Núcleo de Produtos', 21240 => 'Capiton',
    21242 => 'BGA - Advocacia',     21244 => 'Altura Assessoria',
    21246 => 'Nimbus Privacy',      21538 => 'ContaFarma',
    21250 => 'Externo',
];

try {
    $dao       = new ConfiguracaoDAO();
    $diaInicio = max(1, min(28, (int)($dao->get('financeiro_dia_inicio') ?? 27)));

    $mesParam      = trim($_GET['mes']     ?? '');
    $filtroEmpresa = (int)($_GET['empresa'] ?? 0);
    $filtroDepto   = trim($_GET['depto']   ?? '');

    // Sessão de portal: força a empresa do portal, ignora GET['empresa']
    if ($isPortalSession) {
        $filtroEmpresa = (int)$_SESSION['portal_company_id'];
    }

    // Mês mínimo válido: 06/2026 (primeiro período de faturamento)
    if ($mesParam && preg_match('/^(\d{2})\/(\d{4})$/', $mesParam, $mm)) {
        $dtReq = new DateTime(sprintf('%04d-%02d-01', (int)$mm[2], (int)$mm[1]));
        if ($dtReq < new DateTime('2026-06-01')) {
            $mesParam = '06/2026';
        }
    }

    $periodo = rlCalcPeriodo($diaInicio, $mesParam);

    $bitrix = new BitrixService();
    if (!$bitrix->isConfigured()) {
        echo json_encode([
            'sucesso'  => false,
            'aviso'    => 'Webhook Bitrix24 não configurado',
            'periodo'  => $periodo,
            'kpis'     => ['total' => 0, 'suporte' => 0, 'dev' => 0, 'infra' => 0],
            'faturas'  => [], 'servicos' => [], 'infra' => [], 'demandas' => [],
            'mesesDisponiveis' => rlGerarMeses($diaInicio),
            'empresasDisponiveis' => [],
        ]);
        exit;
    }

    // ── 1. Buscar FATURAS (cat/210/) ─────────────────────────────────────────
    $faturaRaw = $bitrix->listItems(RL_ENTITY,
        ['categoryId' => RL_CAT_FINANC, RL_CONTROLE => $periodo['referencia']],
        ['id','title','companyId','opportunity', RL_CONTROLE,
         RL_TIPO_FAT, RL_VH_SUP, RL_VH_DEV, RL_VCONT_SUP, RL_VCONT_DEV,
         RL_VTOTAL_SUP, RL_VTOTAL_DEV, RL_VTOTAL_INFRA],
        0
    );

    // Indexar cat/210/ por companyId para lookup em SERVIÇOS
    $faturaByCompany = [];
    foreach ($faturaRaw as $f) {
        $cid = (int)($f['companyId'] ?? 0);
        if ($cid) $faturaByCompany[$cid] = $f;
    }

    // ── 2. Buscar INFRA (cat/284/) ───────────────────────────────────────────
    $infraRaw = $bitrix->listItems(RL_ENTITY,
        ['categoryId' => RL_CAT_INFRA, 'stageId' => RL_INFRA_STAGE, RL_CONTROLE => $periodo['referencia']],
        ['id','companyId','opportunity', RL_PRODUTO, RL_DEPTO],
        0
    );

    // ── 3. Buscar DEMANDAS faturáveis (cat/208/) ─────────────────────────────
    $inicioStr = $periodo['inicio'] . 'T00:00:00';
    $fimStr    = $periodo['fim']    . 'T23:59:59';

    $demandaRaw = $bitrix->listItems(RL_ENTITY,
        ['categoryId' => RL_CAT_DEMANDAS, '>=' . RL_DATA_FIN => $inicioStr, '<=' . RL_DATA_FIN => $fimStr],
        ['id','title','companyId', RL_TIPO, RL_TEMPO, RL_DEPTO, RL_DATA_FIN],
        0
    );
    $demandaRaw = array_values(array_filter($demandaRaw,
        fn($d) => in_array((int)($d[RL_TIPO] ?? 0), $TIPOS_FATURAVEL, true)
    ));

    // ── 4. Batch-fetch nomes de empresa ──────────────────────────────────────
    $allCids = [];
    foreach ([$faturaRaw, $infraRaw, $demandaRaw] as $list) {
        foreach ($list as $r) $allCids[] = (int)($r['companyId'] ?? 0);
    }
    $allCids      = array_values(array_unique(array_filter($allCids)));
    $companyNames = rlBatchCompanyNames($bitrix, $allCids);

    // ── 5. Build FATURAS ─────────────────────────────────────────────────────
    // Agrupar cat/284/ por empresa para o breakdown de departamentos
    $infraByCompany = [];
    foreach ($infraRaw as $ic) {
        $cid = (int)($ic['companyId'] ?? 0);
        if ($cid) $infraByCompany[$cid][] = $ic;
    }

    $faturas = [];
    foreach ($faturaRaw as $f) {
        $cid     = (int)($f['companyId'] ?? 0);
        $empresa = $companyNames[$cid] ?? "Empresa #{$cid}";

        if ($filtroEmpresa && $cid !== $filtroEmpresa) continue;

        $departamentos = [];
        if (!empty($infraByCompany[$cid])) {
            $deptoTotais = [];
            foreach ($infraByCompany[$cid] as $ic) {
                $did  = (int)($ic[RL_DEPTO] ?? 0);
                $nome = $DEPTO_LABELS[$did] ?? ($did ? "Dept {$did}" : 'Geral');
                $deptoTotais[$nome] = ($deptoTotais[$nome] ?? 0.0) + (float)($ic['opportunity'] ?? 0);
            }
            if (count($deptoTotais) > 1) {
                foreach ($deptoTotais as $nome => $tot) {
                    $departamentos[] = ['nome' => $nome, 'total' => round($tot, 2)];
                }
            }
        }

        $faturas[] = [
            'id'            => (int)$f['id'],
            'empresa'       => $empresa,
            'mesCobranca'   => $f[RL_CONTROLE] ?? $periodo['referencia'],
            'periodoInicio' => $periodo['inicio'],
            'periodoFim'    => $periodo['fim'],
            'total'         => round((float)($f['opportunity'] ?? 0), 2),
            'departamentos' => $departamentos,
        ];
    }
    usort($faturas, fn($a, $b) => strcmp($a['empresa'], $b['empresa']));

    // ── 6. Build SERVIÇOS ────────────────────────────────────────────────────
    // Agrupar demandas por empresa → departamento → tipo
    $svcAgg = []; // [cid][deptoName][colName] = minutos
    foreach ($demandaRaw as $d) {
        $cid  = (int)($d['companyId'] ?? 0);
        $tipo = (int)($d[RL_TIPO] ?? 0);
        $mins = (int)($d[RL_TEMPO] ?? 0);
        $did  = (int)($d[RL_DEPTO] ?? 0);
        $nome = $DEPTO_LABELS[$did] ?? ($did ? "Dept {$did}" : 'Geral');
        $col  = $TIPO_COL_MAP[$tipo] ?? null;

        if (!$col || !$cid) continue;

        if (!isset($svcAgg[$cid])) $svcAgg[$cid] = [];
        if (!isset($svcAgg[$cid][$nome])) {
            $svcAgg[$cid][$nome] = ['suporteTI' => 0, 'suporteB24' => 0, 'devImpl' => 0, 'devMelh' => 0];
        }
        $svcAgg[$cid][$nome][$col] += $mins;
    }

    $servicos = [];
    foreach ($svcAgg as $cid => $depts) {
        if ($filtroEmpresa && $cid !== $filtroEmpresa) continue;

        $empresa = $companyNames[$cid] ?? "Empresa #{$cid}";
        $fatCard = $faturaByCompany[$cid] ?? null;
        $vhSup   = rlMoney($fatCard[RL_VH_SUP]    ?? null);
        $vhDev   = rlMoney($fatCard[RL_VH_DEV]    ?? null);
        $vContSup = rlMoney($fatCard[RL_VCONT_SUP] ?? null);
        $vContDev = rlMoney($fatCard[RL_VCONT_DEV] ?? null);
        $tipoFat  = (int)($fatCard[RL_TIPO_FAT] ?? 0);
        $hasContract = ($tipoFat === RL_FAT_CONTRATO);

        $deptRows = [];
        $totals   = ['suporteTI' => 0.0, 'suporteB24' => 0.0, 'devImpl' => 0.0, 'devMelh' => 0.0, 'total' => 0.0];

        foreach ($depts as $deptoNome => $mins) {
            if ($filtroDepto && $deptoNome !== $filtroDepto) continue;

            $sti   = round(($mins['suporteTI']  / 60) * $vhSup, 2);
            $sb24  = round(($mins['suporteB24'] / 60) * $vhSup, 2);
            $dimpl = round(($mins['devImpl']    / 60) * $vhDev, 2);
            $dmelh = round(($mins['devMelh']    / 60) * $vhDev, 2);
            $dtot  = $sti + $sb24 + $dimpl + $dmelh;

            $deptRows[] = ['nome' => $deptoNome, 'suporteTI' => $sti, 'suporteB24' => $sb24, 'devImpl' => $dimpl, 'devMelh' => $dmelh, 'total' => round($dtot, 2)];
            $totals['suporteTI']  += $sti;
            $totals['suporteB24'] += $sb24;
            $totals['devImpl']    += $dimpl;
            $totals['devMelh']    += $dmelh;
            $totals['total']      += $dtot;
        }

        if (empty($deptRows)) continue;

        $totals = array_map(fn($v) => round($v, 2), $totals);

        $servicos[] = [
            'companyId'     => $cid,
            'empresa'       => $empresa,
            'multiploDepts' => count($depts) > 1 && !$filtroDepto,
            'depts'         => $deptRows,
            'total'         => $totals,
            'hasContract'   => $hasContract,
            'vContSup'      => $vContSup,
            'vContDev'      => $vContDev,
        ];
    }
    usort($servicos, fn($a, $b) => strcmp($a['empresa'], $b['empresa']));

    // ── 7. Build INFRA ───────────────────────────────────────────────────────
    $infraAgg = []; // [cid][deptoName][col] = valor
    foreach ($infraRaw as $ic) {
        $cid  = (int)($ic['companyId'] ?? 0);
        $prod = (int)($ic[RL_PRODUTO] ?? 0);
        $did  = (int)($ic[RL_DEPTO] ?? 0);
        $col  = $PROD_COL_MAP[$prod] ?? null;
        $nome = $DEPTO_LABELS[$did] ?? ($did ? "Dept {$did}" : 'Geral');

        if (!$col || !$cid) continue;
        if ($filtroEmpresa && $cid !== $filtroEmpresa) continue;
        if ($filtroDepto && $nome !== $filtroDepto) continue;

        if (!isset($infraAgg[$cid])) $infraAgg[$cid] = [];
        if (!isset($infraAgg[$cid][$nome])) {
            $infraAgg[$cid][$nome] = array_fill_keys($INFRA_COLS, 0.0);
            $infraAgg[$cid][$nome]['total'] = 0.0;
        }
        $val = (float)($ic['opportunity'] ?? 0);
        $infraAgg[$cid][$nome][$col]   += $val;
        $infraAgg[$cid][$nome]['total'] += $val;
    }

    $infra = [];
    foreach ($infraAgg as $cid => $depts) {
        $empresa = $companyNames[$cid] ?? "Empresa #{$cid}";
        $deptRows = [];
        $totals = array_fill_keys($INFRA_COLS, 0.0);
        $totals['total'] = 0.0;

        foreach ($depts as $nome => $vals) {
            $row = ['nome' => $nome];
            foreach ($INFRA_COLS as $col) {
                $row[$col] = round($vals[$col], 2);
                $totals[$col] += $vals[$col];
            }
            $row['total'] = round($vals['total'], 2);
            $totals['total'] += $vals['total'];
            $deptRows[] = $row;
        }
        $totals = array_map(fn($v) => round($v, 2), $totals);

        $infra[] = [
            'companyId'     => $cid,
            'empresa'       => $empresa,
            'multiploDepts' => count($depts) > 1 && !$filtroDepto,
            'depts'         => $deptRows,
            'total'         => $totals,
        ];
    }
    usort($infra, fn($a, $b) => strcmp($a['empresa'], $b['empresa']));

    // ── 8. Build DEMANDAS ────────────────────────────────────────────────────
    $demandas = [];
    foreach ($demandaRaw as $d) {
        $cid  = (int)($d['companyId'] ?? 0);
        $tipo = (int)($d[RL_TIPO] ?? 0);
        $did  = (int)($d[RL_DEPTO] ?? 0);
        $nome = $DEPTO_LABELS[$did] ?? ($did ? "Dept {$did}" : 'Geral');

        if ($filtroEmpresa && $cid !== $filtroEmpresa) continue;
        if ($filtroDepto && $nome !== $filtroDepto) continue;

        $demandas[] = [
            'id'           => (int)$d['id'],
            'nome'         => $d['title'] ?? '',
            'tipo'         => $TIPO_LABELS[$tipo] ?? "Tipo {$tipo}",
            'tipoId'       => $tipo,
            'departamento' => $nome,
            'solicitante'  => '', // UF code não identificado no codebase
            'tempoMinutos' => (int)($d[RL_TEMPO] ?? 0),
            'mesCobranca'  => $periodo['referencia'],
            'resumo'       => '', // UF code não identificado no codebase
        ];
    }
    usort($demandas, fn($a, $b) => $a['id'] <=> $b['id']);

    // ── 9. KPIs (dos cards cat/210/ — sem filtro de empresa no total geral) ──
    $kpis = ['total' => 0.0, 'suporte' => 0.0, 'dev' => 0.0, 'infra' => 0.0];
    foreach ($faturaRaw as $f) {
        $cid = (int)($f['companyId'] ?? 0);
        if ($filtroEmpresa && $cid !== $filtroEmpresa) continue;
        $kpis['total']   += (float)($f['opportunity'] ?? 0);
        $kpis['suporte'] += rlMoney($f[RL_VTOTAL_SUP]   ?? null);
        $kpis['dev']     += rlMoney($f[RL_VTOTAL_DEV]   ?? null);
        $kpis['infra']   += rlMoney($f[RL_VTOTAL_INFRA] ?? null);
    }
    $kpis = array_map(fn($v) => round($v, 2), $kpis);

    // ── 10. Listas para filtros ───────────────────────────────────────────────
    $empresasDisponiveis = [];
    foreach ($allCids as $cid) {
        $empresasDisponiveis[] = ['id' => $cid, 'nome' => $companyNames[$cid] ?? "Empresa #{$cid}"];
    }
    usort($empresasDisponiveis, fn($a, $b) => strcmp($a['nome'], $b['nome']));

    // Departamentos disponíveis (union de todos os datasets)
    $deptosSet = [];
    foreach ($demandaRaw as $d) {
        $did  = (int)($d[RL_DEPTO] ?? 0);
        $nome = $DEPTO_LABELS[$did] ?? ($did ? "Dept {$did}" : 'Geral');
        $deptosSet[$nome] = true;
    }
    foreach ($infraRaw as $ic) {
        $did  = (int)($ic[RL_DEPTO] ?? 0);
        $nome = $DEPTO_LABELS[$did] ?? ($did ? "Dept {$did}" : 'Geral');
        $deptosSet[$nome] = true;
    }
    $deptosDisponiveis = array_keys($deptosSet);
    sort($deptosDisponiveis);

    echo json_encode([
        'sucesso'              => true,
        'periodo'              => $periodo,
        'kpis'                 => $kpis,
        'faturas'              => $faturas,
        'servicos'             => $servicos,
        'infra'                => $infra,
        'demandas'             => $demandas,
        'mesesDisponiveis'     => rlGerarMeses($diaInicio),
        'empresasDisponiveis'  => $empresasDisponiveis,
        'deptosDisponiveis'    => $deptosDisponiveis,
        'avisoSolicitante'     => 'Campos Solicitante e Resumo de cat/208/ não mapeados (UF codes não encontrados no codebase)',
    ]);

} catch (Exception $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}

// ── Helpers ──────────────────────────────────────────────────────────────────

function rlMoney(mixed $val): float {
    if ($val === null || $val === '' || $val === false) return 0.0;
    $s = is_array($val) ? (string)($val[0] ?? '') : (string)$val;
    return round((float)explode('|', $s)[0], 2);
}

function rlCalcPeriodo(int $diaInicio, string $mesParam = ''): array {
    if ($mesParam && preg_match('/^(\d{2})\/(\d{4})$/', $mesParam, $m)) {
        $refMes = (int)$m[1];
        $refAno = (int)$m[2];
        $fim    = new DateTime(sprintf('%04d-%02d-%02d', $refAno, $refMes, $diaInicio - 1));
        $inicioMes = $refMes - 1; $inicioAno = $refAno;
        if ($inicioMes < 1) { $inicioMes = 12; $inicioAno--; }
        $inicio = new DateTime(sprintf('%04d-%02d-%02d', $inicioAno, $inicioMes, $diaInicio));
        return [
            'referencia' => sprintf('%02d/%04d', $refMes, $refAno),
            'inicio'     => $inicio->format('Y-m-d'),
            'fim'        => $fim->format('Y-m-d'),
        ];
    }

    $hoje = new DateTime();
    $dia  = (int)$hoje->format('d');
    $mes  = (int)$hoje->format('m');
    $ano  = (int)$hoje->format('Y');
    if ($dia >= $diaInicio) {
        $inicioMes = $mes; $inicioAno = $ano;
    } else {
        $inicioMes = $mes - 1; $inicioAno = $ano;
        if ($inicioMes < 1) { $inicioMes = 12; $inicioAno--; }
    }
    $inicio = new DateTime(sprintf('%04d-%02d-%02d', $inicioAno, $inicioMes, $diaInicio));
    $fim    = clone $inicio;
    $fim->add(new DateInterval('P1M'));
    $fim->sub(new DateInterval('P1D'));
    return [
        'referencia' => sprintf('%02d/%04d', (int)$fim->format('m'), (int)$fim->format('Y')),
        'inicio'     => $inicio->format('Y-m-d'),
        'fim'        => $fim->format('Y-m-d'),
    ];
}

function rlGerarMeses(int $diaInicio): array {
    $minMes = '06/2026';
    $minDt  = new DateTime('2026-06-01');
    $meses  = [];
    for ($i = 0; $i < 12; $i++) {
        $dt = new DateTime('first day of this month');
        $dt->modify("-{$i} months");
        if ($dt < $minDt) break;
        $meses[] = sprintf('%02d/%04d', (int)$dt->format('m'), (int)$dt->format('Y'));
    }
    return $meses;
}

function rlBatchCompanyNames(BitrixService $bitrix, array $ids): array {
    $cache = [];
    foreach (array_chunk($ids, 50) as $chunk) {
        $cmd = [];
        foreach ($chunk as $i => $cid) {
            $cmd["co{$i}"] = 'crm.company.get?' . http_build_query(['id' => $cid], '', '&', PHP_QUERY_RFC3986);
        }
        $resp    = $bitrix->call('batch', ['halt' => 0, 'cmd' => $cmd]);
        $results = $resp['result'] ?? [];
        foreach ($chunk as $i => $cid) {
            $co = $results["co{$i}"] ?? null;
            $cache[$cid] = $co['TITLE'] ?? "Empresa #{$cid}";
        }
    }
    return $cache;
}
