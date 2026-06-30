<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
header('Content-Type: application/json');
$auth = new AuthenticationService();
if (!$auth->validateSession()) { http_response_code(401); echo json_encode(['erro' => 'Não autenticado']); exit; }
$user = $auth->getCurrentUser();
if (($user['perfil'] ?? '') !== 'admin_interno') { http_response_code(403); echo json_encode(['erro' => 'Acesso restrito']); exit; }

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$clienteId = (int)($body['cliente_id'] ?? 0);
$usuarioId = (int)($body['usuario_id'] ?? 0);
if (!$clienteId || !$usuarioId) { echo json_encode(['erro' => 'Dados inválidos']); exit; }

try {
    $db = Database::getInstance();
    $db->execute(
        "DELETE FROM cliente_usuarios WHERE cliente_id = :c AND usuario_id = :u",
        ['c' => $clienteId, 'u' => $usuarioId]
    );
    echo json_encode(['sucesso' => true]);
} catch (Exception $e) { echo json_encode(['erro' => $e->getMessage()]); }
