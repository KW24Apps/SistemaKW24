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

    // slug is immutable — never updated, only set at row creation
    $db->execute(
        'UPDATE relatorios_bi SET nome_amigavel = :nome, visivel = :visivel WHERE id = :id',
        [':nome' => $nome, ':visivel' => $visivel ? 'true' : 'false', ':id' => $id]
    );

    $row = $db->fetchAll('SELECT slug FROM relatorios_bi WHERE id = :id', [':id' => $id]);
    echo json_encode(['success' => true, 'slug' => $row[0]['slug'] ?? '']);
    exit;
}

http_response_code(400);
echo json_encode(['erro' => 'action inválida']);
