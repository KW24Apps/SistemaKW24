<?php
session_start();
require_once __DIR__ . '/../includes/helpers.php';
requireAuthentication();

// Configuração de cabeçalhos JSON
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../dao/DAO.php';
    $dao = new DAO();
    
    // Verifica se é busca por ID específico
    $contatoId = $_GET['id'] ?? null;
    if ($contatoId) {
        // Busca contato específico por ID
        $pdo = $dao->getPdo();
        $sql = "SELECT id, nome, cargo, email, telefone, id_bitrix 
                FROM contatos 
                WHERE id = ?";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$contatoId]);
        $contato = $stmt->fetch();
        
        if ($contato) {
            $contatoProcessado = [
                'id' => (int)$contato['id'],
                'nome' => $contato['nome'] ?? '',
                'cargo' => $contato['cargo'] ?? '',
                'email' => $contato['email'] ?? '',
                'telefone' => $contato['telefone'] ?? '',
                'telefone_raw' => $contato['telefone'] ?? '',
                'id_bitrix' => $contato['id_bitrix'] ? (int)$contato['id_bitrix'] : null
            ];
            echo json_encode($contatoProcessado);
        } else {
            http_response_code(404);
            echo json_encode(['error' => true, 'message' => 'Contato não encontrado']);
        }
        exit;
    }
    
    // Determina se é busca (POST) ou carregamento inicial (GET)
    $searchTerm = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $searchTerm = $_POST['term'] ?? '';
    }
    
    if (empty($searchTerm)) {
        // Carregamento inicial - todos os contatos
        $contatos = $dao->getContatosCampos();
    } else {
        // Busca com filtro
        $pdo = $dao->getPdo();
        $searchTerm = '%' . $searchTerm . '%';
        
        $sql = "SELECT id, nome, cargo, email, telefone, id_bitrix 
                FROM contatos 
                WHERE nome LIKE ? 
                   OR cargo LIKE ? 
                   OR email LIKE ? 
                   OR telefone LIKE ?
                ORDER BY id ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        $contatos = $stmt->fetchAll();
    }
    
    // Função para formatar telefone
    function formatTelefone($telefone) {
        if (empty($telefone)) return '';
        
        $telefone = preg_replace('/[^0-9]/', '', $telefone);
        if (strlen($telefone) === 13) {
            return sprintf('(%s) %s-%s', substr($telefone,2,2), substr($telefone,4,5), substr($telefone,9,4));
        } elseif (strlen($telefone) === 12) {
            return sprintf('(%s) %s-%s', substr($telefone,2,2), substr($telefone,4,4), substr($telefone,8,4));
        } elseif (strlen($telefone) === 11) {
            return sprintf('(%s) %s-%s', substr($telefone,0,2), substr($telefone,2,5), substr($telefone,7,4));
        } elseif (strlen($telefone) === 10) {
            return sprintf('(%s) %s-%s', substr($telefone,0,2), substr($telefone,2,4), substr($telefone,6,4));
        }
        return $telefone;
    }
    
    // Processa os dados dos contatos
    $contatosProcessados = [];
    foreach ($contatos as $contato) {
        $contatosProcessados[] = [
            'id' => (int)$contato['id'],
            'nome' => $contato['nome'] ?? '',
            'cargo' => $contato['cargo'] ?? '',
            'email' => $contato['email'] ?? '',
            'telefone' => formatTelefone($contato['telefone'] ?? ''),
            'telefone_raw' => $contato['telefone'] ?? '',
            'id_bitrix' => $contato['id_bitrix'] ? (int)$contato['id_bitrix'] : null
        ];
    }
    
    echo json_encode($contatosProcessados);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Erro ao buscar contatos: ' . $e->getMessage()
    ]);
}
?>
