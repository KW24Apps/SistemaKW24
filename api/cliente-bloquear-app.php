<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
header('Content-Type: application/json');
$auth = new AuthenticationService();
if (!$auth->validateSession()) { http_response_code(401); echo json_encode(['erro'=>'Não autenticado']); exit; }

$body        = json_decode(file_get_contents('php://input'), true);
$clienteId   = (int)($body['cliente_id']   ?? 0);
$aplicacaoId = (int)($body['aplicacao_id'] ?? 0);
$ativo       = ($body['ativo'] ?? false) ? 'TRUE' : 'FALSE';

if (!$clienteId || !$aplicacaoId) { echo json_encode(['erro'=>'Dados inválidos']); exit; }

try {
    $db = Database::getInstance();
    $db->execute(
        "UPDATE cliente_aplicacoes SET ativo = :ativo::boolean WHERE cliente_id = :c AND aplicacao_id = :a",
        ['ativo' => $ativo, 'c' => $clienteId, 'a' => $aplicacaoId]
    );
    echo json_encode(['sucesso' => true]);
} catch (Exception $e) { echo json_encode(['erro' => $e->getMessage()]); }
