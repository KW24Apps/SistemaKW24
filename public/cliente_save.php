<?php
// Endpoint para salvar dados do cliente
require_once __DIR__ . '/../dao/DAO.php';
header('Content-Type: application/json; charset=utf-8');

// Só aceita POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Obtém dados do POST
$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

$id = intval($input['id']);
$nome = $input['nome'] ?? '';
$cnpj = $input['cnpj'] ?? '';
$link_bitrix = $input['link_bitrix'] ?? '';
$email = $input['email'] ?? '';
$telefone = $input['telefone'] ?? '';
$endereco = $input['endereco'] ?? '';

try {
    $dao = new DAO();
    $db = $dao->getPdo();
    
    $sql = "UPDATE clientes SET 
                nome = :nome, 
                cnpj = :cnpj, 
                link_bitrix = :link_bitrix, 
                email = :email, 
                telefone = :telefone, 
                endereco = :endereco 
            WHERE id = :id";
    
    $stmt = $db->prepare($sql);
    $result = $stmt->execute([
        ':id' => $id,
        ':nome' => $nome,
        ':cnpj' => $cnpj,
        ':link_bitrix' => $link_bitrix,
        ':email' => $email,
        ':telefone' => $telefone,
        ':endereco' => $endereco
    ]);
    
    if ($result && $stmt->rowCount() > 0) {
        echo json_encode(['success' => true, 'message' => 'Cliente atualizado com sucesso']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Nenhuma alteração foi feita']);
    }
    
} catch (Exception $e) {
    error_log('Erro ao salvar cliente: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
