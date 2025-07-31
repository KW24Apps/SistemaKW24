<?php
/**
 * SCRIPT DE DEBUG - TESTE LOCAL DE AUTENTICAÇÃO
 * Simula todo o processo de login e migração sem mexer no banco
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Senha fixa para teste
define('TEST_PASSWORD', '159Qwaszx753!@*');
define('TEST_USERNAME', 'gabriel.acker');

// Configuração de segurança (mesmo do sistema)
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

/**
 * Função para log detalhado
 */
function debugLog($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    
    if ($data !== null) {
        $logMessage .= " | Data: " . (is_array($data) || is_object($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data);
    }
    
    echo $logMessage . "\n";
    file_put_contents(__DIR__ . '/debug_test.log', $logMessage . "\n", FILE_APPEND);
}

/**
 * Simula detecção de senha legacy (mesmo código do sistema)
 */
function isLegacyPassword($password) {
    $info = password_get_info($password);
    debugLog("Password info", $info);
    
    $isLegacy = $info['algo'] === null || $info['algo'] === 0;
    debugLog("Is legacy password", $isLegacy ? 'YES' : 'NO');
    
    return $isLegacy;
}

/**
 * Simula verificação de senha (mesmo código do sistema)
 */
function verifyPassword($inputPassword, $storedPassword) {
    debugLog("=== VERIFICAÇÃO DE SENHA ===");
    debugLog("Input password", $inputPassword);
    debugLog("Stored password", $storedPassword);
    
    // Se é hash legacy (MD5 ou texto), compara direto
    if (isLegacyPassword($storedPassword)) {
        debugLog("LEGACY PASSWORD DETECTED - Comparing directly");
        
        // Tenta MD5 primeiro
        $md5Check = md5($inputPassword) === $storedPassword;
        debugLog("MD5 check", $md5Check ? 'MATCH' : 'NO MATCH');
        debugLog("MD5 generated", md5($inputPassword));
        
        // Tenta texto puro
        $plainCheck = $inputPassword === $storedPassword;
        debugLog("Plain text check", $plainCheck ? 'MATCH' : 'NO MATCH');
        
        $result = $md5Check || $plainCheck;
        debugLog("Legacy verification result", $result ? 'SUCCESS' : 'FAILED');
        
        return $result;
    }
    
    // Se é hash moderno, usa password_verify
    debugLog("MODERN PASSWORD DETECTED - Using password_verify");
    $result = password_verify($inputPassword, $storedPassword);
    debugLog("Modern verification result", $result ? 'SUCCESS' : 'FAILED');
    
    return $result;
}

/**
 * Simula migração de senha (mesmo código do sistema)
 */
function migratePassword($userId, $plainPassword, $config) {
    debugLog("=== MIGRAÇÃO DE SENHA ===");
    debugLog("User ID", $userId);
    debugLog("Plain password to migrate", $plainPassword);
    debugLog("Algorithm", $config['security']['password_algorithm']);
    
    // Gera novo hash
    $newHash = password_hash(
        $plainPassword, 
        $config['security']['password_algorithm'],
        $config['security']['password_options']
    );
    
    debugLog("Generated new hash", $newHash);
    
    // Verifica se o hash gerado funciona
    $verification = password_verify($plainPassword, $newHash);
    debugLog("Hash verification test", $verification ? 'SUCCESS' : 'FAILED');
    
    return $newHash;
}

/**
 * Simula updatePassword do DAO (mesmo código)
 */
function simulateUpdatePassword($newPassword, $config) {
    debugLog("=== SIMULAÇÃO UPDATE PASSWORD ===");
    debugLog("Input password", $newPassword);
    debugLog("Input length", strlen($newPassword));
    
    // CORREÇÃO: Se já é um hash válido, usa direto; senão, faz hash
    if (strlen($newPassword) > 60 && (str_contains($newPassword, '$2y$') || str_contains($newPassword, '$argon2id$'))) {
        debugLog("DETECTED AS HASH - Using directly");
        $hashedPassword = $newPassword;
    } else {
        debugLog("DETECTED AS PLAIN TEXT - Generating hash");
        $hashedPassword = password_hash($newPassword, $config['security']['password_algorithm']);
    }
    
    debugLog("Final hash to store", $hashedPassword);
    
    // Testa se funciona
    $testVerify = password_verify(TEST_PASSWORD, $hashedPassword);
    debugLog("Final hash test with original password", $testVerify ? 'SUCCESS' : 'FAILED');
    
    return $hashedPassword;
}

// Limpa log anterior
file_put_contents(__DIR__ . '/debug_test.log', '');

debugLog("=== INICIANDO TESTE DEBUG ===");
debugLog("Test password", TEST_PASSWORD);
debugLog("Test username", TEST_USERNAME);

// CENÁRIO 1: Senha MD5 no banco (simulando situação atual)
debugLog("\n=== CENÁRIO 1: SENHA MD5 NO BANCO ===");
$md5Password = md5(TEST_PASSWORD);
debugLog("MD5 password in database", $md5Password);

// Simula login com senha MD5
$loginSuccess = verifyPassword(TEST_PASSWORD, $md5Password);
debugLog("Login with MD5", $loginSuccess ? 'SUCCESS' : 'FAILED');

if ($loginSuccess) {
    // Simula migração
    $newHash = migratePassword(1, TEST_PASSWORD, $config);
    
    // Simula update no banco
    $finalHash = simulateUpdatePassword($newHash, $config);
    
    // Testa se consegue logar com o hash final
    debugLog("\n=== TESTE FINAL COM HASH MIGRADO ===");
    $finalTest = verifyPassword(TEST_PASSWORD, $finalHash);
    debugLog("Final login test", $finalTest ? 'SUCCESS' : 'FAILED');
}

// CENÁRIO 2: Senha em texto puro no banco
debugLog("\n=== CENÁRIO 2: SENHA TEXTO PURO NO BANCO ===");
$plainPassword = TEST_PASSWORD;
debugLog("Plain password in database", $plainPassword);

$loginSuccess2 = verifyPassword(TEST_PASSWORD, $plainPassword);
debugLog("Login with plain text", $loginSuccess2 ? 'SUCCESS' : 'FAILED');

if ($loginSuccess2) {
    // Simula migração
    $newHash2 = migratePassword(1, TEST_PASSWORD, $config);
    
    // Simula update no banco
    $finalHash2 = simulateUpdatePassword($newHash2, $config);
    
    // Testa se consegue logar com o hash final
    debugLog("\n=== TESTE FINAL COM HASH MIGRADO (CENÁRIO 2) ===");
    $finalTest2 = verifyPassword(TEST_PASSWORD, $finalHash2);
    debugLog("Final login test (scenario 2)", $finalTest2 ? 'SUCCESS' : 'FAILED');
}

// CENÁRIO 3: Testando diretamente password_hash e password_verify
debugLog("\n=== CENÁRIO 3: TESTE DIRETO PASSWORD_HASH ===");
$directHash = password_hash(TEST_PASSWORD, PASSWORD_ARGON2ID);
debugLog("Direct hash generated", $directHash);

$directVerify = password_verify(TEST_PASSWORD, $directHash);
debugLog("Direct verification", $directVerify ? 'SUCCESS' : 'FAILED');

debugLog("\n=== TESTE CONCLUÍDO ===");
debugLog("Verifique o arquivo debug_test.log para detalhes completos");

echo "\n=== RESUMO ===\n";
echo "✅ Script executado com sucesso!\n";
echo "📋 Log salvo em: debug_test.log\n";
echo "🔍 Analise os logs para identificar onde está o problema\n";
?>
