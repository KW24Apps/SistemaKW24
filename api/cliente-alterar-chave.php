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
$user = $auth->getCurrentUser();
if (($user['perfil'] ?? '') !== 'admin_interno') {
    http_response_code(403);
    echo json_encode(['erro' => 'Acesso restrito a administradores']);
    exit;
}

$body      = json_decode(file_get_contents('php://input'), true) ?? [];
$clienteId = (int)($body['cliente_id']       ?? 0);
$novaChave = trim($body['nova_chave_acesso'] ?? '');

if (!$clienteId || $novaChave === '') {
    echo json_encode(['erro' => 'Dados inválidos']);
    exit;
}

try {
    $db = Database::getInstance();

    // Garante que o cliente existe
    $cliente = $db->fetchOne("SELECT id FROM clientes WHERE id = :id", ['id' => $clienteId]);
    if (!$cliente) {
        echo json_encode(['erro' => 'Cliente não encontrado']);
        exit;
    }

    // Verifica unicidade da nova chave_acesso
    $existe = $db->fetchOne(
        "SELECT id FROM clientes WHERE chave_acesso = :chave AND id != :id",
        ['chave' => $novaChave, 'id' => $clienteId]
    );
    if ($existe) {
        echo json_encode(['erro' => 'Esta chave já está em uso por outro cliente']);
        exit;
    }

    // Atualiza chave_acesso do cliente
    $db->execute(
        "UPDATE clientes SET chave_acesso = :chave WHERE id = :id",
        ['chave' => $novaChave, 'id' => $clienteId]
    );

    // Regenera todas ca.chave com a nova lógica determinística
    $apps = $db->fetchAll(
        "SELECT ca.id, ca.descricao, a.nome AS app_nome
         FROM cliente_aplicacoes ca
         JOIN aplicacoes a ON ca.aplicacao_id = a.id
         WHERE ca.cliente_id = :id
         ORDER BY a.nome, ca.descricao",
        ['id' => $clienteId]
    );

    $resultado = [];
    foreach ($apps as $ca) {
        $sufixo      = strtoupper(substr(md5($ca['descricao'] ?? ''), 0, 5));
        $novaChaveApp = $novaChave . $sufixo;
        $db->execute(
            "UPDATE cliente_aplicacoes SET chave = :chave WHERE id = :id",
            ['chave' => $novaChaveApp, 'id' => $ca['id']]
        );
        $resultado[] = [
            'app_nome'   => $ca['app_nome'],
            'descricao'  => $ca['descricao'],
            'nova_chave' => $novaChaveApp,
        ];
    }

    echo json_encode(['sucesso' => true, 'chaves' => $resultado]);

} catch (Exception $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}
