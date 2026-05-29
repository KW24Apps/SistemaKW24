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
$id   = (int)($body['id'] ?? 0);

if (!$id) {
    echo json_encode(['erro' => 'ID inválido']);
    exit;
}

// Campos permitidos para atualização
$permitidos = ['nome', 'cnpj', 'chave_acesso', 'link_bitrix', 'telefone', 'email', 'endereco', 'id_bitrix'];

$sets   = [];
$params = ['id' => $id];

foreach ($permitidos as $campo) {
    if (array_key_exists($campo, $body)) {
        $sets[]        = "{$campo} = :{$campo}";
        $params[$campo] = $body[$campo] !== '' ? $body[$campo] : null;
    }
}

if (empty($sets)) {
    echo json_encode(['erro' => 'Nenhum campo para atualizar']);
    exit;
}

try {
    $db = Database::getInstance();
    $db->execute(
        "UPDATE clientes SET " . implode(', ', $sets) . " WHERE id = :id",
        $params
    );
    echo json_encode(['sucesso' => true]);
} catch (Exception $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}
