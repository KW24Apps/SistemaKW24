<?php
/**
 * TESTE RÁPIDO DE AUTENTICAÇÃO
 * Para diagnóstico - será removido após teste
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "=== TESTE DE DIAGNÓSTICO KW24 ===\n\n";

// 1. Teste de arquivos
echo "1. Verificando arquivos essenciais:\n";
$files = [
    'config/config.php',
    'helpers/Database.php', 
    'dao/ColaboradorDAO.php',
    'services/AuthenticationService.php'
];

foreach($files as $file) {
    if(file_exists($file)) {
        echo "✅ $file - OK\n";
    } else {
        echo "❌ $file - FALTANDO\n";
    }
}

echo "\n2. Teste de conexão com banco:\n";
try {
    require_once 'helpers/Database.php';
    $db = Database::getInstance();
    $conn = $db->connect();
    if($conn) {
        echo "✅ Conexão com banco - OK\n";
        
        // Teste simples de query
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM Colaboradores");
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "✅ Total de colaboradores: " . $result['total'] . "\n";
        
    } else {
        echo "❌ Falha na conexão com banco\n";
    }
} catch(Exception $e) {
    echo "❌ Erro de banco: " . $e->getMessage() . "\n";
}

echo "\n3. Teste do AuthenticationService:\n";
try {
    require_once 'services/AuthenticationService.php';
    $authService = new AuthenticationService();
    echo "✅ AuthenticationService carregado - OK\n";
    
    // Teste com usuário conhecida
    echo "\n4. Teste de autenticação com gabriel.acker:\n";
    $result = $authService->authenticate('gabriel.acker', '159Qwaszx753!@*');
    
    if($result['success']) {
        echo "✅ Autenticação - SUCESSO\n";
        echo "   Usuário: " . $result['user']['Nome'] . "\n";
        echo "   Perfil: " . $result['user']['perfil'] . "\n";
    } else {
        echo "❌ Autenticação falhou: " . $result['message'] . "\n";
    }
    
} catch(Exception $e) {
    echo "❌ Erro no AuthenticationService: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DO TESTE ===\n";
?>
