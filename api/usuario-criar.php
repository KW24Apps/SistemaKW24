<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
header('Content-Type: application/json');
$auth = new AuthenticationService();
if (!$auth->validateSession()) { http_response_code(401); echo json_encode(['erro'=>'Não autenticado']); exit; }
$body = json_decode(file_get_contents('php://input'), true);
$nome     = trim($body['nome']     ?? '');
$cpf      = trim($body['cpf']      ?? '');
$username = trim($body['username'] ?? '');
$email    = trim($body['email']    ?? '');
$senha    = $body['senha']         ?? '';
$perfil   = $body['perfil']        ?? 'usuario_cliente';
if (!$nome||!$cpf||!$username||!$email||!$senha) { echo json_encode(['erro'=>'Campos obrigatórios']); exit; }
if (strlen($senha) < 6) { echo json_encode(['erro'=>'Senha muito curta']); exit; }
try {
    $db = Database::getInstance();
    $exists = $db->fetchOne("SELECT id FROM usuarios WHERE username=:u OR cpf=:c", ['u'=>$username,'c'=>$cpf]);
    if ($exists) { echo json_encode(['erro'=>'Username ou CPF já cadastrado']); exit; }
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $db->execute("INSERT INTO usuarios (nome,cpf,username,senha,email,perfil,ativo) VALUES(:nome,:cpf,:username,:senha,:email,:perfil,TRUE)",
        ['nome'=>$nome,'cpf'=>$cpf,'username'=>$username,'senha'=>$hash,'email'=>$email,'perfil'=>$perfil]);
    $id = (int)$db->getLastInsertId('usuarios_id_seq');
    echo json_encode(['sucesso'=>true,'id'=>$id]);
} catch (Exception $e) { echo json_encode(['erro'=>$e->getMessage()]); }
