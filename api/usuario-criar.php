<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Acesso.php';
header('Content-Type: application/json');
$auth = new AuthenticationService();
if (!$auth->validateSession()) { http_response_code(401); echo json_encode(['erro'=>'Não autenticado']); exit; }

$perfilLogado = acessoPerfilLogado();
if ($perfilLogado !== 'admin_interno' && $perfilLogado !== 'admin_cliente') {
    http_response_code(403); echo json_encode(['erro'=>'Acesso negado']); exit;
}

$body = json_decode(file_get_contents('php://input'), true);
$nome       = trim($body['nome']     ?? '');
$cpf        = trim($body['cpf']      ?? '');
$username   = trim($body['username'] ?? '');
$email      = trim($body['email']    ?? '') ?: null;
$senha      = $body['senha']         ?? '';
$perfil     = $body['perfil']        ?? 'usuario_cliente';
$profile_id = isset($body['profile_id']) && $body['profile_id'] ? (int)$body['profile_id'] : null;
$cliente_id = isset($body['cliente_id']) && $body['cliente_id'] ? (int)$body['cliente_id'] : null;
$acessos    = is_array($body['acessos'] ?? null) ? $body['acessos'] : [];
if (!$nome || !$cpf || !$username || !$senha) { echo json_encode(['erro'=>'Campos obrigatórios']); exit; }
if (strlen($senha) < 6) { echo json_encode(['erro'=>'Senha muito curta']); exit; }

$db = Database::getInstance();

// admin_cliente: não pode criar admin_interno e só pode vincular às SUAS empresas
if (ehAdminCliente()) {
    if ($perfil === 'admin_interno') {
        http_response_code(403); echo json_encode(['erro'=>'Sem permissão para criar Admin Interno']); exit;
    }
    if (!in_array($perfil, ['admin_cliente','usuario_cliente'], true)) $perfil = 'usuario_cliente';
    if ($cliente_id && !adminClienteTemEmpresa($db, $cliente_id)) {
        http_response_code(403); echo json_encode(['erro'=>'Empresa fora do seu escopo']); exit;
    }
    $profile_id = null; // admin_cliente não gerencia permission_profiles
}

try {
    $exists = $db->fetchOne("SELECT id FROM usuarios WHERE username=:u OR cpf=:c", ['u'=>$username,'c'=>$cpf]);
    if ($exists) { echo json_encode(['erro'=>'Username ou CPF já cadastrado']); exit; }
    $hash = password_hash($senha, PASSWORD_DEFAULT);
    $db->execute(
        "INSERT INTO usuarios (nome,cpf,username,senha,email,perfil,profile_id,criado_por_id,ativo)
         VALUES(:nome,:cpf,:username,:senha,:email,:perfil,:profile_id,:criado_por,TRUE)",
        ['nome'=>$nome,'cpf'=>$cpf,'username'=>$username,'senha'=>$hash,'email'=>$email,
         'perfil'=>$perfil,'profile_id'=>$profile_id,'criado_por'=>acessoUsuarioIdLogado()]
    );
    $id = (int)$db->getLastInsertId('usuarios_id_seq');
    if ($cliente_id) {
        $db->execute(
            "INSERT INTO cliente_usuarios (cliente_id, usuario_id) VALUES (:c, :u) ON CONFLICT DO NOTHING",
            ['c' => $cliente_id, 'u' => $id]
        );
    }
    // Acessos a relatórios (respeita scoping de admin_cliente via helper)
    if ($acessos) { salvarAcessosUsuario($db, $id, $acessos); }
    echo json_encode(['sucesso'=>true,'id'=>$id]);
} catch (Exception $e) { echo json_encode(['erro'=>$e->getMessage()]); }
