<?php
session_start();
require_once __DIR__ . '/../includes/helpers.php';
requireAuthentication();

// Configuração de cabeçalhos JSON
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => true, 'message' => 'Método não permitido']);
    exit;
}

try {
    // Pega o ID da URL
    $contatoId = $_GET['id'] ?? null;
    
    if (!$contatoId || !is_numeric($contatoId)) {
        echo json_encode(['error' => true, 'message' => 'ID do contato inválido']);
        exit;
    }
    
    // Validação dos dados recebidos
    $nome = trim($_POST['nome'] ?? '');
    $cargo = trim($_POST['cargo'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    
    // Validações básicas
    if (empty($nome)) {
        echo json_encode(['error' => true, 'message' => 'Nome é obrigatório']);
        exit;
    }
    
    if (strlen($nome) > 100) {
        echo json_encode(['error' => true, 'message' => 'Nome deve ter no máximo 100 caracteres']);
        exit;
    }
    
    if (!empty($cargo) && strlen($cargo) > 100) {
        echo json_encode(['error' => true, 'message' => 'Cargo deve ter no máximo 100 caracteres']);
        exit;
    }
    
    if (!empty($email)) {
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            echo json_encode(['error' => true, 'message' => 'Email inválido']);
            exit;
        }
        if (strlen($email) > 100) {
            echo json_encode(['error' => true, 'message' => 'Email deve ter no máximo 100 caracteres']);
            exit;
        }
    }
    
    if (!empty($telefone) && strlen($telefone) > 20) {
        echo json_encode(['error' => true, 'message' => 'Telefone deve ter no máximo 20 caracteres']);
        exit;
    }
    
    // Conecta ao banco
    require_once __DIR__ . '/../dao/DAO.php';
    $dao = new DAO();
    $pdo = $dao->getPdo();
    
    // Verifica se o contato existe
    $stmt = $pdo->prepare("SELECT id FROM contatos WHERE id = ?");
    $stmt->execute([$contatoId]);
    if (!$stmt->fetch()) {
        echo json_encode(['error' => true, 'message' => 'Contato não encontrado']);
        exit;
    }
    
    // Verifica se já existe outro contato com mesmo email
    if (!empty($email)) {
        $stmt = $pdo->prepare("SELECT id FROM contatos WHERE email = ? AND id != ?");
        $stmt->execute([$email, $contatoId]);
        if ($stmt->fetch()) {
            echo json_encode(['error' => true, 'message' => 'Já existe outro contato com este email']);
            exit;
        }
    }
    
    // Atualiza o contato
    $sql = "UPDATE contatos SET nome = ?, cargo = ?, email = ?, telefone = ? WHERE id = ?";
    $stmt = $pdo->prepare($sql);
    
    $success = $stmt->execute([
        $nome,
        !empty($cargo) ? $cargo : null,
        !empty($email) ? $email : null,
        !empty($telefone) ? $telefone : null,
        $contatoId
    ]);
    
    if ($success) {
        echo json_encode([
            'success' => true,
            'message' => 'Contato atualizado com sucesso!'
        ]);
    } else {
        echo json_encode(['error' => true, 'message' => 'Erro ao atualizar contato']);
    }
    
} catch (Exception $e) {
    error_log("Erro ao atualizar contato: " . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'error' => true, 
        'message' => 'Erro interno do servidor'
    ]);
}
?>
