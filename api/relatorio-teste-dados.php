<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
header('Content-Type: application/json');

$auth = new AuthenticationService();
if (!$auth->validateSession()) {
    http_response_code(401);
    echo json_encode(['erro' => 'Não autenticado']);
    exit;
}

try {
    $db = Database::getInstance();

    $totalClientes  = (int)($db->fetchOne("SELECT COUNT(*) AS n FROM clientes")['n'] ?? 0);
    $clientesAtivos = (int)($db->fetchOne("SELECT COUNT(*) AS n FROM clientes WHERE ativo = TRUE")['n'] ?? 0);

    $syncPorDia = $db->fetchAll("
        SELECT
            DATE(executado_em)                                   AS dia,
            COUNT(*)                                             AS operacoes,
            COALESCE(SUM(registros), 0)                          AS registros,
            SUM(CASE WHEN status = 'erro' THEN 1 ELSE 0 END)    AS erros
        FROM sync_historico
        WHERE executado_em >= NOW() - INTERVAL '30 days'
        GROUP BY DATE(executado_em)
        ORDER BY dia ASC
    ");

    $topEntidades = $db->fetchAll("
        SELECT
            entidade,
            COALESCE(SUM(registros), 0) AS total_registros,
            COUNT(*)                    AS execucoes
        FROM sync_historico
        WHERE executado_em >= NOW() - INTERVAL '30 days'
        GROUP BY entidade
        ORDER BY total_registros DESC
        LIMIT 10
    ");

    echo json_encode([
        'sucesso'       => true,
        'kpis'          => [
            'total_clientes'    => $totalClientes,
            'clientes_ativos'   => $clientesAtivos,
            'clientes_inativos' => $totalClientes - $clientesAtivos,
        ],
        'sync_por_dia'  => $syncPorDia,
        'top_entidades' => $topEntidades,
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['erro' => $e->getMessage()]);
}
