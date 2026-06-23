<?php
session_start();
ini_set('display_errors', '0');
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';

header('Content-Type: application/json; charset=utf-8');

try {

$auth = new AuthenticationService();
if (!$auth->validateSession()) {
    http_response_code(401);
    echo json_encode(['error' => 'Não autenticado']);
    exit;
}

$user = $auth->getCurrentUser();
if (!$user || ($user['perfil'] ?? '') !== 'admin_interno') {
    http_response_code(403);
    echo json_encode(['error' => 'Acesso restrito']);
    exit;
}

$db     = Database::getInstance();
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

// ── GET ──────────────────────────────────────────────────────────────────────

if ($method === 'GET') {
    if ($action === 'list') {
        $rows = $db->fetchAll(
            'SELECT pp.id, pp.nome, pp.menus, pp.criado_em,
                    COUNT(u.id)::int AS user_count
               FROM permission_profiles pp
          LEFT JOIN usuarios u ON u.profile_id = pp.id
           GROUP BY pp.id
           ORDER BY pp.criado_em'
        );
        echo json_encode(['data' => $rows]);
        exit;
    }

    if ($action === 'get' && isset($_GET['id'])) {
        $row = $db->fetchOne(
            'SELECT id, nome, menus, criado_em FROM permission_profiles WHERE id = :id',
            ['id' => (int)$_GET['id']]
        );
        if (!$row) { http_response_code(404); echo json_encode(['error' => 'Não encontrado']); exit; }
        echo json_encode(['data' => $row]);
        exit;
    }

    if ($action === 'clientes') {
        $rows = $db->fetchAll(
            'SELECT id, nome FROM clientes ORDER BY nome'
        );
        echo json_encode(['data' => $rows]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Ação inválida']);
    exit;
}

// ── POST ─────────────────────────────────────────────────────────────────────

if ($method === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    if ($action === 'create') {
        $nome  = trim($body['nome'] ?? '');
        $menus = $body['menus'] ?? [];

        if ($nome === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Nome obrigatório']);
            exit;
        }

        try {
            $db->execute(
                'INSERT INTO permission_profiles (nome, menus) VALUES (:nome, :menus::jsonb)',
                ['nome' => $nome, 'menus' => json_encode($menus)]
            );
            $new = $db->fetchOne(
                'SELECT id, nome, menus, criado_em FROM permission_profiles WHERE nome = :nome ORDER BY id DESC LIMIT 1',
                ['nome' => $nome]
            );
            echo json_encode(['success' => true, 'data' => $new]);
        } catch (Exception $e) {
            http_response_code(409);
            echo json_encode(['error' => 'Nome já existe']);
        }
        exit;
    }

    if ($action === 'update' && isset($_GET['id'])) {
        $nome  = trim($body['nome'] ?? '');
        $menus = $body['menus'] ?? [];

        if ($nome === '') {
            http_response_code(422);
            echo json_encode(['error' => 'Nome obrigatório']);
            exit;
        }

        try {
            $db->execute(
                'UPDATE permission_profiles SET nome = :nome, menus = :menus::jsonb WHERE id = :id',
                ['nome' => $nome, 'menus' => json_encode($menus), 'id' => (int)$_GET['id']]
            );
            echo json_encode(['success' => true]);
        } catch (Exception $e) {
            http_response_code(409);
            echo json_encode(['error' => 'Nome já existe']);
        }
        exit;
    }

    if ($action === 'delete' && isset($_GET['id'])) {
        $id = (int)$_GET['id'];

        $count = $db->fetchOne(
            'SELECT COUNT(*)::int AS c FROM usuarios WHERE profile_id = :id',
            ['id' => $id]
        );
        if ($count && $count['c'] > 0) {
            http_response_code(409);
            echo json_encode(['error' => 'Perfil está em uso por ' . $count['c'] . ' usuário(s)']);
            exit;
        }

        $db->execute('DELETE FROM permission_profiles WHERE id = :id', ['id' => $id]);
        echo json_encode(['success' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['error' => 'Ação inválida']);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Método não permitido']);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro interno: ' . $e->getMessage()]);
}
