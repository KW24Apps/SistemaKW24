<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
header('Content-Type: application/json');
$auth = new AuthenticationService();
if (!$auth->validateSession()) { http_response_code(401); echo json_encode(['erro'=>'Não autenticado']); exit; }
$body = json_decode(file_get_contents('php://input'), true);
$id   = (int)($body['id'] ?? 0);
if (!$id) { echo json_encode(['erro'=>'ID inválido']); exit; }
// Não deixa excluir o próprio usuário logado
session_start();
if ($id === (int)($_SESSION['user_id'] ?? 0)) { echo json_encode(['erro'=>'Não pode excluir o próprio usuário']); exit; }
try {
    $db = Database::getInstance();
    $db->execute("DELETE FROM usuarios WHERE id=:id", ['id'=>$id]);
    echo json_encode(['sucesso'=>true]);
} catch (Exception $e) { echo json_encode(['erro'=>$e->getMessage()]); }
