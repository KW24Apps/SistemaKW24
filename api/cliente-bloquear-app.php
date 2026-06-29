<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
header('Content-Type: application/json');
$auth = new AuthenticationService();
if (!$auth->validateSession()) { http_response_code(401); echo json_encode(['erro' => 'Não autenticado']); exit; }

$body      = json_decode(file_get_contents('php://input'), true);
$caId      = (int)($body['ca_id']      ?? 0);
$clienteId = (int)($body['cliente_id'] ?? 0);
$ativo     = ($body['ativo'] ?? false) ? 'TRUE' : 'FALSE';

if (!$caId || !$clienteId) { echo json_encode(['erro' => 'Dados inválidos']); exit; }

try {
    $db = Database::getInstance();
    $db->execute(
        "UPDATE cliente_aplicacoes SET ativo = :ativo::boolean WHERE id = :ca_id AND cliente_id = :c",
        ['ativo' => $ativo, 'ca_id' => $caId, 'c' => $clienteId]
    );
    echo json_encode(['sucesso' => true]);
} catch (Exception $e) { echo json_encode(['erro' => $e->getMessage()]); }
