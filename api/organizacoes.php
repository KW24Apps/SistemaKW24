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
$action = trim($_GET['action'] ?? $_POST['action'] ?? '');

try {
    // ── list ────────────────────────────────────────────────────────────────
    if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $rows = $db->fetchAll(
            "SELECT id, nome, ativo, webhook_motor,
                    to_char(created_at, 'DD/MM/YYYY') AS created_fmt
             FROM organizacoes
             ORDER BY nome ASC"
        );
        echo json_encode($rows);
        exit;
    }

    // ── get ─────────────────────────────────────────────────────────────────
    if ($action === 'get' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $id  = (int)($_GET['id'] ?? 0);
        $row = $db->fetchOne(
            "SELECT id, nome, ativo, webhook_motor,
                    to_char(created_at, 'DD/MM/YYYY') AS created_fmt
             FROM organizacoes WHERE id = :id",
            ['id' => $id]
        );
        echo json_encode($row ?: ['erro' => 'Não encontrado']);
        exit;
    }

    $body = json_decode(file_get_contents('php://input'), true) ?? [];

    // ── create ──────────────────────────────────────────────────────────────
    if ($action === 'create') {
        $nome = trim($body['nome'] ?? '');
        if (!$nome) {
            echo json_encode(['erro' => 'Nome é obrigatório']);
            exit;
        }
        $ativo   = isset($body['ativo']) ? (bool)$body['ativo'] : true;
        $webhook = trim($body['webhook_motor'] ?? '') ?: null;

        $db->execute(
            "INSERT INTO organizacoes (nome, ativo, webhook_motor) VALUES (:nome, :ativo, :wb)",
            ['nome' => $nome, 'ativo' => $ativo ? 'true' : 'false', 'wb' => $webhook]
        );
        $id = (int)$db->getLastInsertId('organizacoes_id_seq');
        echo json_encode(['sucesso' => true, 'id' => $id]);
        exit;
    }

    // ── update ──────────────────────────────────────────────────────────────
    if ($action === 'update') {
        $id   = (int)($body['id'] ?? 0);
        $nome = trim($body['nome'] ?? '');
        if (!$id || !$nome) {
            echo json_encode(['erro' => 'Dados inválidos']);
            exit;
        }
        $ativo = isset($body['ativo']) ? (bool)$body['ativo'] : true;

        // Webhook: só atualiza se fornecido e não-vazio (vazio = preservar valor atual)
        $sets   = "nome = :nome, ativo = :ativo, updated_at = NOW()";
        $params = ['nome' => $nome, 'ativo' => $ativo ? 'true' : 'false', 'id' => $id];
        if (isset($body['webhook_motor']) && trim($body['webhook_motor'] ?? '') !== '') {
            $sets .= ", webhook_motor = :wb";
            $params['wb'] = trim($body['webhook_motor']);
        }

        $db->execute("UPDATE organizacoes SET $sets WHERE id = :id", $params);
        echo json_encode(['sucesso' => true]);
        exit;
    }

    // ── toggle-ativo ────────────────────────────────────────────────────────
    if ($action === 'toggle-ativo') {
        $id = (int)($body['id'] ?? 0);
        if (!$id) {
            echo json_encode(['erro' => 'ID inválido']);
            exit;
        }
        $db->execute("UPDATE organizacoes SET ativo = NOT ativo, updated_at = NOW() WHERE id = :id", ['id' => $id]);
        $row = $db->fetchOne("SELECT ativo FROM organizacoes WHERE id = :id", ['id' => $id]);
        echo json_encode(['sucesso' => true, 'ativo' => (bool)$row['ativo']]);
        exit;
    }

    echo json_encode(['erro' => 'Ação desconhecida']);

} catch (Exception $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}
