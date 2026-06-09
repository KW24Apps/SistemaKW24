<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
header('Content-Type: application/json; charset=utf-8');

$auth = new AuthenticationService();
if (!$auth->validateSession()) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autenticado']);
    exit;
}

// ── DB connection to bx_sync_nimbus_tax ──────────────────────────────────────
function bxPdo(): PDO {
    $cfg = require __DIR__ . '/../config/config.php';
    $db  = $cfg['database'];
    $dsn = "pgsql:host={$db['host']};port={$db['port']};dbname=bx_sync_nimbus_tax";
    return new PDO($dsn, $db['username'], $db['password'], $db['options']);
}

// ── Parameters ────────────────────────────────────────────────────────────────
$pipeline      = 'RELATÓRIO PRELIMINAR (DIAGNOST)';
$status_filter = (isset($_GET['status_filter']) && $_GET['status_filter'] !== '')
    ? $_GET['status_filter']
    : null;

// CASE expression shared across all queries.
// NOTE: uses bare column names — callers prefix with table alias "n." where needed.
define('STATUS_CASE', "CASE
    WHEN etapa IN ('Sem Interesse','Sem valor de crédito','Perdidos',
                   'Fechado com outra empresa','Lixeira','Documentos Incompletos')
        THEN 'Sem Oportunidade'
    WHEN etapa = 'Suspenso'
        THEN 'Suspenso'
    WHEN pipeline = :pipeline_case
        THEN 'Em Diagnóstico'
    ELSE 'Com Oportunidade'
END");

// Build filter clause + params used by every visual that respects the cross-filter.
// NOTE: status summary (query B) deliberately excludes this clause — it is the
// cross-filter source, so it always shows totals for all statuses.
$sf_clause = '';
$sf_params = [];
if ($status_filter !== null) {
    $sf_clause = ' AND (' . STATUS_CASE . ') = :status_filter';
    $sf_params = ['status_filter' => $status_filter];
}

try {
    $pdo = bxPdo();

    // ── A: Stage table — "Nome da Etapa Numerado" ────────────────────────────
    // Groups by etapa, sorted by tbl_etapas.sort for pipeline 17 (DIAGNOST).
    $stmtA = $pdo->prepare("
        WITH ordered_etapas AS (
            SELECT
                nome,
                sort,
                LPAD(ROW_NUMBER() OVER (ORDER BY sort)::text, 2, '0') || ' - ' || nome
                    AS etapa_num
            FROM tbl_etapas
            WHERE pipeline_id = 17
        )
        SELECT
            COALESCE(oe.etapa_num, '?? - ' || n.etapa)  AS etapa_ordenada,
            COUNT(n.bitrix_id)                            AS total,
            COALESCE(SUM(n.valor), 0)                     AS valor_soma,
            MIN(COALESCE(oe.sort, 9999))                  AS sort_key
        FROM tbl_negocio n
        LEFT JOIN ordered_etapas oe ON oe.nome = n.etapa
        WHERE n.pipeline = :pipeline {$sf_clause}
        GROUP BY n.etapa, oe.etapa_num
        ORDER BY MIN(COALESCE(oe.sort, 9999)) ASC, n.etapa ASC
    ");
    $stmtA->execute(array_merge(['pipeline' => $pipeline, 'pipeline_case' => $pipeline], $sf_params));
    $etapa_table = $stmtA->fetchAll(PDO::FETCH_ASSOC);

    // ── B: Status summary — "Etapas Oportunidades" ───────────────────────────
    // Cross-filter SOURCE — does NOT apply status_filter.
    $stmtB = $pdo->prepare("
        SELECT
            " . STATUS_CASE . " AS status,
            COUNT(bitrix_id)      AS total,
            COALESCE(SUM(valor), 0) AS valor_soma
        FROM tbl_negocio
        WHERE pipeline = :pipeline
        GROUP BY status
        ORDER BY status DESC
    ");
    $stmtB->execute(['pipeline' => $pipeline, 'pipeline_case' => $pipeline]);
    $status_table = $stmtB->fetchAll(PDO::FETCH_ASSOC);

    // ── C: KPI totals ─────────────────────────────────────────────────────────
    $stmtC = $pdo->prepare("
        SELECT
            COUNT(n.bitrix_id)        AS total,
            COALESCE(SUM(n.valor), 0) AS valor_soma
        FROM tbl_negocio n
        WHERE n.pipeline = :pipeline {$sf_clause}
    ");
    $stmtC->execute(array_merge(['pipeline' => $pipeline, 'pipeline_case' => $pipeline], $sf_params));
    $kpis = $stmtC->fetch(PDO::FETCH_ASSOC);

    // ── D: Donut — Top 9 products + "Outros" ─────────────────────────────────
    $stmtD = $pdo->prepare("
        WITH produto_counts AS (
            SELECT
                COALESCE(NULLIF(TRIM(o.nome_nova_oportunidade_produto), ''), '(Sem Produto)')
                    AS produto,
                COUNT(n.bitrix_id) AS total
            FROM tbl_negocio n
            LEFT JOIN tbl_oportunidades o
                   ON o.bitrix_id::text = n.oportunidade_id
            WHERE n.pipeline = :pipeline {$sf_clause}
            GROUP BY o.nome_nova_oportunidade_produto
        ),
        ranked AS (
            SELECT produto, total,
                   ROW_NUMBER() OVER (ORDER BY total DESC) AS rn
            FROM produto_counts
        )
        SELECT
            CASE WHEN rn <= 9 THEN produto ELSE 'Outros' END AS produto,
            SUM(total) AS total
        FROM ranked
        GROUP BY CASE WHEN rn <= 9 THEN produto ELSE 'Outros' END
        ORDER BY SUM(total) DESC
    ");
    $stmtD->execute(array_merge(['pipeline' => $pipeline, 'pipeline_case' => $pipeline], $sf_params));
    $donut = $stmtD->fetchAll(PDO::FETCH_ASSOC);

    // ── E: Detail table ───────────────────────────────────────────────────────
    $stmtE = $pdo->prepare("
        SELECT
            n.bitrix_id,
            COALESCE(NULLIF(TRIM(emp.titulo), ''), n.empresa, '—')          AS cliente,
            COALESCE(NULLIF(TRIM(o.nome_nova_oportunidade_produto), ''), '—') AS oportunidade,
            n.etapa,
            COALESCE(n.observacoes, '')                                       AS observacoes,
            COALESCE(n.valor, 0)                                              AS valor,
            'https://gnapp.bitrix24.com.br/crm/deal/details/' || n.bitrix_id || '/'
                AS link_deal
        FROM tbl_negocio n
        LEFT JOIN tbl_empresas emp ON emp.bitrix_id::text = n.empresa_id
        LEFT JOIN tbl_oportunidades o  ON o.bitrix_id::text  = n.oportunidade_id
        WHERE n.pipeline = :pipeline {$sf_clause}
        ORDER BY n.bitrix_id DESC
        LIMIT 500
    ");
    $stmtE->execute(array_merge(['pipeline' => $pipeline, 'pipeline_case' => $pipeline], $sf_params));
    $detalhe = $stmtE->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'sucesso'       => true,
        'status_filter' => $status_filter,
        'etapa_table'   => $etapa_table,
        'status_table'  => $status_table,
        'kpis'          => $kpis,
        'donut'         => $donut,
        'detalhe'       => $detalhe,
    ], JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}
