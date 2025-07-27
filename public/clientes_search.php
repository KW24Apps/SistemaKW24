<?php
// Endpoint AJAX para busca inteligente de clientes
require_once __DIR__ . '/../dao/DAO.php';
header('Content-Type: application/json; charset=utf-8');

$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$dao = new DAO();

if ($search === '') {
    // Retorna todos se não houver busca
    $clientes = $dao->getClientesCampos();
    echo json_encode($clientes);
    exit;
}

// Monta consulta SQL dinâmica para busca em todos os campos relevantes

$db = $dao->getPdo();
$sql = "SELECT id, nome, cnpj, link_bitrix, telefone, email FROM clientes WHERE 
    id LIKE :q OR
    nome LIKE :q OR
    cnpj LIKE :q OR
    link_bitrix LIKE :q OR
    telefone LIKE :q OR
    email LIKE :q";
$stmt = $db->prepare($sql);
$stmt->execute([':q' => "%$search%"]);
$clientes = $stmt->fetchAll();
echo json_encode($clientes);
