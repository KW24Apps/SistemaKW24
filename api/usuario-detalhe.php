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
$usr = $db->fetchOne("SELECT id,nome,username,email,cargo,telefone,perfil,ativo,ultimo_acesso FROM usuarios WHERE id=:id", ['id'=>$id]);
if (!$usr) { echo json_encode(['erro'=>'Usuário não encontrado']); exit; }
echo json_encode(['usuario' => $usr]);
