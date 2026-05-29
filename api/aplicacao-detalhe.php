<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
header('Content-Type: application/json');
$auth = new AuthenticationService();
if (!$auth->validateSession()) { http_response_code(401); echo json_encode(['erro'=>'Não autenticado']); exit; }

$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['erro'=>'ID inválido']); exit; }

$db  = Database::getInstance();
$app = $db->fetchOne("SELECT * FROM aplicacoes WHERE id = :id", ['id' => $id]);
if (!$app) { echo json_encode(['erro'=>'Aplicação não encontrada']); exit; }

$clientes = $db->fetchAll("
    SELECT c.id, c.nome FROM clientes c
    JOIN cliente_aplicacoes ca ON ca.cliente_id = c.id
    WHERE ca.aplicacao_id = :id AND ca.ativo = TRUE
    ORDER BY c.nome ASC
", ['id' => $id]);

echo json_encode(['app' => $app, 'clientes' => $clientes]);
