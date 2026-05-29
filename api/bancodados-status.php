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
            ca.running_since,
            ca.last_run_started_at,
            ca.ativo
        FROM cliente_aplicacoes ca
        JOIN clientes   c ON c.id = ca.cliente_id
        JOIN aplicacoes a ON a.id = ca.aplicacao_id
        WHERE a.slug = 'BancoDados'
        ORDER BY ca.last_synced_at DESC NULLS LAST
    ");

    // Últimas execuções agrupadas por cliente + hora (= uma "rodada" de sync)
    $runs = $db->fetchAll("
        SELECT
            sh.cliente_id,
            c.nome                          AS cliente_nome,
            DATE_TRUNC('hour', sh.executado_em) AS run_hora,
            MIN(sh.executado_em)            AS iniciou_em,
            MAX(sh.executado_em)            AS terminou_em,
            SUM(sh.registros)               AS total_registros,
            COUNT(*)                        AS total_tabelas,
            SUM(CASE WHEN sh.status='erro' THEN 1 ELSE 0 END) AS total_erros,
            JSON_AGG(
                JSON_BUILD_OBJECT(
                    'entidade',     sh.entidade,
                    'registros',    sh.registros,
                    'status',       sh.status,
                    'executado_em', sh.executado_em
                ) ORDER BY sh.executado_em
            ) AS entidades
        FROM sync_historico sh
        JOIN clientes c ON c.id = sh.cliente_id
        GROUP BY sh.cliente_id, c.nome, DATE_TRUNC('hour', sh.executado_em)
        ORDER BY MAX(sh.executado_em) DESC
        LIMIT 10
    ");

    // Decodifica o JSON das entidades e aplica nomes amigáveis
    foreach ($runs as &$run) {
        $ents = json_decode($run['entidades'], true) ?? [];
        foreach ($ents as &$e) {
            $e['entidade_label'] = $labelMap[$e['entidade']] ?? $e['entidade'];
        }
        $run['entidades'] = $ents;
    }
    unset($run);

    // histórico legado — mantido para compatibilidade
    $historico = [];

    // Mapa de nomes amigáveis: chave técnica → label legível
    $labelMap = [
        'usuarios'  => 'Usuários',
        'pipelines' => 'Pipelines',
        'etapas'    => 'Etapas',
        'empresas'  => 'Empresas',
        'contatos'  => 'Contatos',
    ];
    // Adiciona entidades específicas de cada cliente (crm_2, crm_1126, etc.)
    foreach ($clientes as $cl) {
        $cfg = json_decode($cl['config_extra'] ?? '{}', true);
        foreach ($cfg['entities'] ?? [] as $e) {
            $key = ($e['type'] ?? 'crm') . '_' . $e['id'];
            $labelMap[$key] = $e['label'] ?? $e['table_base_name'];
        }
    }
    // Aplica o mapa ao histórico
    foreach ($historico as &$h) {
        $h['entidade_label'] = $labelMap[$h['entidade']] ?? $h['entidade'];
    }
    unset($h);

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

        // Em andamento = running_since definido nos últimos 4h
        $runningSince = $cl['running_since'];
        $isRunning    = $runningSince && (time() - strtotime($runningSince)) < 4 * 3600;

        // Status visual
        if ($isRunning) {
            $statusLabel = 'Em andamento';
            $statusCor   = 'yellow';
        } elseif (!$lastSync) {
            $statusLabel = 'Nunca sincronizado';
            $statusCor   = 'gray';
        } else {
            $statusLabel = 'Concluído';
            $statusCor   = 'green';
        }

        // Calcula duração do último sync completo
        $startedAt = $cl['last_run_started_at'];
        $durMin    = null;
        if ($startedAt && $lastSync) {
            $durSec = strtotime($lastSync) - strtotime($startedAt);
            $durMin = $durSec > 0 ? round($durSec / 60, 1) : null;
        }

        $resultado[] = [
            'cliente_id'    => $cl['cliente_id'],
            'cliente_nome'  => $cl['cliente_nome'],
            'db_name'       => $dbName,
            'ativo'         => $cl['ativo'],
            'intervalo_h'   => $intervalo,
            'last_synced'   => $lastSync,
            'run_started'   => $startedAt,
            'running_since' => $runningSince,
            'duracao_min'   => $durMin,
            'next_sync'     => $nextSyncTs ? date('Y-m-d H:i:s', $nextSyncTs) : null,
            'status_label'  => $statusLabel,
            'status_cor'    => $statusCor,
            'entidades'    => array_map(fn($e) => [
                'label'    => $e['label'] ?? $e['table_base_name'],
                'tabela'   => $e['table_base_name'],
                'categorias'=> count($e['categories'] ?? [])
            ], $entidades),
        ];
    }

    echo json_encode(['sucesso' => true, 'clientes' => $resultado, 'runs' => $runs]);

} catch (Exception $e) { echo json_encode(['erro' => $e->getMessage()]); }
