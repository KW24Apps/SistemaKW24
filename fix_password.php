<?php
/**
 * SCRIPT DE CORREÇÃO DEFINITIVA
 * Gera o hash correto e fornece o SQL para atualizar
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('CORRECT_PASSWORD', '159Qwaszx753!@*');
define('USERNAME', 'gabriel.acker');

// Configuração idêntica ao sistema
$config = [
    'security' => [
        'password_algorithm' => PASSWORD_ARGON2ID,
        'password_options' => [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 3
        ]
    ]
];

function debugLog($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    
    if ($data !== null) {
        $logMessage .= " | Data: " . (is_array($data) || is_object($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data);
    }
    
    echo $logMessage . "\n";
    file_put_contents(__DIR__ . '/correction.log', $logMessage . "\n", FILE_APPEND);
}

// Limpa log anterior
file_put_contents(__DIR__ . '/correction.log', '');

debugLog("=== SCRIPT DE CORREÇÃO DEFINITIVA ===");
debugLog("Password", CORRECT_PASSWORD);
debugLog("Username", USERNAME);

// Gera o hash correto usando Argon2ID (como o sistema)
$correctHashArgon = password_hash(
    CORRECT_PASSWORD, 
    $config['security']['password_algorithm'],
    $config['security']['password_options']
);

debugLog("Correct Argon2ID hash", $correctHashArgon);

// Testa se funciona
$testArgon = password_verify(CORRECT_PASSWORD, $correctHashArgon);
debugLog("Argon2ID verification", $testArgon ? 'SUCCESS ✅' : 'FAILED ❌');

// Gera também um BCRYPT como alternativa
$correctHashBcrypt = password_hash(CORRECT_PASSWORD, PASSWORD_BCRYPT);
debugLog("Alternative BCRYPT hash", $correctHashBcrypt);

// Testa se funciona
$testBcrypt = password_verify(CORRECT_PASSWORD, $correctHashBcrypt);
debugLog("BCRYPT verification", $testBcrypt ? 'SUCCESS ✅' : 'FAILED ❌');

// Gera MD5 para resetar para estado inicial se necessário
$md5Hash = md5(CORRECT_PASSWORD);
debugLog("MD5 hash (for reset)", $md5Hash);

debugLog("\n=== COMANDOS SQL PARA CORREÇÃO ===");

echo "\n" . str_repeat("=", 80) . "\n";
echo "🔧 COMANDOS SQL PARA CORREÇÃO\n";
echo str_repeat("=", 80) . "\n\n";

echo "1️⃣ OPÇÃO 1 - Usar Argon2ID (recomendado):\n";
echo "UPDATE Colaboradores SET senha = '$correctHashArgon' WHERE UserName = '" . USERNAME . "';\n\n";

echo "2️⃣ OPÇÃO 2 - Usar BCRYPT (alternativa):\n";
echo "UPDATE Colaboradores SET senha = '$correctHashBcrypt' WHERE UserName = '" . USERNAME . "';\n\n";

echo "3️⃣ OPÇÃO 3 - Reset para MD5 (para testar migração novamente):\n";
echo "UPDATE Colaboradores SET senha = '$md5Hash' WHERE UserName = '" . USERNAME . "';\n\n";

echo str_repeat("=", 80) . "\n";
echo "💡 INSTRUÇÕES:\n";
echo "1. Execute UM dos comandos SQL acima no seu banco de dados\n";
echo "2. Teste o login com a senha: " . CORRECT_PASSWORD . "\n";
echo "3. Se usar a opção 3 (MD5), o sistema fará migração automática\n";
echo str_repeat("=", 80) . "\n\n";

// Teste extra: simula o processo completo
debugLog("\n=== SIMULAÇÃO DO PROCESSO COMPLETO ===");

// Simula login com MD5
$md5Login = md5(CORRECT_PASSWORD) === $md5Hash;
debugLog("Login with MD5", $md5Login ? 'SUCCESS' : 'FAILED');

if ($md5Login) {
    // Simula migração
    $migratedHash = password_hash(
        CORRECT_PASSWORD, 
        $config['security']['password_algorithm'],
        $config['security']['password_options']
    );
    debugLog("Migrated hash", $migratedHash);
    
    // Testa hash migrado
    $migratedTest = password_verify(CORRECT_PASSWORD, $migratedHash);
    debugLog("Migrated hash test", $migratedTest ? 'SUCCESS ✅' : 'FAILED ❌');
}

debugLog("\n=== CORREÇÃO CONCLUÍDA ===");

echo "✅ Script de correção executado com sucesso!\n";
echo "📋 Log detalhado salvo em: correction.log\n";
echo "🚀 Execute um dos comandos SQL acima para corrigir o problema!\n";
?>
