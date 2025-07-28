<?php
// Endpoint AJAX para busca inteligente de clientes
require_once __DIR__ . '/../dao/DAO.php';
header('Content-Type: application/json; charset=utf-8');

// Suporte tanto para GET quanto POST
$search = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $search = isset($_POST['term']) ? trim($_POST['term']) : '';
} else {
    $search = isset($_GET['q']) ? trim($_GET['q']) : '';
}

$clienteId = isset($_GET['id']) ? intval($_GET['id']) : 0;
$dao = new DAO();

// Se for busca por ID específico (para modal)
if ($clienteId > 0) {
    $db = $dao->getPdo();
    $sql = "SELECT id, nome, cnpj, link_bitrix, telefone, email, endereco FROM clientes WHERE id = :id";
    $stmt = $db->prepare($sql);
    $stmt->execute([':id' => $clienteId]);
    $cliente = $stmt->fetch();
    echo json_encode($cliente ?: []);
    exit;
}

// Se for busca por termo ou listar todos
if ($search === '') {
    // Retorna todos se não houver busca
    $clientes = $dao->getClientesCampos();
} else {
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
}

echo json_encode($clientes);
