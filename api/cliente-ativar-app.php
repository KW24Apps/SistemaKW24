<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
header('Content-Type: application/json');
$auth = new AuthenticationService();
if (!$auth->validateSession()) { http_response_code(401); echo json_encode(['erro' => 'Não autenticado']); exit; }

$body       = json_decode(file_get_contents('php://input'), true);
$clienteId   = (int)($body['cliente_id']   ?? 0);
$aplicacaoId = (int)($body['aplicacao_id'] ?? 0);
$webhook     = trim($body['webhook_bitrix'] ?? '');

if (!$clienteId || !$aplicacaoId) { echo json_encode(['erro' => 'Dados inválidos']); exit; }

try {
    $db = Database::getInstance();
    $existe = $db->fetchOne(
        "SELECT id FROM cliente_aplicacoes WHERE cliente_id = :c AND aplicacao_id = :a",
        ['c' => $clienteId, 'a' => $aplicacaoId]
    );
    if ($existe) {
        $db->execute(
            "UPDATE cliente_aplicacoes SET ativo = TRUE, webhook_bitrix = :w WHERE cliente_id = :c AND aplicacao_id = :a",
            ['c' => $clienteId, 'a' => $aplicacaoId, 'w' => $webhook ?: null]
        );
    } else {
        $db->execute(
            "INSERT INTO cliente_aplicacoes (cliente_id, aplicacao_id, ativo, webhook_bitrix) VALUES (:c, :a, TRUE, :w)",
            ['c' => $clienteId, 'a' => $aplicacaoId, 'w' => $webhook ?: null]
        );
    }
    echo json_encode(['sucesso' => true]);
} catch (Exception $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}
