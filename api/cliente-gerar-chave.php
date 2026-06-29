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
$clienteId = (int)($body['cliente_id'] ?? 0);
if (!$clienteId) {
    echo json_encode(['erro' => 'ID de cliente inválido']);
    exit;
}

try {
    $db      = Database::getInstance();
    $cliente = $db->fetchOne("SELECT id, nome, chave_acesso FROM clientes WHERE id = :id", ['id' => $clienteId]);
    if (!$cliente) {
        echo json_encode(['erro' => 'Cliente não encontrado']);
        exit;
    }

    if ($cliente['chave_acesso']) {
        echo json_encode(['sucesso' => true, 'chave_acesso' => $cliente['chave_acesso']]);
        exit;
    }

    $slug    = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $cliente['nome']));
    $slug    = trim($slug, '-');
    $slug    = preg_replace('/-+/', '-', $slug);
    $chars   = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $charLen = strlen($chars) - 1;

    $tentativas = 0;
    do {
        $sufixo = '';
        for ($i = 0; $i < 16; $i++) {
            $sufixo .= $chars[random_int(0, $charLen)];
        }
        $chave  = $slug . '-' . $sufixo;
        $existe = $db->fetchOne("SELECT id FROM clientes WHERE chave_acesso = :chave", ['chave' => $chave]);
        $tentativas++;
        if ($tentativas > 100) {
            echo json_encode(['erro' => 'Não foi possível gerar chave única. Tente novamente.']);
            exit;
        }
    } while ($existe);

    $db->execute("UPDATE clientes SET chave_acesso = :chave WHERE id = :id", ['chave' => $chave, 'id' => $clienteId]);
    echo json_encode(['sucesso' => true, 'chave_acesso' => $chave]);

} catch (Exception $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}
