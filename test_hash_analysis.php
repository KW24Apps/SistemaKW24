<?php
/**
 * TESTE ESPECÍFICO - ANALISA O HASH PROBLEMÁTICO
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('TEST_PASSWORD', '159Qwaszx753!@*');
define('PROBLEMATIC_HASH', '$2y$10$DCNHZRIb1KdW6G3Y25XBP.O3II2tj7OFzAEmm5yXAHwyn12JUTf7i');

function debugLog($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    
    if ($data !== null) {
        $logMessage .= " | Data: " . (is_array($data) || is_object($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data);
    }
    
    echo $logMessage . "\n";
    file_put_contents(__DIR__ . '/hash_analysis.log', $logMessage . "\n", FILE_APPEND);
}

// Limpa log anterior
file_put_contents(__DIR__ . '/hash_analysis.log', '');

debugLog("=== ANÁLISE DO HASH PROBLEMÁTICO ===");
debugLog("Password to test", TEST_PASSWORD);
debugLog("Problematic hash", PROBLEMATIC_HASH);

// Analisa o hash problemático
$hashInfo = password_get_info(PROBLEMATIC_HASH);
debugLog("Hash info", $hashInfo);

// Testa verificação
$verification = password_verify(TEST_PASSWORD, PROBLEMATIC_HASH);
debugLog("Verification result", $verification ? 'SUCCESS' : 'FAILED');

// Vamos criar vários hashes BCRYPT para comparar
debugLog("\n=== TESTANDO BCRYPT (que parece ser o tipo do hash problemático) ===");

for ($i = 1; $i <= 5; $i++) {
    $newBcryptHash = password_hash(TEST_PASSWORD, PASSWORD_BCRYPT);
    debugLog("BCRYPT hash attempt $i", $newBcryptHash);
    
    $testVerify = password_verify(TEST_PASSWORD, $newBcryptHash);
    debugLog("BCRYPT verification $i", $testVerify ? 'SUCCESS' : 'FAILED');
}

// Agora vamos testar se algo está alterando a senha antes do hash
debugLog("\n=== TESTE DE ALTERAÇÃO DE SENHA ===");

$testPasswords = [
    TEST_PASSWORD,
    trim(TEST_PASSWORD),
    htmlspecialchars(TEST_PASSWORD),
    htmlentities(TEST_PASSWORD),
    stripslashes(TEST_PASSWORD),
    addslashes(TEST_PASSWORD),
    urlencode(TEST_PASSWORD),
    base64_encode(TEST_PASSWORD),
    strtolower(TEST_PASSWORD),
    strtoupper(TEST_PASSWORD)
];

debugLog("Testing various password transformations:");
foreach ($testPasswords as $index => $testPass) {
    $transformNames = [
        'original',
        'trimmed', 
        'htmlspecialchars',
        'htmlentities',
        'stripslashes',
        'addslashes',
        'urlencode',
        'base64_encode',
        'lowercase',
        'uppercase'
    ];
    
    debugLog("Transform: {$transformNames[$index]}", $testPass);
    
    $verify = password_verify($testPass, PROBLEMATIC_HASH);
    debugLog("Verify with {$transformNames[$index]}", $verify ? 'SUCCESS' : 'FAILED');
    
    if ($verify) {
        debugLog("🎯 FOUND MATCHING TRANSFORMATION!", $transformNames[$index]);
    }
}

// Vamos tentar regenerar o hash exato usando BCRYPT
debugLog("\n=== TENTANDO REGENERAR O HASH EXATO ===");

// O hash problemático é BCRYPT com cost 10
// Vamos tentar várias vezes para ver se conseguimos o mesmo salt
$attempts = 10;
for ($i = 1; $i <= $attempts; $i++) {
    $bcryptHash = password_hash(TEST_PASSWORD, PASSWORD_BCRYPT, ['cost' => 10]);
    debugLog("Regeneration attempt $i", $bcryptHash);
    
    if ($bcryptHash === PROBLEMATIC_HASH) {
        debugLog("🎯 MATCHED EXACT HASH!", "Attempt $i");
        break;
    }
}

debugLog("\n=== ANALYSIS COMPLETE ===");

echo "\n=== RESUMO DA ANÁLISE ===\n";
echo "📋 Hash problemático: " . PROBLEMATIC_HASH . "\n";
echo "🔍 Log salvo em: hash_analysis.log\n";
echo "💡 Se encontrou algum SUCCESS acima, descobrimos o problema!\n";
?>
