<?php
session_start();
require_once __DIR__ . '/../services/AuthenticationService.php';
require_once __DIR__ . '/../helpers/Database.php';
header('Content-Type: application/json');
$auth = new AuthenticationService();
if (!$auth->validateSession()) { http_response_code(401); echo json_encode(['erro'=>'Não autenticado']); exit; }

$body = json_decode(file_get_contents('php://input'), true);
$slug = trim($body['slug'] ?? '');
$nome = trim($body['nome'] ?? '');
$desc = trim($body['descricao'] ?? '');

if (!$slug || !$nome) { echo json_encode(['erro'=>'Slug e Nome são obrigatórios']); exit; }

try {
    $db = Database::getInstance();
    $existe = $db->fetchOne("SELECT id FROM aplicacoes WHERE slug = :slug", ['slug' => $slug]);
    if ($existe) { echo json_encode(['erro'=>'Slug já existe']); exit; }

    $db->execute("INSERT INTO aplicacoes (slug, nome, descricao) VALUES (:slug, :nome, :desc)",
        ['slug' => $slug, 'nome' => $nome, 'desc' => $desc ?: null]);
    $id = (int)$db->getLastInsertId('aplicacoes_id_seq');
    echo json_encode(['sucesso' => true, 'id' => $id]);
} catch (Exception $e) { echo json_encode(['erro' => $e->getMessage()]); }
