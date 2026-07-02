<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
header('Content-Type: application/json');

$auth = new AuthenticationService();
if (!$auth->validateSession()) { http_response_code(401); echo json_encode(['erro' => 'Não autenticado']); exit; }

$user = $auth->getCurrentUser();
if (($user['perfil'] ?? '') !== 'admin_interno') {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso negado']);
    exit;
}

$db     = Database::getInstance();
$body   = json_decode(file_get_contents('php://input'), true) ?? [];
$action = trim($body['action'] ?? '');

function relatoriosBiAplicacaoId($db): ?int {
    $row = $db->fetchOne("SELECT id FROM aplicacoes WHERE slug = 'relatorios-bi'");
    return $row ? (int)$row['id'] : null;
}

try {

    // action=get — catálogo completo, relatórios já configurados para o cliente (com
    // permissões por usuário) e a lista de usuários vinculados ao cliente.
    if ($action === 'get') {
        $clienteId = (int)($body['cliente_id'] ?? 0);
        if (!$clienteId) { echo json_encode(['erro' => 'cliente_id inválido']); exit; }

        $catalogo = $db->fetchAll(
            "SELECT id, slug, nome_amigavel FROM relatorios_bi ORDER BY grupo, ordem, nome_amigavel"
        );

        $usuarios = $db->fetchAll(
            "SELECT u.id AS usuario_id, u.nome, u.username
               FROM cliente_usuarios cu
               JOIN usuarios u ON u.id = cu.usuario_id
              WHERE cu.cliente_id = :c
              ORDER BY u.nome",
            ['c' => $clienteId]
        );

        $descricao    = null;
        $configurados = [];
        $aplicacaoId  = relatoriosBiAplicacaoId($db);
        if ($aplicacaoId) {
            $ca = $db->fetchOne(
                "SELECT id, config_extra, descricao FROM cliente_aplicacoes
                  WHERE cliente_id = :c AND aplicacao_id = :a AND ativo = TRUE",
                ['c' => $clienteId, 'a' => $aplicacaoId]
            );
            if ($ca) {
                $descricao = $ca['descricao'];
                $slugsHabilitados = [];
                if ($ca['config_extra']) {
                    $extra = json_decode($ca['config_extra'], true) ?? [];
                    // Formato antigo (pré per-report per-user) já era array de slugs — segue igual.
                    $slugsHabilitados = is_array($extra['relatorios'] ?? null) ? $extra['relatorios'] : [];
                }

                if ($slugsHabilitados) {
                    $permissoes = $db->fetchAll(
                        "SELECT relatorio_id, usuario_id, pode_ver, pode_criar_portal
                           FROM relatorio_usuario_permissoes
                          WHERE cliente_aplicacao_id = :ca",
                        ['ca' => $ca['id']]
                    );
                    $permPorRelatorio = [];
                    foreach ($permissoes as $p) {
                        $permPorRelatorio[(int)$p['relatorio_id']][] = [
                            'usuario_id'        => (int)$p['usuario_id'],
                            'pode_ver'          => (bool)$p['pode_ver'],
                            'pode_criar_portal' => (bool)$p['pode_criar_portal'],
                        ];
                    }

                    $catalogoPorSlug = [];
                    foreach ($catalogo as $r) { $catalogoPorSlug[$r['slug']] = $r; }

                    foreach ($slugsHabilitados as $slug) {
                        if (!isset($catalogoPorSlug[$slug])) continue; // relatório removido do catálogo
                        $r = $catalogoPorSlug[$slug];
                        $configurados[] = [
                            'slug'          => $r['slug'],
                            'nome_amigavel' => $r['nome_amigavel'],
                            'permissoes'    => $permPorRelatorio[(int)$r['id']] ?? [],
                        ];
                    }
                }
            }
        }

        echo json_encode([
            'sucesso'   => true,
            'catalogo'  => $catalogo,
            'relatorios' => $configurados,
            'usuarios'  => $usuarios,
            'descricao' => $descricao,
        ]);
        exit;
    }

    // action=save — substitui por completo a configuração do cliente: relatórios
    // habilitados + permissões por usuário de cada um.
    if ($action === 'save') {
        $clienteId = (int)($body['cliente_id'] ?? 0);
        $descricao = trim($body['descricao'] ?? '');
        if (!$clienteId) { echo json_encode(['erro' => 'cliente_id inválido']); exit; }
        if (!$descricao) { echo json_encode(['erro' => 'Descrição é obrigatória']); exit; }

        $relatoriosBody = is_array($body['relatorios'] ?? null) ? $body['relatorios'] : [];

        $aplicacaoId = relatoriosBiAplicacaoId($db);
        if (!$aplicacaoId) { echo json_encode(['erro' => 'Aplicação relatorios-bi não encontrada']); exit; }

        // Valida os relatórios recebidos contra o catálogo real (nunca confia no slug do cliente).
        $idPorSlug = [];
        foreach ($db->fetchAll("SELECT id, slug FROM relatorios_bi") as $r) {
            $idPorSlug[$r['slug']] = (int)$r['id'];
        }

        $slugsValidos    = [];
        $entradasPorRel  = []; // relatorio_id => [ ['usuario_id'=>, 'pode_ver'=>, 'pode_criar_portal'=>], ... ]
        foreach ($relatoriosBody as $rel) {
            $slug = (string)($rel['slug'] ?? '');
            if (!isset($idPorSlug[$slug])) continue; // ignora slug desconhecido
            $slugsValidos[] = $slug;
            $relatorioId = $idPorSlug[$slug];

            $linhas = [];
            foreach ((is_array($rel['permissoes'] ?? null) ? $rel['permissoes'] : []) as $p) {
                $usuarioId = (int)($p['usuario_id'] ?? 0);
                if (!$usuarioId) continue;
                $podeCriarPortal = !empty($p['pode_criar_portal']);
                // Regra de negócio: não é possível criar portal sem poder ver o relatório.
                $podeVer = $podeCriarPortal ? true : !empty($p['pode_ver']);
                if (!$podeVer && !$podeCriarPortal) continue; // sem nenhuma permissão, não grava linha
                $linhas[] = ['usuario_id' => $usuarioId, 'pode_ver' => $podeVer, 'pode_criar_portal' => $podeCriarPortal];
            }
            $entradasPorRel[$relatorioId] = $linhas;
        }
        $slugsValidos = array_values(array_unique($slugsValidos));

        $configExtra = json_encode(['relatorios' => $slugsValidos]);

        $ca = $db->fetchOne(
            "SELECT id FROM cliente_aplicacoes WHERE cliente_id = :c AND aplicacao_id = :a",
            ['c' => $clienteId, 'a' => $aplicacaoId]
        );
        if ($ca) {
            $caId = (int)$ca['id'];
            $db->execute(
                "UPDATE cliente_aplicacoes SET config_extra = :extra::jsonb, descricao = :desc, ativo = TRUE WHERE id = :id",
                ['extra' => $configExtra, 'desc' => $descricao, 'id' => $caId]
            );
        } else {
            $db->execute(
                "INSERT INTO cliente_aplicacoes (cliente_id, aplicacao_id, ativo, config_extra, descricao)
                 VALUES (:c, :a, TRUE, :extra::jsonb, :desc)",
                ['c' => $clienteId, 'a' => $aplicacaoId, 'extra' => $configExtra, 'desc' => $descricao]
            );
            $caId = (int)$db->getLastInsertId('cliente_aplicacoes_id_seq');
        }

        // Substitui por completo as permissões desta instância — mesma semântica de
        // "bulk replace" do botão Salvar (estado local vira o estado salvo por inteiro).
        $db->execute("DELETE FROM relatorio_usuario_permissoes WHERE cliente_aplicacao_id = :ca", ['ca' => $caId]);
        foreach ($entradasPorRel as $relatorioId => $linhas) {
            foreach ($linhas as $linha) {
                $db->execute(
                    "INSERT INTO relatorio_usuario_permissoes
                        (cliente_aplicacao_id, relatorio_id, usuario_id, pode_ver, pode_criar_portal)
                     VALUES (:ca, :r, :u, :ver, :portal)",
                    [
                        'ca'     => $caId,
                        'r'      => $relatorioId,
                        'u'      => $linha['usuario_id'],
                        'ver'    => $linha['pode_ver'] ? 'true' : 'false',
                        'portal' => $linha['pode_criar_portal'] ? 'true' : 'false',
                    ]
                );
            }
        }

        echo json_encode(['sucesso' => true]);
        exit;
    }

    if ($action === 'deactivate') {
        $clienteId = (int)($body['cliente_id'] ?? 0);
        if (!$clienteId) { echo json_encode(['erro' => 'cliente_id inválido']); exit; }

        $aplicacaoId = relatoriosBiAplicacaoId($db);
        if (!$aplicacaoId) { echo json_encode(['erro' => 'Aplicação relatorios-bi não encontrada']); exit; }

        // Apenas desativa — não deleta o registro nem as permissões (config_extra/descricao/
        // relatorio_usuario_permissoes ficam preservados, prontos se reativado depois).
        $db->execute(
            "UPDATE cliente_aplicacoes SET ativo = FALSE WHERE cliente_id = :c AND aplicacao_id = :a",
            ['c' => $clienteId, 'a' => $aplicacaoId]
        );

        echo json_encode(['sucesso' => true]);
        exit;
    }

    http_response_code(400);
    echo json_encode(['erro' => 'Ação inválida']);

} catch (Exception $e) {
    error_log('[relatorio-acesso] ' . $e->getMessage());
    echo json_encode(['erro' => 'Erro interno']);
}
