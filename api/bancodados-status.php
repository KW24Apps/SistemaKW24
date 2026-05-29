<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
header('Content-Type: application/json');

$auth = new AuthenticationService();
if (!$auth->validateSession()) { http_response_code(401); echo json_encode(['erro'=>'Não autenticado']); exit; }

try {
    $db = Database::getInstance();

    // Status atual de cada cliente com BancoDados ativo
    $clientes = $db->fetchAll("
        SELECT
            c.id            AS cliente_id,
            c.nome          AS cliente_nome,
            ca.config_extra,
            ca.last_synced_at,
            ca.ativo
        FROM cliente_aplicacoes ca
        JOIN clientes   c ON c.id = ca.cliente_id
        JOIN aplicacoes a ON a.id = ca.aplicacao_id
        WHERE a.slug = 'BancoDados'
        ORDER BY ca.last_synced_at DESC NULLS LAST
    ");

    // Últimas 20 sincronizações (histórico)
    $historico = $db->fetchAll("
        SELECT
            sh.id,
            c.nome     AS cliente_nome,
            sh.entidade,
            sh.registros,
            sh.status,
            sh.mensagem,
            sh.executado_em
        FROM sync_historico sh
        JOIN clientes c ON c.id = sh.cliente_id
        ORDER BY sh.executado_em DESC
        LIMIT 20
    ");

    // Monta resultado por cliente
    $resultado = [];
    foreach ($clientes as $cl) {
        $config      = json_decode($cl['config_extra'] ?? '{}', true);
        $intervalo   = (int)($config['intervalo_horas'] ?? 6);
        $dbName      = $config['db_name'] ?? '—';
        $entidades   = $config['entities'] ?? [];
        $lastSync    = $cl['last_synced_at'];
        $nextSyncTs  = $lastSync ? strtotime($lastSync) + $intervalo * 3600 : null;
        $agora       = time();

        // Status visual
        if (!$lastSync) {
            $statusLabel = 'Nunca sincronizado';
            $statusCor   = 'gray';
        } elseif ($nextSyncTs < $agora) {
            $statusLabel = 'Em andamento';
            $statusCor   = 'yellow';
        } else {
            $statusLabel = 'Concluído';
            $statusCor   = 'green';
        }

        $resultado[] = [
            'cliente_id'   => $cl['cliente_id'],
            'cliente_nome' => $cl['cliente_nome'],
            'db_name'      => $dbName,
            'ativo'        => $cl['ativo'],
            'intervalo_h'  => $intervalo,
            'last_synced'  => $lastSync,
            'next_sync'    => $nextSyncTs ? date('Y-m-d H:i:s', $nextSyncTs) : null,
            'status_label' => $statusLabel,
            'status_cor'   => $statusCor,
            'entidades'    => array_map(fn($e) => [
                'label'    => $e['label'] ?? $e['table_base_name'],
                'tabela'   => $e['table_base_name'],
                'categorias'=> count($e['categories'] ?? [])
            ], $entidades),
        ];
    }

    echo json_encode(['sucesso' => true, 'clientes' => $resultado, 'historico' => $historico]);

} catch (Exception $e) { echo json_encode(['erro' => $e->getMessage()]); }
