<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';

header('Content-Type: application/json');

$auth = new AuthenticationService();
if (!$auth->validateSession()) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autenticado']);
    exit;
}
$user = $auth->getCurrentUser();
$isAdmin = ($user['perfil'] ?? '') === 'admin_interno';
if (!$isAdmin) {
    $db           = Database::getInstance();
    $prof         = $db->fetchOne(
        'SELECT pp.menus FROM usuarios u
           JOIN permission_profiles pp ON pp.id = u.profile_id
          WHERE u.id = :id AND u.profile_id IS NOT NULL',
        ['id' => $user['id']]
    );
    $allowedMenus = $prof ? (json_decode($prof['menus'], true) ?? []) : [];
    if (!in_array('portais-bi', $allowedMenus, true)) {
        http_response_code(403);
        echo json_encode(['erro' => 'Acesso negado']);
        exit;
    }
}

$pdo    = Database::getInstance()->getConnection();
$action = trim($_GET['action'] ?? $_POST['action'] ?? '');

// Direct connection to bx_sync_nimbus_tax for filter lists (same PG instance, same credentials)
function getBxPdo(): PDO {
    $cfg = require __DIR__ . '/../config/config.php';
    $db  = $cfg['database'];
    $dsn = "pgsql:host={$db['host']};port={$db['port']};dbname=bx_sync_nimbus_tax";
    return new PDO($dsn, $db['username'], $db['password'], $db['options']);
}

// Direct connection to bx_sync_contabilidade (relatorio-contabilidade filter lists)
function getCtPdo(): PDO {
    $cfg = require __DIR__ . '/../config/config.php';
    $db  = $cfg['database'];
    $dsn = "pgsql:host={$db['host']};port={$db['port']};dbname=bx_sync_contabilidade";
    return new PDO($dsn, $db['username'], $db['password'], $db['options']);
}

try {

    // ── GET: list all portals ───────────────────────────────────────────────
    if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $sqlList = 'SELECT id, relatorio_slug, filter_type, filter_values, filter_labels,
                    slug, nome, embed_token, ativo,
                    ct_indicador_values, ct_indicador_labels,
                    ct_contab_values, ct_contab_labels, ct_completo,
                    to_char(created_at, \'DD/MM/YYYY\') AS created_fmt
             FROM portais_bi';
        $bind = [];
        // admin_interno vê todos os portais; demais usuários só os de relatórios
        // liberados via aplicação (relatorios_visiveis, calculado em index.php).
        if (!$isAdmin) {
            $visiveis = $_SESSION['relatorios_visiveis'] ?? [];
            if (!$visiveis) {
                echo json_encode(['sucesso' => true, 'portais' => []]);
                exit;
            }
            $ph = [];
            foreach ($visiveis as $i => $slug) { $ph[] = ':s' . $i; $bind[':s' . $i] = $slug; }
            $sqlList .= ' WHERE relatorio_slug IN (' . implode(',', $ph) . ')';
        }
        $sqlList .= ' ORDER BY created_at DESC';
        $stmtList = $pdo->prepare($sqlList);
        foreach ($bind as $k => $v) { $stmtList->bindValue($k, $v); }
        $stmtList->execute();
        $rows = $stmtList->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['ativo']               = (bool)$r['ativo'];
            $r['filter_values']       = json_decode($r['filter_values'], true) ?? [];
            $r['filter_labels']       = json_decode($r['filter_labels'], true) ?? [];
            $r['ct_indicador_values'] = json_decode($r['ct_indicador_values'] ?? '[]', true) ?? [];
            $r['ct_indicador_labels'] = json_decode($r['ct_indicador_labels'] ?? '[]', true) ?? [];
            $r['ct_contab_values']    = json_decode($r['ct_contab_values']    ?? '[]', true) ?? [];
            $r['ct_contab_labels']    = json_decode($r['ct_contab_labels']    ?? '[]', true) ?? [];
            $r['ct_completo']         = (bool)$r['ct_completo'];
        }
        echo json_encode(['sucesso' => true, 'portais' => $rows]);
        exit;
    }

    // ── GET: list filter options from bx_sync_nimbus_tax ───────────────────
    if ($action === 'list-filters' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $type = $_GET['type'] ?? '';
        $bx   = getBxPdo();

        if ($type === 'parceiro') {
            $rows = $bx->query(
                "SELECT DISTINCT parceiro_comercial_id AS id, parceiro_comercial AS nome
                 FROM tbl_negocio
                 WHERE parceiro_comercial IS NOT NULL AND parceiro_comercial != ''
                   AND parceiro_comercial_id IS NOT NULL AND parceiro_comercial_id != ''
                 ORDER BY parceiro_comercial"
            )->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['sucesso' => true, 'items' => $rows]);
            exit;
        }

        if ($type === 'oportunidade') {
            $rows = $bx->query(
                "SELECT DISTINCT oportunidade_id AS id, oportunidade AS nome
                 FROM tbl_negocio
                 WHERE oportunidade IS NOT NULL AND oportunidade != ''
                   AND oportunidade_id IS NOT NULL AND oportunidade_id != ''
                 ORDER BY oportunidade"
            )->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['sucesso' => true, 'items' => $rows]);
            exit;
        }

        // relatorio-contabilidade — indicadores (parceiro_indicacao), excluindo vendas próprias
        if ($type === 'ct-indicador') {
            $ct = getCtPdo();
            $rows = $ct->query(
                "SELECT DISTINCT TRIM(parceiro_indicacao) AS id, TRIM(parceiro_indicacao) AS nome
                 FROM tbl_onboard
                 WHERE parceiro_indicacao IS NOT NULL
                   AND TRIM(parceiro_indicacao) != ''
                   AND UPPER(TRIM(parceiro_indicacao))
                       NOT IN ('FF CONTABILIDADE LTDA','CAPITON CONTABILIDADE S/S')
                 ORDER BY nome"
            )->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['sucesso' => true, 'items' => $rows]);
            exit;
        }

        // relatorio-contabilidade — contabilidades responsáveis
        if ($type === 'ct-contab') {
            $ct = getCtPdo();
            $rows = $ct->query(
                "SELECT DISTINCT TRIM(contabilidade_responsavel_operacional) AS id,
                                 TRIM(contabilidade_responsavel_operacional) AS nome
                 FROM tbl_onboard
                 WHERE contabilidade_responsavel_operacional IS NOT NULL
                   AND TRIM(contabilidade_responsavel_operacional) != ''
                 ORDER BY nome"
            )->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['sucesso' => true, 'items' => $rows]);
            exit;
        }

        http_response_code(400);
        echo json_encode(['erro' => 'type inválido — use parceiro, oportunidade, ct-indicador ou ct-contab']);
        exit;
    }

    // ── POST: create ────────────────────────────────────────────────────────
    if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $relatorioSlug = trim($body['relatorio_slug'] ?? '');
        $filterType    = trim($body['filter_type']    ?? '');
        $filterValues  = $body['filter_values'] ?? [];
        $filterLabels  = $body['filter_labels'] ?? [];
        $slug          = strtolower(trim($body['slug']  ?? ''));
        $nome          = trim($body['nome']            ?? '');
        $senha         = trim($body['senha']           ?? '');

        $isContab = ($relatorioSlug === 'relatorio-contabilidade');

        if (!$relatorioSlug || !$filterType || !$slug || !$senha) {
            echo json_encode(['erro' => 'Campos obrigatórios não preenchidos']); exit;
        }
        // Contabilidade usa o par indicador/contabilidade (dimensões próprias, ver ct_*
        // abaixo); demais relatórios usam parceiro/oportunidade.
        $filterTypesValidos = $isContab ? ['indicador', 'contabilidade'] : ['parceiro', 'oportunidade'];
        if (!in_array($filterType, $filterTypesValidos, true)) {
            echo json_encode(['erro' => 'filter_type inválido']); exit;
        }
        // filter_values pode vir vazio (contabilidade usa ct_*) ou ['__completo__']
        // (Relatório Completo — sem filtro). Validação de seleção fica no frontend.
        if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
            echo json_encode(['erro' => 'Slug inválido — use apenas letras minúsculas, números e hifens']); exit;
        }
        if (in_array($slug, ['bi', 'sair', 'embed'], true)) {
            echo json_encode(['erro' => 'Slug reservado — escolha outro']); exit;
        }

        $senhaHash  = password_hash($senha, PASSWORD_BCRYPT);
        $embedToken = bin2hex(random_bytes(32));

        if ($isContab) {
            $ctCompleto        = (bool)($body['ct_completo'] ?? false);
            $ctIndicadorValues = array_values($body['ct_indicador_values'] ?? []);
            $ctIndicadorLabels = array_values($body['ct_indicador_labels'] ?? []);
            $ctContabValues    = array_values($body['ct_contab_values']    ?? []);
            $ctContabLabels    = array_values($body['ct_contab_labels']    ?? []);

            $stmt = $pdo->prepare(
                'INSERT INTO portais_bi
                    (relatorio_slug, filter_type, filter_values, filter_labels, slug, nome, senha_hash, embed_token,
                     ct_indicador_values, ct_indicador_labels, ct_contab_values, ct_contab_labels, ct_completo)
                 VALUES (?, ?, ?::jsonb, ?::jsonb, ?, ?, ?, ?, ?::jsonb, ?::jsonb, ?::jsonb, ?::jsonb, ?)'
            );
            $stmt->execute([
                $relatorioSlug, $filterType,
                json_encode(array_values($filterValues)), json_encode(array_values($filterLabels)),
                $slug, $nome ?: null, $senhaHash, $embedToken,
                json_encode($ctIndicadorValues), json_encode($ctIndicadorLabels),
                json_encode($ctContabValues), json_encode($ctContabLabels),
                $ctCompleto ? 'true' : 'false',
            ]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO portais_bi
                    (relatorio_slug, filter_type, filter_values, filter_labels, slug, nome, senha_hash, embed_token)
                 VALUES (?, ?, ?::jsonb, ?::jsonb, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $relatorioSlug, $filterType,
                json_encode(array_values($filterValues)), json_encode(array_values($filterLabels)),
                $slug, $nome ?: null, $senhaHash, $embedToken,
            ]);
        }
        $id = (int)$pdo->lastInsertId('portais_bi_id_seq');

        echo json_encode([
            'sucesso'     => true,
            'id'          => $id,
            'slug'        => $slug,
            'embed_token' => $embedToken,
        ]);
        exit;
    }

    // ── POST: update ────────────────────────────────────────────────────────
    if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];

        $id           = (int)($body['id']            ?? 0);
        $filterType   = trim($body['filter_type']    ?? '');
        $filterValues = $body['filter_values'] ?? [];
        $filterLabels = $body['filter_labels'] ?? [];
        $slug         = strtolower(trim($body['slug'] ?? ''));
        $nome         = trim($body['nome']            ?? '');
        $novaSenha    = trim($body['senha']           ?? '');

        if (!$id || !$filterType || !$slug) {
            echo json_encode(['erro' => 'Campos obrigatórios não preenchidos']); exit;
        }
        // relatorio_slug é imutável — busca do banco p/ saber se é contabilidade
        $rSlug = $pdo->prepare('SELECT relatorio_slug FROM portais_bi WHERE id=?');
        $rSlug->execute([$id]);
        $isContab = ($rSlug->fetchColumn() === 'relatorio-contabilidade');

        $filterTypesValidos = $isContab ? ['indicador', 'contabilidade'] : ['parceiro', 'oportunidade'];
        if (!in_array($filterType, $filterTypesValidos, true)) {
            echo json_encode(['erro' => 'filter_type inválido']); exit;
        }
        // filter_values pode vir vazio (contabilidade usa ct_*) ou ['__completo__']
        // (Relatório Completo — sem filtro). Validação de seleção fica no frontend.
        if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
            echo json_encode(['erro' => 'Slug inválido']); exit;
        }
        if (in_array($slug, ['bi', 'sair', 'embed'], true)) {
            echo json_encode(['erro' => 'Slug reservado']); exit;
        }

        if ($novaSenha) {
            $senhaHash = password_hash($novaSenha, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare(
                'UPDATE portais_bi
                 SET filter_type=?, filter_values=?::jsonb, filter_labels=?::jsonb,
                     slug=?, nome=?, senha_hash=?
                 WHERE id=?'
            );
            $stmt->execute([
                $filterType,
                json_encode(array_values($filterValues)), json_encode(array_values($filterLabels)),
                $slug, $nome ?: null, $senhaHash, $id,
            ]);
        } else {
            $stmt = $pdo->prepare(
                'UPDATE portais_bi
                 SET filter_type=?, filter_values=?::jsonb, filter_labels=?::jsonb,
                     slug=?, nome=?
                 WHERE id=?'
            );
            $stmt->execute([
                $filterType,
                json_encode(array_values($filterValues)), json_encode(array_values($filterLabels)),
                $slug, $nome ?: null, $id,
            ]);
        }

        // contabilidade: atualiza também os campos ct_* (vindos do body)
        if ($isContab) {
            $stmt = $pdo->prepare(
                'UPDATE portais_bi
                 SET ct_indicador_values=?::jsonb, ct_indicador_labels=?::jsonb,
                     ct_contab_values=?::jsonb, ct_contab_labels=?::jsonb, ct_completo=?
                 WHERE id=?'
            );
            $stmt->execute([
                json_encode(array_values($body['ct_indicador_values'] ?? [])),
                json_encode(array_values($body['ct_indicador_labels'] ?? [])),
                json_encode(array_values($body['ct_contab_values']    ?? [])),
                json_encode(array_values($body['ct_contab_labels']    ?? [])),
                ((bool)($body['ct_completo'] ?? false)) ? 'true' : 'false',
                $id,
            ]);
        }
        echo json_encode(['sucesso' => true]);
        exit;
    }

    // ── POST: toggle ────────────────────────────────────────────────────────
    if ($action === 'toggle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int)($body['id'] ?? 0);
        if (!$id) { echo json_encode(['erro' => 'ID inválido']); exit; }

        $pdo->prepare('UPDATE portais_bi SET ativo = NOT ativo WHERE id=?')->execute([$id]);

        $r = $pdo->prepare('SELECT ativo FROM portais_bi WHERE id=?');
        $r->execute([$id]);
        $novoAtivo = (bool)$r->fetchColumn();

        echo json_encode(['sucesso' => true, 'ativo' => $novoAtivo]);
        exit;
    }

    // ── POST: delete ────────────────────────────────────────────────────────
    if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $body = json_decode(file_get_contents('php://input'), true) ?? [];
        $id   = (int)($body['id'] ?? 0);
        if (!$id) { echo json_encode(['erro' => 'ID inválido']); exit; }

        $pdo->prepare('DELETE FROM portais_bi WHERE id=?')->execute([$id]);
        echo json_encode(['sucesso' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['erro' => 'Ação inválida']);

} catch (PDOException $e) {
    $msg = $e->getMessage();
    if (stripos($msg, 'unique') !== false || stripos($msg, 'duplicate') !== false) {
        echo json_encode(['erro' => 'Slug já existe — escolha outro']);
    } else {
        error_log('[portais-bi] ' . $msg);
        echo json_encode(['erro' => 'Erro no banco de dados']);
    }
} catch (Exception $e) {
    error_log('[portais-bi] ' . $e->getMessage());
    echo json_encode(['erro' => 'Erro interno']);
}
