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

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['erro' => 'ID inválido']);
    exit;
}

$db = Database::getInstance();

$cliente = $db->fetchOne("SELECT * FROM clientes WHERE id = :id", ['id' => $id]);
if (!$cliente) {
    echo json_encode(['erro' => 'Cliente não encontrado']);
    exit;
}

$aplicacoes = $db->fetchAll("
    SELECT a.id, a.slug, a.nome, a.descricao, ca.webhook_bitrix, ca.ativo
    FROM cliente_aplicacoes ca
    JOIN aplicacoes a ON a.id = ca.aplicacao_id
    WHERE ca.cliente_id = :id AND ca.ativo = TRUE
    ORDER BY a.nome ASC
", ['id' => $id]);

echo json_encode([
    'cliente'   => $cliente,
    'aplicacoes' => $aplicacoes
]);
