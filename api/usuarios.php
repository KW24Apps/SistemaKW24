<?php
/**
 * usuarios.php — listagem (JSON) e empresas do admin logado.
 *   action=list            → usuários (admin_interno: todos; admin_cliente: das suas empresas)
 *   action=minhas-empresas → empresas vinculadas ao logado (dropdown de criar usuário)
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

$db     = Database::getInstance();
$action = $_GET['action'] ?? '';

try {
    if ($action === 'list') {
        $busca = trim($_GET['busca'] ?? '');
        if (ehAdminCliente()) {
            $sql = "SELECT DISTINCT u.id, u.nome, u.username, u.email, u.perfil, u.ativo, u.ultimo_acesso
                      FROM usuarios u
                      JOIN cliente_usuarios cu ON cu.usuario_id = u.id
                     WHERE cu.cliente_id IN (SELECT cliente_id FROM cliente_usuarios WHERE usuario_id = :admin)";
            $params = ['admin' => acessoUsuarioIdLogado()];
            if ($busca !== '') {
                $sql .= " AND (u.nome ILIKE :b OR u.username ILIKE :b OR u.email ILIKE :b)";
                $params['b'] = "%{$busca}%";
            }
            $sql .= " ORDER BY u.nome ASC";
            $rows = $db->fetchAll($sql, $params);
        } else {
            $sql = "SELECT id, nome, username, email, perfil, ativo, ultimo_acesso FROM usuarios";
            $params = [];
            if ($busca !== '') {
                $sql .= " WHERE nome ILIKE :b OR username ILIKE :b OR email ILIKE :b";
                $params['b'] = "%{$busca}%";
            }
            $sql .= " ORDER BY nome ASC";
            $rows = $db->fetchAll($sql, $params);
        }
        foreach ($rows as $i => $r) { $rows[$i]['ativo'] = (bool)$r['ativo']; }
        echo json_encode(['sucesso'=>true, 'usuarios'=>$rows]);
        exit;
    }

    if ($action === 'minhas-empresas') {
        if (ehAdminInterno()) {
            $rows = $db->fetchAll("SELECT id, nome FROM clientes ORDER BY nome");
        } else {
            $rows = $db->fetchAll(
                "SELECT c.id, c.nome FROM cliente_usuarios cu
                   JOIN clientes c ON c.id = cu.cliente_id
                  WHERE cu.usuario_id = :id ORDER BY c.nome",
                ['id' => acessoUsuarioIdLogado()]
            );
        }
        echo json_encode(['sucesso'=>true, 'empresas'=>$rows]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['erro'=>'Ação inválida']);
} catch (Exception $e) {
    error_log('[usuarios] '.$e->getMessage());
    echo json_encode(['erro'=>'Erro interno']);
}
