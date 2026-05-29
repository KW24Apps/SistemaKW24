<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
header('Content-Type: application/json');

$auth = new AuthenticationService();
if (!$auth->validateSession()) { http_response_code(401); echo json_encode(['erro'=>'Não autenticado']); exit; }

$body        = json_decode(file_get_contents('php://input'), true);
$clienteId   = (int)($body['cliente_id']   ?? 0);
$aplicacaoId = (int)($body['aplicacao_id'] ?? 0);

if (!$clienteId || !$aplicacaoId) { echo json_encode(['erro'=>'Dados inválidos']); exit; }

try {
    $db  = Database::getInstance();
    $row = $db->fetchOne(
        "SELECT ca.config_extra, ca.running_since
         FROM cliente_aplicacoes ca
         JOIN aplicacoes a ON a.id = ca.aplicacao_id
         WHERE ca.cliente_id = :c AND ca.aplicacao_id = :a AND a.slug = 'BancoDados'",
        ['c' => $clienteId, 'a' => $aplicacaoId]
    );

    if (!$row) { echo json_encode(['erro' => 'Configuração não encontrada']); exit; }

    // Verificar se já está rodando (running_since nos últimos 4h)
    if ($row['running_since']) {
        $runningSince = strtotime($row['running_since']);
        if ((time() - $runningSince) < 4 * 3600) {
            echo json_encode(['erro' => 'Sincronização já está em andamento para este cliente.']);
            exit;
        }
    }

    $config = json_decode($row['config_extra'] ?? '{}', true);
    $dbName = $config['db_name'] ?? null;

    if (!$dbName) { echo json_encode(['erro' => 'Nome do banco (db_name) não configurado']); exit; }

    // Marca como rodando ANTES de disparar (evita duplo clique)
    $db->execute(
        "UPDATE cliente_aplicacoes SET running_since = NOW() WHERE cliente_id = :c AND aplicacao_id = :a",
        ['c' => $clienteId, 'a' => $aplicacaoId]
    );

    // PHP CLI — busca binário correto (não usa PHP_BINARY que aponta para FPM)
    $phpBin = '/usr/bin/php8.1';
    if (!file_exists($phpBin)) $phpBin = trim(shell_exec('which php') ?: '/usr/bin/php');
    $script  = '/var/www/dadosgn.kw24.com.br/BitrixDataSync/main.php';
    $logFile = '/tmp/bitrix_sync_' . preg_replace('/[^a-z0-9_]/', '', $dbName) . '.log';

    $cmd = "{$phpBin} {$script} --cliente=" . escapeshellarg($dbName) . " >> {$logFile} 2>&1 &";
    shell_exec($cmd);

    echo json_encode([
        'sucesso'  => true,
        'mensagem' => "Sincronização iniciada em background.",
        'log_file' => $logFile
    ]);

} catch (Exception $e) { echo json_encode(['erro' => $e->getMessage()]); }
