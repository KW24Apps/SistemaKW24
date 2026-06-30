<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
header('Content-Type: application/json');
$auth = new AuthenticationService();
if (!$auth->validateSession()) { http_response_code(401); echo json_encode(['erro' => 'Não autenticado']); exit; }

$clienteId = (int)($_GET['cliente_id'] ?? 0);
if (!$clienteId) { echo json_encode(['erro' => 'cliente_id inválido']); exit; }

$db = Database::getInstance();

// ?todos=1 → todos os usuários que NÃO estão vinculados a este cliente
if (!empty($_GET['todos'])) {
    $users = $db->fetchAll(
        "SELECT u.id, u.nome, u.username, u.email
         FROM usuarios u
         WHERE u.ativo = TRUE
           AND u.id NOT IN (
               SELECT usuario_id FROM cliente_usuarios WHERE cliente_id = :cid
           )
         ORDER BY u.nome",
        ['cid' => $clienteId]
    );
    echo json_encode(['sucesso' => true, 'usuarios' => $users]);
    exit;
}

// Default → usuários vinculados a este cliente
$users = $db->fetchAll(
    "SELECT u.id, u.nome, u.username, u.email, cu.ativo, cu.created_at
     FROM cliente_usuarios cu
     JOIN usuarios u ON u.id = cu.usuario_id
     WHERE cu.cliente_id = :cid
     ORDER BY u.nome",
    ['cid' => $clienteId]
);
echo json_encode(['sucesso' => true, 'usuarios' => $users]);
