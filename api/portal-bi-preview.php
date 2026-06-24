<?php
/**
 * Portal BI — Preview para usuários internos autenticados.
 * Cria sessão portal_bi com os filtros do portal e redireciona para o relatório,
 * sem exigir senha — reusa o auth-check.php existente no nginx.
 */
session_start();
define('SYSTEM_ACCESS', true);
require_once __DIR__ . '/../helpers/Database.php';

if (empty($_SESSION['user_authenticated'])) {
    http_response_code(401);
    echo 'Não autenticado.';
    exit;
}

$slug = trim($_GET['slug'] ?? '');
if (!$slug || !preg_match('/^[a-z0-9\-]+$/', $slug)) {
    http_response_code(400);
    echo 'Parâmetro inválido.';
    exit;
}

$pdo  = Database::getInstance()->getConnection();
$stmt = $pdo->prepare(
    'SELECT * FROM portais_bi WHERE slug = :slug AND ativo = true LIMIT 1'
);
$stmt->execute(['slug' => $slug]);
$portal = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$portal) {
    http_response_code(404);
    echo 'Portal não encontrado ou inativo.';
    exit;
}

$_SESSION['portal_bi'] = [
    'portal_id'      => (int)$portal['id'],
    'relatorio_slug' => $portal['relatorio_slug'],
    'filter_type'    => $portal['filter_type'],
    'filter_values'  => json_decode($portal['filter_values'], true) ?? [],
    'filter_labels'  => json_decode($portal['filter_labels'], true) ?? [],
    'nome'           => $portal['nome'] ?? '',
    'expires'        => 0,
];

header('Location: /relatorios-bi/' . $portal['relatorio_slug'] . '/');
exit;
