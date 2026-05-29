<?php
/**
 * Busca funis/categorias de uma entidade no Bitrix24
 */
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
header('Content-Type: application/json');

$auth = new AuthenticationService();
if (!$auth->validateSession()) { http_response_code(401); echo json_encode(['erro'=>'Não autenticado']); exit; }

$clienteId   = (int)($_GET['cliente_id']   ?? 0);
$aplicacaoId = (int)($_GET['aplicacao_id'] ?? 0);
$entityId    = (int)($_GET['entity_id']    ?? 0);

if (!$clienteId || !$aplicacaoId || !$entityId) { echo json_encode(['erro'=>'Dados inválidos']); exit; }

try {
    $db  = Database::getInstance();
    $row = $db->fetchOne(
        "SELECT webhook_bitrix FROM cliente_aplicacoes WHERE cliente_id = :c AND aplicacao_id = :a",
        ['c' => $clienteId, 'a' => $aplicacaoId]
    );

    if (!$row || empty($row['webhook_bitrix'])) {
        echo json_encode(['erro' => 'Webhook não configurado']); exit;
    }

    $webhook = rtrim($row['webhook_bitrix'], '/') . '/';
    $url     = $webhook . 'crm.category.list?entityTypeId=' . $entityId;
    $resp    = @file_get_contents($url);

    if ($resp === false) {
        echo json_encode(['funis' => [], 'aviso' => 'Não foi possível consultar o Bitrix24']); exit;
    }

    $data  = json_decode($resp, true);
    $cats  = $data['result']['categories'] ?? $data['result'] ?? [];

    $funis = array_map(fn($c) => [
        'id'   => $c['id'],
        'nome' => $c['name'] ?? $c['title'] ?? 'Funil ' . $c['id']
    ], $cats);

    echo json_encode(['sucesso' => true, 'funis' => $funis]);

} catch (Exception $e) { echo json_encode(['erro' => $e->getMessage()]); }
