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
    echo json_encode(['erro' => 'Acesso restrito a administradores']);
    exit;
}

$db     = Database::getInstance();
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = trim($body['action'] ?? '');

try {
    // ── list ────────────────────────────────────────────────────────────────
    if ($action === 'list') {
        $rows = $db->fetchAll(
            "SELECT id, nome, chave, ativo, to_char(created_at, 'DD/MM/YYYY HH24:MI') AS created_fmt
             FROM mcp_clients
             ORDER BY created_at DESC"
        );
        echo json_encode($rows);
        exit;
    }

    // ── create ──────────────────────────────────────────────────────────────
    if ($action === 'create') {
        $nome = trim($body['nome'] ?? '');
        if ($nome === '') {
            echo json_encode(['erro' => 'Nome é obrigatório']);
            exit;
        }

        $chave = bin2hex(random_bytes(32));

        $db->execute(
            "INSERT INTO mcp_clients (nome, chave) VALUES (:nome, :chave)",
            ['nome' => $nome, 'chave' => $chave]
        );
        $id = (int) $db->getLastInsertId('mcp_clients_id_seq');

        $row = $db->fetchOne(
            "SELECT id, nome, chave, ativo, to_char(created_at, 'DD/MM/YYYY HH24:MI') AS created_fmt
             FROM mcp_clients WHERE id = :id",
            ['id' => $id]
        );
        echo json_encode($row);
        exit;
    }

    // ── toggle ──────────────────────────────────────────────────────────────
    if ($action === 'toggle') {
        $id = (int) ($body['id'] ?? 0);
        if (!$id) {
            echo json_encode(['erro' => 'ID inválido']);
            exit;
        }
        $db->execute("UPDATE mcp_clients SET ativo = NOT ativo WHERE id = :id", ['id' => $id]);
        $row = $db->fetchOne("SELECT id, ativo FROM mcp_clients WHERE id = :id", ['id' => $id]);
        if (!$row) {
            echo json_encode(['erro' => 'Não encontrado']);
            exit;
        }
        echo json_encode(['id' => (int) $row['id'], 'ativo' => (bool) $row['ativo']]);
        exit;
    }

    // ── delete ──────────────────────────────────────────────────────────────
    if ($action === 'delete') {
        $id = (int) ($body['id'] ?? 0);
        if (!$id) {
            echo json_encode(['erro' => 'ID inválido']);
            exit;
        }
        $db->execute("DELETE FROM mcp_clients WHERE id = :id", ['id' => $id]);
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['erro' => 'Ação desconhecida']);

} catch (Exception $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}
