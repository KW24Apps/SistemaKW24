<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Acesso.php';
header('Content-Type: application/json');
$auth = new AuthenticationService();
if (!$auth->validateSession()) { http_response_code(401); echo json_encode(['erro'=>'Não autenticado']); exit; }
$body = json_decode(file_get_contents('php://input'), true);
$id   = (int)($body['id'] ?? 0);
if (!$id) { echo json_encode(['erro'=>'ID inválido']); exit; }
$db = Database::getInstance();

// Autorização: admin_interno edita todos; admin_cliente só os das suas empresas;
// usuario_cliente não edita ninguém.
if (ehAdminCliente()) {
    if (!usuarioNasEmpresasDoAdmin($db, $id)) {
        http_response_code(403); echo json_encode(['erro'=>'Acesso negado']); exit;
    }
} elseif (!ehAdminInterno()) {
    http_response_code(403); echo json_encode(['erro'=>'Acesso negado']); exit;
}

$permitidos = ['nome','email','cargo','telefone','username','profile_id','perfil'];
if (ehAdminCliente()) {
    // admin_cliente não gerencia permission_profiles e não promove a admin_interno
    $permitidos = ['nome','email','cargo','telefone','username','perfil'];
    if (($body['perfil'] ?? '') === 'admin_interno') {
        http_response_code(403); echo json_encode(['erro'=>'Sem permissão para definir Admin Interno']); exit;
    }
}
$sets=[]; $params=['id'=>$id];
foreach ($permitidos as $c) {
    if (array_key_exists($c, $body)) { $sets[]="{$c}=:{$c}"; $params[$c]=$body[$c]; }
}
if (empty($sets)) { echo json_encode(['erro'=>'Nada para atualizar']); exit; }
try {
    $db = Database::getInstance();
    $db->execute("UPDATE usuarios SET ".implode(',',$sets)." WHERE id=:id", $params);
    echo json_encode(['sucesso'=>true]);
} catch (Exception $e) { echo json_encode(['erro'=>$e->getMessage()]); }
