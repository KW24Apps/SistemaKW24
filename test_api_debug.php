<?php
/**
 * TESTE RÁPIDO - PASSWORD RECOVERY
 * Script para testar a API diretamente
 */

// Configurar headers
header('Content-Type: application/json; charset=utf-8');

// Incluir dependências
require_once __DIR__ . '/controllers/PasswordRecoveryController.php';

try {
    echo "🔧 Testando Password Recovery API\n\n";
    
    // Teste 1: Instanciar controller
    echo "1. Instanciando controller...\n";
    $controller = new PasswordRecoveryController();
    echo "   ✅ Controller criado\n\n";
    
    // Teste 2: Testar método initiate diretamente
    echo "2. Testando método initiate...\n";
    
    // Simular POST data
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['CONTENT_TYPE'] = 'application/json';
    
    // Capturar output
    ob_start();
    
    // Simular dados de entrada
    $testData = json_encode(['identifier' => 'gabriel.acker@kw24.com.br']);
    
    // Simular input stream
    $temp = fopen('php://temp', 'r+');
    fwrite($temp, $testData);
    rewind($temp);
    
    // Redirect input
    $originalInput = 'php://input';
    
    // Execute
    try {
        $controller->initiate();
    } catch (Exception $e) {
        echo "Erro no initiate: " . $e->getMessage() . "\n";
    }
    
    $output = ob_get_clean();
    
    echo "   Resultado: " . $output . "\n\n";
    
    echo "3. Teste concluído\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Stack: " . $e->getTraceAsString() . "\n";
}
?>
