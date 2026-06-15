<?php
/**
 * Webhook receiver para demandas finalizadas do Bitrix24.
 * Chamado pelo Bitrix24 (sem sessão). Sempre retorna HTTP 200.
 *
 * Conflito documentado: FINANCEIRO.md indica UF_CRM_41_1737476922 como
 * "Tempo de Atendimento Final #", mas a API real retorna esse campo como
 * "Departamento" (enum). O campo correto para o hash é ufCrm41_1767906196,
 * confirmado inspecionando cards reais via crm.item.list.
 */

require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../dao/ConfiguracaoDAO.php';
require_once __DIR__ . '/../services/BitrixService.php';

header('Content-Type: application/json');

// ── Constantes ───────────────────────────────────────────────────────────────

define('BX_ENTITY_TYPE',  1054); // SPA "KW24"
define('BX_CAT_DEMANDAS', 208);  // Demandas Mensais (Execução)
define('BX_CAT_FINANC',   210);  // Funil Financeiro

// Tipos de chamado faturáveis (enum IDs confirmados via API)
define('TIPOS_SUPORTE', [21204, 21206]); // Suporte Bitrix24, Suporte Técnico
define('TIPOS_DEV',     [21208, 21210]); // Desenvolvimento - Melhoria, Desenvolvimento - Implementação
define('TIPOS_FATURA',  [21204, 21206, 21208, 21210]);

// Campos do card de demanda (category 208)
define('F_TIPO_CHAMADO', 'ufCrm41_1737476320'); // Tipo de Chamado (enum)
define('F_TEMPO_ATUAL',  'ufCrm41_1751475675'); // Tempo de Atendimento Final (Em Minutos)
define('F_TEMPO_HASH',   'ufCrm41_1767906196'); // Tempo de Atendimento Final # (último computado)
define('F_DATA_FIN',     'ufCrm41_1778777816'); // Data de finalização
define('F_FATURA_LINK',  'ufCrm41_1767897101'); // Fatura/Cobrança (CRM relation — retorna array)

// Campos do card financeiro (category 210)
define('F_MIN_SUPORTE', 'ufCrm41_1767900752'); // Tempo Total de Suporte (Em Minutos)
define('F_MIN_DEV',     'ufCrm41_1767900780'); // Tempo Total de Desenvolvimento (Em Minutos)
define('F_COMPETENCIA', 'ufCrm41_1742081702'); // Data de Competência (campo para referência de período)

// ── Helpers ───────────────────────────────────────────────────────────────────

function responder(string $status, string $msg): void {
    error_log("[financeiro-webhook] {$status}: {$msg}");
    echo json_encode(['status' => $status, 'msg' => $msg]);
    exit;
}

/**
 * Calcula o período vigente de faturamento.
 * Retorna: inicio (DateTime), fim (DateTime), referencia "MM/YYYY", refDate "YYYY-MM-01".
 */
function calcularPeriodo(int $diaInicio): array {
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

    $fim = clone $inicio;
    $fim->add(new DateInterval('P1M'));
    $fim->sub(new DateInterval('P1D'));
    $fim->setTime(23, 59, 59);

    $refMes = (int)$fim->format('m');
    $refAno = (int)$fim->format('Y');

    return [
        'inicio'     => $inicio,
        'fim'        => $fim,
        'referencia' => sprintf('%02d/%04d', $refMes, $refAno),
        'refDate'    => sprintf('%04d-%02d-01', $refAno, $refMes),
    ];
}

// ── a) Ler ID do card do payload ─────────────────────────────────────────────

$cardId = 0;

// Bitrix24 envia form-encoded: data[FIELDS][ID]
if (!empty($_POST['data']['FIELDS']['ID'])) {
    $cardId = (int)$_POST['data']['FIELDS']['ID'];
}

// Fallback: JSON body
if (!$cardId) {
    $body   = json_decode(file_get_contents('php://input'), true) ?? [];
    $cardId = (int)($body['data']['FIELDS']['ID'] ?? $body['id'] ?? 0);
}

// Fallback para testes via curl com query string: ?id=XXXX
if (!$cardId) {
    $cardId = (int)($_GET['id'] ?? 0);
}

if (!$cardId) {
    responder('ignored', 'card ID ausente no payload');
}

// ── Inicializar serviço ──────────────────────────────────────────────────────

try {
    $bitrix = new BitrixService();
} catch (Exception $e) {
    responder('erro', 'falha ao inicializar BitrixService: ' . $e->getMessage());
}

if (!$bitrix->isConfigured()) {
    responder('erro', 'webhook Bitrix24 não configurado em configuracoes_sistema');
}

// ── b) Buscar card de demanda ────────────────────────────────────────────────

$demand = $bitrix->getItem(BX_ENTITY_TYPE, $cardId);

if (!$demand) {
    responder('erro', "não foi possível buscar o card {$cardId}");
}

if ((int)($demand['categoryId'] ?? 0) !== BX_CAT_DEMANDAS) {
    responder('ignored', "card {$cardId} não pertence à categoria 208 (categoryId={$demand['categoryId']})");
}

// ── c) Verificar tipo de chamado ─────────────────────────────────────────────

$tipoChamado = (int)($demand[F_TIPO_CHAMADO] ?? 0);

if (!in_array($tipoChamado, TIPOS_FATURA, true)) {
    responder('ignored', "tipo de chamado {$tipoChamado} não é faturável (card {$cardId})");
}

// Determina qual campo de minutos usar no card financeiro
$minField = in_array($tipoChamado, TIPOS_SUPORTE, true) ? F_MIN_SUPORTE : F_MIN_DEV;

// ── d) Verificar período de faturamento ──────────────────────────────────────

$dao       = new ConfiguracaoDAO();
$diaInicio = max(1, min(28, (int)($dao->get('financeiro_dia_inicio') ?? 27)));
$periodo   = calcularPeriodo($diaInicio);

$dataFinRaw = $demand[F_DATA_FIN] ?? '';
if (!$dataFinRaw) {
    responder('ignored', "card {$cardId} sem data de finalização");
}

try {
    $dataFin = (new DateTime($dataFinRaw))->format('Y-m-d');
} catch (Exception $e) {
    responder('erro', "data de finalização inválida '{$dataFinRaw}' no card {$cardId}");
}

$periodoInicio = $periodo['inicio']->format('Y-m-d');
$periodoFim    = $periodo['fim']->format('Y-m-d');

if ($dataFin < $periodoInicio || $dataFin > $periodoFim) {
    responder('ignored', "data {$dataFin} fora do período {$periodoInicio}→{$periodoFim} (card {$cardId})");
}

// ── e) Calcular delta de minutos ─────────────────────────────────────────────

$tempoAtual      = (int)($demand[F_TEMPO_ATUAL] ?? 0);
$tempoRegistrado = (int)($demand[F_TEMPO_HASH]  ?? 0);

if ($tempoAtual === $tempoRegistrado) {
    responder('ok', "no-op: tempo não mudou (card {$cardId}, minutos={$tempoAtual})");
}

$delta = $tempoAtual - $tempoRegistrado;

// ── f) Encontrar ou criar card financeiro ─────────────────────────────────────

$companyId  = (int)($demand['companyId'] ?? 0);
$faturaLink = (array)($demand[F_FATURA_LINK] ?? []);

if (!$companyId) {
    responder('erro', "card {$cardId} sem empresa vinculada");
}

$financialId = 0;
$cardCriado  = false;

// Se já tem link direto para o card financeiro
if (!empty($faturaLink)) {
    $financialId = (int)$faturaLink[0];
}

// Buscar por empresa + período
if (!$financialId) {
    $financialCards = $bitrix->listItems(BX_ENTITY_TYPE, [
        'categoryId' => BX_CAT_FINANC,
        'companyId'  => $companyId,
    ]);

    foreach ($financialCards as $fc) {
        if (strpos($fc['title'] ?? '', $periodo['referencia']) !== false) {
            $financialId = (int)$fc['id'];
            break;
        }
    }
}

// Criar novo card financeiro
if (!$financialId) {
    $company     = $bitrix->getCompany($companyId);
    $companyName = $company['TITLE'] ?? "Empresa #{$companyId}";
    $title       = "Fatura Referente a {$periodo['referencia']} - {$companyName}";

    $financialId = $bitrix->createItem(BX_ENTITY_TYPE, [
        'categoryId'  => BX_CAT_FINANC,
        'title'       => $title,
        'companyId'   => $companyId,
        F_COMPETENCIA => $periodo['refDate'],
        $minField     => (string)$delta,
    ]);

    if (!$financialId) {
        responder('erro', "falha ao criar card financeiro para empresa {$companyId} período {$periodo['referencia']}");
    }

    $cardCriado = true;
    error_log("[financeiro-webhook] Card financeiro criado: id={$financialId} empresa={$companyId} período={$periodo['referencia']} delta={$delta}min");
}

// ── g) Atualizar minutos (apenas se card já existia) ─────────────────────────

if (!$cardCriado) {
    $financialCard = $bitrix->getItem(BX_ENTITY_TYPE, $financialId);

    if (!$financialCard) {
        responder('erro', "falha ao buscar card financeiro {$financialId}");
    }

    $minutosAtuais = (int)($financialCard[$minField] ?? 0);
    $minutosNovos  = $minutosAtuais + $delta;

    $ok = $bitrix->updateItem(BX_ENTITY_TYPE, $financialId, [
        $minField => (string)$minutosNovos,
    ]);

    if (!$ok) {
        responder('erro', "falha ao atualizar minutos no card financeiro {$financialId}");
    }

    error_log("[financeiro-webhook] Card financeiro {$financialId} atualizado: {$minutosAtuais}+{$delta}={$minutosNovos}min campo={$minField}");
}

// ── h) Atualizar card de demanda ─────────────────────────────────────────────

$updateDemand = [
    F_TEMPO_HASH => (string)$tempoAtual,
];

// Só vincula Fatura/Cobrança se o campo estava vazio
if (empty($faturaLink)) {
    $updateDemand[F_FATURA_LINK] = [$financialId];
}

$bitrix->updateItem(BX_ENTITY_TYPE, $cardId, $updateDemand);

// ── Resposta final ────────────────────────────────────────────────────────────

$acao = $cardCriado
    ? "card financeiro criado id={$financialId}"
    : "card financeiro {$financialId} atualizado";

responder('ok', "card {$cardId} processado — tipo={$tipoChamado} delta={$delta}min — {$acao}");
