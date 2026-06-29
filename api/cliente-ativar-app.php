<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
header('Content-Type: application/json');
$auth = new AuthenticationService();
if (!$auth->validateSession()) { http_response_code(401); echo json_encode(['erro' => 'Não autenticado']); exit; }

$body        = json_decode(file_get_contents('php://input'), true);
$clienteId   = (int)($body['cliente_id']   ?? 0);
$aplicacaoId = (int)($body['aplicacao_id'] ?? 0);
$webhook     = trim($body['webhook_bitrix'] ?? '');
$descricao   = trim($body['descricao']      ?? '') ?: null;

if (!$clienteId || !$aplicacaoId) { echo json_encode(['erro' => 'Dados inválidos']); exit; }

try {
    $db = Database::getInstance();

    // Recupera chave_acesso do cliente como base
    $cliente = $db->fetchOne("SELECT chave_acesso FROM clientes WHERE id = :id", ['id' => $clienteId]);
    if (!$cliente) { echo json_encode(['erro' => 'Cliente não encontrado']); exit; }

    // Gera chave única = chave_acesso do cliente + 5 chars maiúsculos aleatórios
    $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $tentativas = 0;
    do {
        $sufixo = '';
        for ($i = 0; $i < 5; $i++) {
            $sufixo .= $chars[random_int(0, 35)];
        }
        $chave = $cliente['chave_acesso'] . $sufixo;
        $existe = $db->fetchOne("SELECT id FROM cliente_aplicacoes WHERE chave = :chave", ['chave' => $chave]);
        $tentativas++;
        if ($tentativas > 200) {
            echo json_encode(['erro' => 'Não foi possível gerar chave única. Tente novamente.']);
            exit;
        }
    } while ($existe);

    $db->execute(
        "INSERT INTO cliente_aplicacoes (cliente_id, aplicacao_id, ativo, webhook_bitrix, chave, descricao)
         VALUES (:c, :a, TRUE, :w, :chave, :desc)",
        [
            'c'     => $clienteId,
            'a'     => $aplicacaoId,
            'w'     => $webhook ?: null,
            'chave' => $chave,
            'desc'  => $descricao,
        ]
    );

    $caId = (int)$db->getLastInsertId('cliente_aplicacoes_id_seq');
    echo json_encode(['sucesso' => true, 'ca_id' => $caId, 'chave' => $chave]);

} catch (Exception $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}
