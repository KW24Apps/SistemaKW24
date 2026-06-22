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
if (($user['perfil'] ?? '') !== 'admin_interno') {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado']);
    exit;
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

try {

    // ── GET: list all portals ───────────────────────────────────────────────
    if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $rows = $pdo->query(
            'SELECT id, relatorio_slug, filter_type, filter_values, filter_labels,
                    slug, nome, embed_token, ativo,
                    to_char(created_at, \'DD/MM/YYYY\') AS created_fmt
             FROM portais_bi ORDER BY created_at DESC'
        )->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as &$r) {
            $r['ativo']         = (bool)$r['ativo'];
            $r['filter_values'] = json_decode($r['filter_values'], true) ?? [];
            $r['filter_labels'] = json_decode($r['filter_labels'], true) ?? [];
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

        http_response_code(400);
        echo json_encode(['erro' => 'type inválido — use parceiro ou oportunidade']);
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

        if (!$relatorioSlug || !$filterType || !$slug || !$senha) {
            echo json_encode(['erro' => 'Campos obrigatórios não preenchidos']); exit;
        }
        if (!in_array($filterType, ['parceiro', 'oportunidade'], true)) {
            echo json_encode(['erro' => 'filter_type inválido']); exit;
        }
        if (empty($filterValues)) {
            echo json_encode(['erro' => 'Selecione pelo menos um filtro']); exit;
        }
        if (!preg_match('/^[a-z0-9\-]+$/', $slug)) {
            echo json_encode(['erro' => 'Slug inválido — use apenas letras minúsculas, números e hifens']); exit;
        }
        if (in_array($slug, ['bi', 'sair', 'embed'], true)) {
            echo json_encode(['erro' => 'Slug reservado — escolha outro']); exit;
        }

        $senhaHash  = password_hash($senha, PASSWORD_BCRYPT);
        $embedToken = bin2hex(random_bytes(32));

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
        if (!in_array($filterType, ['parceiro', 'oportunidade'], true)) {
            echo json_encode(['erro' => 'filter_type inválido']); exit;
        }
        if (empty($filterValues)) {
            echo json_encode(['erro' => 'Selecione pelo menos um filtro']); exit;
        }
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
