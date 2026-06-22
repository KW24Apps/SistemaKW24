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

$action = $_GET['action'] ?? '';
$db     = Database::getInstance();

if ($action === 'list') {
    $rows = $db->fetchAll(
        'SELECT id, slug, nome_amigavel, visivel FROM relatorios_bi ORDER BY ordem ASC'
    );
    echo json_encode(['success' => true, 'data' => $rows]);
    exit;
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $body = json_decode(file_get_contents('php://input'), true) ?? [];
    $id   = (int)($body['id'] ?? 0);

    if (!$id) {
        http_response_code(400);
        echo json_encode(['erro' => 'id inválido']);
        exit;
    }

    $nome    = trim($body['nome_amigavel'] ?? '');
    $visivel = isset($body['visivel']) ? (bool)$body['visivel'] : true;

    if ($nome === '') {
        http_response_code(400);
        echo json_encode(['erro' => 'nome_amigavel não pode ser vazio']);
        exit;
    }

    $slug = generateSlug($nome);

    $db->execute(
        'UPDATE relatorios_bi SET nome_amigavel = :nome, slug = :slug, visivel = :visivel WHERE id = :id',
        [':nome' => $nome, ':slug' => $slug, ':visivel' => $visivel ? 'true' : 'false', ':id' => $id]
    );

    echo json_encode(['success' => true, 'slug' => $slug]);
    exit;
}

http_response_code(400);
echo json_encode(['erro' => 'action inválida']);

function generateSlug(string $nome): string {
    $nome = mb_strtolower(trim($nome), 'UTF-8');
    $from = ['á','à','ã','â','ä','é','è','ê','ë','í','ì','î','ï','ó','ò','ô','õ','ö','ú','ù','û','ü','ç','ñ'];
    $to   = ['a','a','a','a','a','e','e','e','e','i','i','i','i','o','o','o','o','o','u','u','u','u','c','n'];
    $nome = str_replace($from, $to, $nome);
    $nome = preg_replace('/[^a-z0-9]+/', '-', $nome);
    return trim($nome, '-');
}
