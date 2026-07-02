<?php
/**
 * Acesso.php — helpers de autorização do sistema de perfis/permissões por relatório.
 *
 * Perfis:
 *   admin_interno   → acesso total (sem restrição de empresa/relatório)
 *   admin_cliente   → escopado às empresas vinculadas a ele (via cliente_usuarios);
 *                     gerencia usuários dessas empresas e relatórios que ele possui
 *   usuario_cliente → sem gestão (só a própria ficha, leitura)
 *
 * Requer sessão iniciada (session_start) e Database.php carregado. As funções que
 * recebem $db esperam a instância de Database (fetchAll/fetchOne com params nomeados).
 */

function acessoPerfilLogado(): string { return $_SESSION['user_role'] ?? ''; }
function acessoUsuarioIdLogado(): int  { return (int)($_SESSION['user_id'] ?? 0); }
function ehAdminInterno(): bool { return acessoPerfilLogado() === 'admin_interno'; }
function ehAdminCliente(): bool { return acessoPerfilLogado() === 'admin_cliente'; }

/** cliente_ids das empresas vinculadas a um usuário (via cliente_usuarios). */
function empresasDoUsuario($db, int $usuarioId): array {
    $rows = $db->fetchAll(
        "SELECT cliente_id FROM cliente_usuarios WHERE usuario_id = :id",
        ['id' => $usuarioId]
    );
    return array_map('intval', array_column($rows, 'cliente_id'));
}

/** ids de relatórios que o usuário tem em usuario_relatorio_acesso. */
function relatoriosIdsDoUsuario($db, int $usuarioId): array {
    $rows = $db->fetchAll(
        "SELECT relatorio_id FROM usuario_relatorio_acesso WHERE usuario_id = :id",
        ['id' => $usuarioId]
    );
    return array_map('intval', array_column($rows, 'relatorio_id'));
}

/** slugs de relatórios do usuário com pode_portal = TRUE. */
function relatoriosPortalSlugsDoUsuario($db, int $usuarioId): array {
    $rows = $db->fetchAll(
        "SELECT rb.slug
           FROM usuario_relatorio_acesso ura
           JOIN relatorios_bi rb ON rb.id = ura.relatorio_id
          WHERE ura.usuario_id = :id AND ura.pode_portal = TRUE",
        ['id' => $usuarioId]
    );
    return array_column($rows, 'slug');
}

/** TRUE se o usuário logado (admin_cliente) possui a empresa $clienteId. */
function adminClienteTemEmpresa($db, int $clienteId): bool {
    return in_array($clienteId, empresasDoUsuario($db, acessoUsuarioIdLogado()), true);
}

/** TRUE se o usuário-alvo pertence a alguma empresa do admin_cliente logado. */
function usuarioNasEmpresasDoAdmin($db, int $alvoId): bool {
    $r = $db->fetchOne(
        "SELECT 1 AS ok
           FROM cliente_usuarios cu
          WHERE cu.usuario_id = :alvo
            AND cu.cliente_id IN (SELECT cliente_id FROM cliente_usuarios WHERE usuario_id = :admin)
          LIMIT 1",
        ['alvo' => $alvoId, 'admin' => acessoUsuarioIdLogado()]
    );
    return (bool)$r;
}

/**
 * Substitui (apaga e reinsere) os acessos de um usuário. Se o logado for
 * admin_cliente, aplica scoping: só concede relatórios que ele próprio possui e
 * pode_portal apenas onde ele mesmo o tem — demais entradas ignoradas em silêncio.
 */
function salvarAcessosUsuario($db, int $uid, array $acessos): void {
    $permitidos = null; $portalPermitido = [];
    if (ehAdminCliente()) {
        $permitidos = relatoriosIdsDoUsuario($db, acessoUsuarioIdLogado());
        foreach ($db->fetchAll(
            "SELECT relatorio_id, pode_portal FROM usuario_relatorio_acesso WHERE usuario_id = :id",
            ['id' => acessoUsuarioIdLogado()]
        ) as $pr) { $portalPermitido[(int)$pr['relatorio_id']] = (bool)$pr['pode_portal']; }
    }
    $db->execute("DELETE FROM usuario_relatorio_acesso WHERE usuario_id = :id", ['id' => $uid]);
    foreach ($acessos as $a) {
        $rid    = (int)($a['relatorio_id'] ?? 0);
        $portal = !empty($a['pode_portal']);
        if (!$rid) continue;
        if ($permitidos !== null) {
            if (!in_array($rid, $permitidos, true)) continue;
            if ($portal && empty($portalPermitido[$rid])) $portal = false;
        }
        $db->execute(
            "INSERT INTO usuario_relatorio_acesso (usuario_id, relatorio_id, pode_portal)
             VALUES (:uid, :rid, :portal)
             ON CONFLICT (usuario_id, relatorio_id) DO NOTHING",
            ['uid' => $uid, 'rid' => $rid, 'portal' => $portal ? 'true' : 'false']
        );
    }
}
