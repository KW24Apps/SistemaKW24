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

$campos      = ['nome', 'cnpj', 'link_bitrix', 'telefone', 'email', 'endereco'];
$obrigatorios = ['nome', 'cnpj', 'link_bitrix', 'telefone', 'email', 'endereco'];

foreach ($obrigatorios as $campo) {
    if (empty(trim($body[$campo] ?? ''))) {
        echo json_encode(['erro' => "Campo obrigatório: {$campo}"]);
        exit;
    }
}

try {
    $db = Database::getInstance();

    // Gera chave_acesso: slug do nome + 16 chars aleatórios
    $slug    = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $body['nome']));
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
        $chave = $slug . '-' . $sufixo;
        $existe = $db->fetchOne("SELECT id FROM clientes WHERE chave_acesso = :chave", ['chave' => $chave]);
        $tentativas++;
        if ($tentativas > 100) {
            echo json_encode(['erro' => 'Não foi possível gerar chave única. Tente novamente.']);
            exit;
        }
    } while ($existe);

    $orgId = !empty($body['org_id']) ? (int)$body['org_id'] : null;

    $db->execute("
        INSERT INTO clientes (nome, cnpj, chave_acesso, link_bitrix, telefone, email, endereco, id_bitrix, org_id)
        VALUES (:nome, :cnpj, :chave_acesso, :link_bitrix, :telefone, :email, :endereco, :id_bitrix, :org_id)
    ", [
        'nome'         => trim($body['nome']),
        'cnpj'         => trim($body['cnpj']),
        'chave_acesso' => $chave,
        'link_bitrix'  => trim($body['link_bitrix']),
        'telefone'     => trim($body['telefone']),
        'email'        => trim($body['email']),
        'endereco'     => trim($body['endereco']),
        'id_bitrix'    => !empty($body['id_bitrix']) ? (int)$body['id_bitrix'] : null,
        'org_id'       => $orgId,
    ]);

    $id = (int)$db->getLastInsertId('clientes_id_seq');
    echo json_encode(['sucesso' => true, 'id' => $id, 'chave_acesso' => $chave]);

} catch (Exception $e) {
    echo json_encode(['erro' => $e->getMessage()]);
}
