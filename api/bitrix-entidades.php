<?php
/**
 * Busca entidades disponíveis no Bitrix24 do cliente
 * Retorna entidades padrão + SPAs customizados via crm.type.list
 */
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
header('Content-Type: application/json');

$auth = new AuthenticationService();
if (!$auth->validateSession()) { http_response_code(401); echo json_encode(['erro'=>'Não autenticado']); exit; }

$clienteId   = (int)($_GET['cliente_id']   ?? 0);
$aplicacaoId = (int)($_GET['aplicacao_id'] ?? 0);

if (!$clienteId || !$aplicacaoId) { echo json_encode(['erro'=>'Dados inválidos']); exit; }

try {
    $db      = Database::getInstance();
    $row     = $db->fetchOne(
        "SELECT webhook_bitrix FROM cliente_aplicacoes WHERE cliente_id = :c AND aplicacao_id = :a",
        ['c' => $clienteId, 'a' => $aplicacaoId]
    );

    if (!$row || empty($row['webhook_bitrix'])) {
        echo json_encode(['erro' => 'Webhook não configurado para esta aplicação']); exit;
    }

    $webhook = rtrim($row['webhook_bitrix'], '/') . '/';

    // Entidades padrão do Bitrix24
    $entidades = [
        ['id' => 2,  'title' => 'Negócios (Deal)',    'type' => 'crm'],
        ['id' => 4,  'title' => 'Empresas (Company)', 'type' => 'crm'],
        ['id' => 3,  'title' => 'Contatos (Contact)', 'type' => 'crm'],
        ['id' => 31, 'title' => 'Faturas (Invoice)',   'type' => 'crm'],
        ['id' => 7,  'title' => 'Orçamentos (Quote)',  'type' => 'crm'],
    ];

    // Busca SPAs customizados via crm.type.list
    $resp = @file_get_contents($webhook . 'crm.type.list');
    if ($resp !== false) {
        $data = json_decode($resp, true);
        $types = $data['result']['types'] ?? [];
        foreach ($types as $t) {
            $entidades[] = [
                'id'    => $t['entityTypeId'] ?? $t['id'],
                'title' => $t['title'] . ' (SPA)',
                'type'  => 'crm'
            ];
        }
    }

    echo json_encode(['sucesso' => true, 'entidades' => $entidades]);

} catch (Exception $e) { echo json_encode(['erro' => $e->getMessage()]); }
