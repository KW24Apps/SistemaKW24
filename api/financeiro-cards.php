<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../dao/ConfiguracaoDAO.php';
require_once __DIR__ . '/../services/BitrixService.php';

header('Content-Type: application/json');

$auth = new AuthenticationService();
if (!$auth->validateSession()) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autenticado']);
    exit;
}

define('BX_ENTITY_TYPE', 1054);
define('BX_CAT_FINANC',  210);
define('F_CONTROLE',     'ufCrm41_1742082168');
define('F_MIN_SUPORTE',  'ufCrm41_1767900752');
define('F_MIN_DEV',      'ufCrm41_1767900780');

try {
    $dao      = new ConfiguracaoDAO();
    $diaInicio = max(1, min(28, (int)($dao->get('financeiro_dia_inicio') ?? 27)));

    $periodo = calcularPeriodoAtual($diaInicio);

    $bitrix = new BitrixService();

    if (!$bitrix->isConfigured()) {
        echo json_encode([
            'sucesso' => true,
            'periodo' => $periodo,
            'cards'   => [],
            'aviso'   => 'Webhook Bitrix24 não configurado',
        ]);
        exit;
    }

    $rawCards = $bitrix->listItems(BX_ENTITY_TYPE, [
        'categoryId' => BX_CAT_FINANC,
        F_CONTROLE   => $periodo['referencia'],
    ], [
        'id', 'title', 'stageId', 'companyId',
        F_CONTROLE,
        F_MIN_SUPORTE,
        F_MIN_DEV,
    ]);

    $prefix = "Fatura Referente a {$periodo['referencia']} - ";
    $cards  = [];
    foreach ($rawCards as $c) {
        $title   = $c['title'] ?? '';
        $empresa = str_starts_with($title, $prefix)
            ? substr($title, strlen($prefix))
            : $title;

        $cards[] = [
            'id'         => (int)$c['id'],
            'empresa'    => $empresa,
            'stageId'    => $c['stageId'] ?? '',
            'minSuporte' => (int)($c[F_MIN_SUPORTE] ?? 0),
            'minDev'     => (int)($c[F_MIN_DEV]     ?? 0),
        ];
    }

    usort($cards, fn($a, $b) => strcmp($a['empresa'], $b['empresa']));

    echo json_encode([
        'sucesso' => true,
        'periodo' => $periodo,
        'cards'   => $cards,
    ]);

} catch (Exception $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}

function calcularPeriodoAtual(int $diaInicio): array {
    $hoje = new DateTime();
    $dia  = (int)$hoje->format('d');
    $mes  = (int)$hoje->format('m');
    $ano  = (int)$hoje->format('Y');

    if ($dia >= $diaInicio) {
        $inicioMes = $mes;
        $inicioAno = $ano;
    } else {
        $inicioMes = $mes - 1;
        $inicioAno = $ano;
        if ($inicioMes < 1) { $inicioMes = 12; $inicioAno--; }
    }

    $inicio = new DateTime(sprintf('%04d-%02d-%02d', $inicioAno, $inicioMes, $diaInicio));
    $fim    = clone $inicio;
    $fim->add(new DateInterval('P1M'));
    $fim->sub(new DateInterval('P1D'));

    $refMes = (int)$fim->format('m');
    $refAno = (int)$fim->format('Y');

    return [
        'referencia' => sprintf('%02d/%04d', $refMes, $refAno),
        'inicio'     => $inicio->format('Y-m-d'),
        'fim'        => $fim->format('Y-m-d'),
    ];
}
