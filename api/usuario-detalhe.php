<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Acesso.php';
header('Content-Type: application/json');
$auth = new AuthenticationService();
if (!$auth->validateSession()) { http_response_code(401); echo json_encode(['erro'=>'Não autenticado']); exit; }
$id = (int)($_GET['id'] ?? 0);
if (!$id) { echo json_encode(['erro'=>'ID inválido']); exit; }
$db  = Database::getInstance();

// Escopo de leitura: admin_interno vê todos; admin_cliente só das suas empresas;
// usuario_cliente só a própria ficha.
if (ehAdminCliente()) {
    if ($id !== acessoUsuarioIdLogado() && !usuarioNasEmpresasDoAdmin($db, $id)) {
        http_response_code(403); echo json_encode(['erro'=>'Acesso negado']); exit;
    }
} elseif (!ehAdminInterno()) {
    if ($id !== acessoUsuarioIdLogado()) {
        http_response_code(403); echo json_encode(['erro'=>'Acesso negado']); exit;
    }
}

$usr = $db->fetchOne(
    "SELECT u.id, u.nome, u.username, u.email, u.cargo, u.telefone, u.perfil,
            u.ativo, u.ultimo_acesso, u.profile_id, pp.nome AS profile_nome,
            u.criado_por_id, u2.nome AS criado_por_nome
       FROM usuarios u
  LEFT JOIN permission_profiles pp ON pp.id = u.profile_id
  LEFT JOIN usuarios u2 ON u2.id = u.criado_por_id
      WHERE u.id = :id",
    ['id' => $id]
);
if (!$usr) { echo json_encode(['erro'=>'Usuário não encontrado']); exit; }

$criadoPor = $usr['criado_por_id']
    ? ['id' => (int)$usr['criado_por_id'], 'nome' => $usr['criado_por_nome']]
    : null;

$clientes = $db->fetchAll(
    "SELECT c.id, c.nome FROM cliente_usuarios cu JOIN clientes c ON c.id = cu.cliente_id
     WHERE cu.usuario_id = :id ORDER BY c.nome",
    ['id' => $id]
);

$acessos = $db->fetchAll(
    "SELECT ura.relatorio_id, ura.pode_portal, rb.slug, rb.nome_amigavel, rb.grupo
       FROM usuario_relatorio_acesso ura
       JOIN relatorios_bi rb ON rb.id = ura.relatorio_id
      WHERE ura.usuario_id = :id
      ORDER BY rb.grupo, rb.ordem",
    ['id' => $id]
);
foreach ($acessos as $i => $a) {
    $acessos[$i]['relatorio_id'] = (int)$a['relatorio_id'];
    $acessos[$i]['pode_portal']  = (bool)$a['pode_portal'];
}

echo json_encode([
    'usuario'    => $usr,
    'criado_por' => $criadoPor,
    'clientes'   => $clientes,
    'acessos'    => $acessos,
]);
