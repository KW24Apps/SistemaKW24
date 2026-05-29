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

$body = json_decode(file_get_contents('php://input'), true);

$campos = ['nome', 'cnpj', 'chave_acesso', 'link_bitrix', 'telefone', 'email', 'endereco', 'id_bitrix'];
$obrigatorios = ['nome', 'cnpj', 'chave_acesso', 'link_bitrix', 'telefone', 'email', 'endereco'];

foreach ($obrigatorios as $campo) {
    if (empty(trim($body[$campo] ?? ''))) {
        echo json_encode(['erro' => "Campo obrigatório: {$campo}"]);
        exit;
    }
}

try {
    $db = Database::getInstance();

    // Verifica chave de acesso única
    $existe = $db->fetchOne("SELECT id FROM clientes WHERE chave_acesso = :chave", ['chave' => $body['chave_acesso']]);
    if ($existe) {
        echo json_encode(['erro' => 'Chave de acesso já existe para outro cliente.']);
        exit;
    }

    $db->execute("
        INSERT INTO clientes (nome, cnpj, chave_acesso, link_bitrix, telefone, email, endereco, id_bitrix)
        VALUES (:nome, :cnpj, :chave_acesso, :link_bitrix, :telefone, :email, :endereco, :id_bitrix)
    ", [
        'nome'         => trim($body['nome']),
        'cnpj'         => trim($body['cnpj']),
        'chave_acesso' => trim($body['chave_acesso']),
        'link_bitrix'  => trim($body['link_bitrix']),
        'telefone'     => trim($body['telefone']),
        'email'        => trim($body['email']),
        'endereco'     => trim($body['endereco']),
        'id_bitrix'    => !empty($body['id_bitrix']) ? (int)$body['id_bitrix'] : null,
    ]);

    $id = (int)$db->getLastInsertId('clientes_id_seq');
    echo json_encode(['sucesso' => true, 'id' => $id]);

} catch (Exception $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}
