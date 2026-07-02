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

    if ($action === 'get') {
        $clienteId = (int)($body['cliente_id'] ?? 0);
        if (!$clienteId) { echo json_encode(['erro' => 'cliente_id inválido']); exit; }

        $relatorios = $db->fetchAll(
            "SELECT id, slug, nome_amigavel FROM relatorios_bi ORDER BY grupo, ordem, nome_amigavel"
        );

        $selecionados = [];
        $descricao    = null;
        $aplicacaoId = relatoriosBiAplicacaoId($db);
        if ($aplicacaoId) {
            $ca = $db->fetchOne(
                "SELECT config_extra, descricao FROM cliente_aplicacoes
                  WHERE cliente_id = :c AND aplicacao_id = :a AND ativo = TRUE",
                ['c' => $clienteId, 'a' => $aplicacaoId]
            );
            if ($ca) {
                $descricao = $ca['descricao'];
                if ($ca['config_extra']) {
                    $extra = json_decode($ca['config_extra'], true) ?? [];
                    $selecionados = is_array($extra['relatorios'] ?? null) ? $extra['relatorios'] : [];
                }
            }
        }

        $usuarios = $db->fetchAll(
            "SELECT u.id AS usuario_id, u.nome, u.username,
                    cu.pode_ver_relatorio, cu.pode_criar_portal
               FROM cliente_usuarios cu
               JOIN usuarios u ON u.id = cu.usuario_id
              WHERE cu.cliente_id = :c
              ORDER BY u.nome",
            ['c' => $clienteId]
        );
        foreach ($usuarios as $i => $u) {
            $usuarios[$i]['pode_ver_relatorio'] = (bool)$u['pode_ver_relatorio'];
            $usuarios[$i]['pode_criar_portal']  = (bool)$u['pode_criar_portal'];
        }

        echo json_encode([
            'sucesso'              => true,
            'relatorios'           => $relatorios,
            'relatorios_selecionados' => $selecionados,
            'usuarios'              => $usuarios,
            'descricao'             => $descricao,
        ]);
        exit;
    }

    if ($action === 'save') {
        $clienteId = (int)($body['cliente_id'] ?? 0);
        $descricao = trim($body['descricao'] ?? '');
        if (!$clienteId) { echo json_encode(['erro' => 'cliente_id inválido']); exit; }
        if (!$descricao) { echo json_encode(['erro' => 'Descrição é obrigatória']); exit; }

        $relatoriosSlugs = is_array($body['relatorios'] ?? null)
            ? array_values(array_unique(array_map('strval', $body['relatorios'])))
            : [];
        $usuarios = is_array($body['usuarios'] ?? null) ? $body['usuarios'] : [];

        $aplicacaoId = relatoriosBiAplicacaoId($db);
        if (!$aplicacaoId) { echo json_encode(['erro' => 'Aplicação relatorios-bi não encontrada']); exit; }

        $configExtra = json_encode(['relatorios' => $relatoriosSlugs]);

        $ca = $db->fetchOne(
            "SELECT id FROM cliente_aplicacoes WHERE cliente_id = :c AND aplicacao_id = :a",
            ['c' => $clienteId, 'a' => $aplicacaoId]
        );
        if ($ca) {
            $db->execute(
                "UPDATE cliente_aplicacoes SET config_extra = :extra::jsonb, descricao = :desc, ativo = TRUE WHERE id = :id",
                ['extra' => $configExtra, 'desc' => $descricao, 'id' => $ca['id']]
            );
        } else {
            $db->execute(
                "INSERT INTO cliente_aplicacoes (cliente_id, aplicacao_id, ativo, config_extra, descricao)
                 VALUES (:c, :a, TRUE, :extra::jsonb, :desc)",
                ['c' => $clienteId, 'a' => $aplicacaoId, 'extra' => $configExtra, 'desc' => $descricao]
            );
        }

        foreach ($usuarios as $u) {
            $usuarioId = (int)($u['usuario_id'] ?? 0);
            if (!$usuarioId) continue;
            $podeCriarPortal  = !empty($u['pode_criar_portal']);
            // Regra de negócio: não é possível criar portal sem poder ver o relatório.
            $podeVerRelatorio = $podeCriarPortal ? true : !empty($u['pode_ver_relatorio']);
            $db->execute(
                "UPDATE cliente_usuarios SET pode_ver_relatorio = :ver, pode_criar_portal = :portal
                  WHERE cliente_id = :c AND usuario_id = :u",
                ['ver' => $podeVerRelatorio ? 'true' : 'false', 'portal' => $podeCriarPortal ? 'true' : 'false',
                 'c' => $clienteId, 'u' => $usuarioId]
            );
        }

        echo json_encode(['sucesso' => true]);
        exit;
    }

    if ($action === 'deactivate') {
        $clienteId = (int)($body['cliente_id'] ?? 0);
        if (!$clienteId) { echo json_encode(['erro' => 'cliente_id inválido']); exit; }

        $aplicacaoId = relatoriosBiAplicacaoId($db);
        if (!$aplicacaoId) { echo json_encode(['erro' => 'Aplicação relatorios-bi não encontrada']); exit; }

        // Apenas desativa — não deleta o registro (config_extra/descricao ficam preservados).
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
