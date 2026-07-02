<?php
/**
 * usuario-senha.php — redefinir senha de um usuário.
 * admin_interno: qualquer usuário. admin_cliente: só usuários das suas empresas.
 * usuario_cliente → 403.
 */
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Acesso.php';
header('Content-Type: application/json');
$auth = new AuthenticationService();
if (!$auth->validateSession()) { http_response_code(401); echo json_encode(['erro'=>'Não autenticado']); exit; }

$perfil = acessoPerfilLogado();
if ($perfil !== 'admin_interno' && $perfil !== 'admin_cliente') {
    http_response_code(403); echo json_encode(['erro'=>'Acesso negado']); exit;
}

$body  = json_decode(file_get_contents('php://input'), true) ?? [];
$id    = (int)($body['id'] ?? 0);
$senha = (string)($body['senha'] ?? '');
if (!$id) { echo json_encode(['erro'=>'ID inválido']); exit; }
if (strlen($senha) < 6) { echo json_encode(['erro'=>'Senha deve ter pelo menos 6 caracteres']); exit; }

$db = Database::getInstance();
if (ehAdminCliente() && !usuarioNasEmpresasDoAdmin($db, $id)) {
    http_response_code(403); echo json_encode(['erro'=>'Acesso negado']); exit;
}

try {
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $db->execute("UPDATE usuarios SET senha = :s WHERE id = :id", ['s'=>$hash, 'id'=>$id]);
    echo json_encode(['sucesso'=>true]);
} catch (Exception $e) {
    error_log('[usuario-senha] '.$e->getMessage());
    echo json_encode(['erro'=>'Erro interno']);
}
