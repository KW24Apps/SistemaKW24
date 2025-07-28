<?php
// Endpoint para criar novo cliente
session_start();
require_once __DIR__ . '/../includes/helpers.php';
requireAuthentication();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

// Recebe dados JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos']);
    exit;
}

// Validação dos campos obrigatórios
if (empty($data['nome'])) {
    echo json_encode(['success' => false, 'message' => 'Nome da empresa é obrigatório']);
    exit;
}

try {
    require_once __DIR__ . '/../dao/DAO.php';
    $dao = new DAO();
    $db = $dao->getPdo();
    
    // Prepare SQL para inserir novo cliente
    $sql = "INSERT INTO clientes (nome, cnpj, link_bitrix, email, telefone, endereco, created_at) 
            VALUES (:nome, :cnpj, :link_bitrix, :email, :telefone, :endereco, NOW())";
    
    $stmt = $db->prepare($sql);
    
    // Bind dos parâmetros
    $stmt->bindParam(':nome', $data['nome']);
    $stmt->bindParam(':cnpj', $data['cnpj']);
    $stmt->bindParam(':link_bitrix', $data['link_bitrix']);
    $stmt->bindParam(':email', $data['email']);
    $stmt->bindParam(':telefone', $data['telefone']);
    $stmt->bindParam(':endereco', $data['endereco']);
    
    // Executa a inserção
    if ($stmt->execute()) {
        $clienteId = $db->lastInsertId();
        
        echo json_encode([
            'success' => true, 
            'message' => 'Cliente criado com sucesso',
            'cliente_id' => $clienteId
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao inserir cliente no banco de dados']);
    }
    
} catch (PDOException $e) {
    error_log('Erro ao criar cliente: ' . $e->getMessage());
    
    // Verifica se é erro de duplicata (CNPJ único, por exemplo)
    if ($e->getCode() === '23000') {
        echo json_encode(['success' => false, 'message' => 'Cliente já existe (CNPJ duplicado)']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
    }
} catch (Exception $e) {
    error_log('Erro geral ao criar cliente: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno do servidor']);
}
