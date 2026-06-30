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
$usr = $db->fetchOne(
    "SELECT u.id, u.nome, u.username, u.email, u.cargo, u.telefone, u.perfil,
            u.ativo, u.ultimo_acesso, u.profile_id, pp.nome AS profile_nome
       FROM usuarios u
  LEFT JOIN permission_profiles pp ON pp.id = u.profile_id
      WHERE u.id = :id",
    ['id' => $id]
);
if (!$usr) { echo json_encode(['erro'=>'Usuário não encontrado']); exit; }

$clientes = $db->fetchAll(
    "SELECT c.id, c.nome FROM cliente_usuarios cu JOIN clientes c ON c.id = cu.cliente_id
     WHERE cu.usuario_id = :id ORDER BY c.nome",
    ['id' => $id]
);
echo json_encode(['usuario' => $usr, 'clientes' => $clientes]);
