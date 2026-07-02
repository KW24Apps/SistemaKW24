<?php
/**
 * usuario-acessos.php — acessos granulares de usuário × relatório (usuario_relatorio_acesso).
 * Ações: get (acessos de um usuário), list-relatorios (checkboxes agrupados), salvar.
 * Autorização: admin_interno total; admin_cliente escopado às suas empresas/relatórios;
 * usuario_cliente → 403.
 */
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
require_once __DIR__ . '/../helpers/Acesso.php';
header('Content-Type: application/json');

$auth = new AuthenticationService();
if (!$auth->validateSession()) { http_response_code(401); echo json_encode(['erro' => 'Não autenticado']); exit; }

$perfil = acessoPerfilLogado();
if ($perfil !== 'admin_interno' && $perfil !== 'admin_cliente') {
    http_response_code(403); echo json_encode(['erro' => 'Acesso negado']); exit;
}

$db     = Database::getInstance();
$action = $_GET['action'] ?? '';

try {
    // ── GET: acessos de um usuário ──────────────────────────────────────────
    if ($action === 'get') {
        $uid = (int)($_GET['usuario_id'] ?? 0);
        if (!$uid) { echo json_encode(['erro' => 'usuario_id inválido']); exit; }
        if (ehAdminCliente() && !usuarioNasEmpresasDoAdmin($db, $uid)) {
            http_response_code(403); echo json_encode(['erro' => 'Acesso negado']); exit;
        }
        $rows = $db->fetchAll(
            "SELECT ura.relatorio_id, ura.pode_portal, rb.slug, rb.nome_amigavel, rb.grupo
               FROM usuario_relatorio_acesso ura
               JOIN relatorios_bi rb ON rb.id = ura.relatorio_id
              WHERE ura.usuario_id = :id
              ORDER BY rb.grupo, rb.ordem",
            ['id' => $uid]
        );
        foreach ($rows as $i => $r) {
            $rows[$i]['relatorio_id'] = (int)$r['relatorio_id'];
            $rows[$i]['pode_portal']  = (bool)$r['pode_portal'];
        }
        echo json_encode(['sucesso' => true, 'acessos' => $rows]);
        exit;
    }

    // ── GET: todos os relatórios visíveis (agrupados) p/ montar os checkboxes ─
    if ($action === 'list-relatorios') {
        $rows = $db->fetchAll(
            "SELECT id, slug, nome_amigavel, grupo
               FROM relatorios_bi WHERE visivel = TRUE ORDER BY grupo, ordem"
        );
        if (ehAdminCliente()) {
            // admin_cliente só enxerga os relatórios que ele próprio possui;
            // admin_pode_portal informa ao frontend se ele pode conceder portal.
            $meus = relatoriosIdsDoUsuario($db, acessoUsuarioIdLogado());
            $portalMap = [];
            foreach ($db->fetchAll(
                "SELECT relatorio_id, pode_portal FROM usuario_relatorio_acesso WHERE usuario_id = :id",
                ['id' => acessoUsuarioIdLogado()]
            ) as $pr) { $portalMap[(int)$pr['relatorio_id']] = (bool)$pr['pode_portal']; }

            $out = [];
            foreach ($rows as $r) {
                if (!in_array((int)$r['id'], $meus, true)) continue;
                $r['id'] = (int)$r['id'];
                $r['admin_pode_portal'] = $portalMap[(int)$r['id']] ?? false;
                $out[] = $r;
            }
            echo json_encode(['sucesso' => true, 'relatorios' => $out]);
            exit;
        }
        foreach ($rows as $i => $r) {
            $rows[$i]['id'] = (int)$r['id'];
            $rows[$i]['admin_pode_portal'] = true;   // admin_interno pode tudo
        }
        echo json_encode(['sucesso' => true, 'relatorios' => $rows]);
        exit;
    }

    // ── POST: salvar acessos (apaga e reinsere) ──────────────────────────────
    if ($action === 'salvar' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $body    = json_decode(file_get_contents('php://input'), true) ?? [];
        $uid     = (int)($body['usuario_id'] ?? 0);
        $acessos = is_array($body['acessos'] ?? null) ? $body['acessos'] : [];
        if (!$uid) { echo json_encode(['erro' => 'usuario_id inválido']); exit; }
        if (ehAdminCliente() && !usuarioNasEmpresasDoAdmin($db, $uid)) {
            http_response_code(403); echo json_encode(['erro' => 'Acesso negado']); exit;
        }
        salvarAcessosUsuario($db, $uid, $acessos);
        echo json_encode(['sucesso' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['erro' => 'Ação inválida']);

} catch (Exception $e) {
    error_log('[usuario-acessos] ' . $e->getMessage());
    echo json_encode(['erro' => 'Erro interno']);
}
