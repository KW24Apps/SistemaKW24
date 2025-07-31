<?php
/**
 * DESCOBRINDO A SENHA     // Possíveis transformações durante o processo
    trim(EXPECTED_PASSWORD),
    htmlspecialchars(EXPECTED_PASSWORD),
    htmlentities(EXPECTED_PASSWORD),
    addslashes(EXPECTED_PASSWORD),
    stripslashes(EXPECTED_PASSWORD),
    
    // Possíveis alterações de case
    strtolower(EXPECTED_PASSWORD),
    strtoupper(EXPECTED_PASSWORD),PROBLEMÁTICO
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);

define('PROBLEMATIC_HASH', '$2y$10$DCNHZRIb1KdW6G3Y25XBP.O3II2tj7OFzAEmm5yXAHwyn12JUTf7i');
define('EXPECTED_PASSWORD', '159Qwaszx753!@*');

function debugLog($message, $data = null) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message";
    
    if ($data !== null) {
        $logMessage .= " | Data: " . (is_array($data) || is_object($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data);
    }
    
    echo $logMessage . "\n";
    file_put_contents(__DIR__ . '/password_discovery.log', $logMessage . "\n", FILE_APPEND);
}

// Limpa log anterior
file_put_contents(__DIR__ . '/password_discovery.log', '');

debugLog("=== DESCOBRINDO A SENHA REAL DO HASH ===");
debugLog("Hash to analyze", PROBLEMATIC_HASH);
debugLog("Expected password", EXPECTED_PASSWORD);

// Lista de possíveis senhas que podem ter sido usadas
$possiblePasswords = [
    // Senha original
    EXPECTED_PASSWORD,
    
    // Possíveis transformações durante o processo
    trim(EXPECTED_PASSWORD),
    htmlspecialchars(EXPECTED_PASSWORD),
    htmlentities(EXPECTED_PASSWORD),
    addslashes(EXPECTED_PASSWORD),
    stripslashes(EXPECTED_PASSWORD),
    
    // Possíveis alterações de encoding (removidas - funções deprecated no PHP 8.2+)
    // utf8_encode(EXPECTED_PASSWORD),
    // utf8_decode(EXPECTED_PASSWORD),
    
    // Possíveis alterações de case
    strtolower(EXPECTED_PASSWORD),
    strtoupper(EXPECTED_PASSWORD),
    
    // Possíveis codificações/decodificações
    urlencode(EXPECTED_PASSWORD),
    urldecode(EXPECTED_PASSWORD),
    base64_encode(EXPECTED_PASSWORD),
    
    // Possíveis MD5 (se o sistema pegou o MD5 em vez da senha)
    md5(EXPECTED_PASSWORD),
    
    // Possíveis com espaços
    ' ' . EXPECTED_PASSWORD,
    EXPECTED_PASSWORD . ' ',
    ' ' . EXPECTED_PASSWORD . ' ',
    
    // Possíveis com quebras de linha
    EXPECTED_PASSWORD . "\n",
    EXPECTED_PASSWORD . "\r",
    EXPECTED_PASSWORD . "\r\n",
    "\n" . EXPECTED_PASSWORD,
    
    // Se houve algum problema com arrays ou serialização
    serialize(EXPECTED_PASSWORD),
    
    // Se houve json encode
    json_encode(EXPECTED_PASSWORD),
    
    // Se foi tratado como número
    (string)EXPECTED_PASSWORD,
    
    // Algumas variações comuns de encoding
    iconv('UTF-8', 'ISO-8859-1', EXPECTED_PASSWORD),
    
    // Se teve algum escape SQL (removido - função deprecated)
    
    // Verificações de hash direto (caso tenha hashado um hash)
    password_hash(EXPECTED_PASSWORD, PASSWORD_BCRYPT, ['cost' => 10]),
    password_hash(EXPECTED_PASSWORD, PASSWORD_DEFAULT),
];

debugLog("Testing " . count($possiblePasswords) . " possible password variations...");

$found = false;
foreach ($possiblePasswords as $index => $testPassword) {
    if (password_verify($testPassword, PROBLEMATIC_HASH)) {
        debugLog("🎯 FOUND THE REAL PASSWORD!", $testPassword);
        debugLog("Password index", $index);
        debugLog("Password length", strlen($testPassword));
        debugLog("Password hex", bin2hex($testPassword));
        $found = true;
        break;
    }
}

if (!$found) {
    debugLog("❌ Password not found in common transformations");
    debugLog("🔍 Let's try some more exotic possibilities...");
    
    // Tentativas mais específicas
    $exoticTests = [
        // Diferentes combinações de caracteres especiais
        str_replace('!', '', EXPECTED_PASSWORD),
        str_replace('@', '', EXPECTED_PASSWORD),
        str_replace('*', '', EXPECTED_PASSWORD),
        str_replace('!@*', '', EXPECTED_PASSWORD),
        
        // Se apenas os números
        '159753',
        
        // Se apenas as letras
        'Qwaszx',
        
        // Diferentes ordenações
        '753159Qwaszx!@*',
        
        // Se houve algum truncamento
        substr(EXPECTED_PASSWORD, 0, 10),
        substr(EXPECTED_PASSWORD, 0, 15),
        substr(EXPECTED_PASSWORD, 0, 8),
        
        // Se duplicou
        EXPECTED_PASSWORD . EXPECTED_PASSWORD,
        
        // Se inverteu
        strrev(EXPECTED_PASSWORD),
    ];
    
    foreach ($exoticTests as $index => $testPassword) {
        if (password_verify($testPassword, PROBLEMATIC_HASH)) {
            debugLog("🎯 FOUND THE REAL PASSWORD (EXOTIC)!", $testPassword);
            debugLog("Exotic test index", $index);
            $found = true;
            break;
        }
    }
}

if (!$found) {
    debugLog("❌ Could not determine the original password");
    debugLog("💡 The hash was likely generated with a completely different password");
    debugLog("🔧 Recommendation: Reset the password in database and test again");
}

debugLog("\n=== DISCOVERY COMPLETE ===");

echo "\n=== RESULTADO ===\n";
if ($found) {
    echo "✅ Descobrimos qual senha foi usada no hash!\n";
} else {
    echo "❌ Não conseguimos descobrir a senha original\n";
    echo "💡 Isso confirma que o hash foi gerado com senha diferente\n";
}
echo "📋 Detalhes completos em: password_discovery.log\n";
?>
