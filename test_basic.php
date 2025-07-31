<?php
/**
 * TESTE BÁSICO - Apenas para verificar se os arquivos carregam
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "🔍 TESTE BÁSICO DE CARREGAMENTO<br><br>";

echo "1. Testando require_once do config...<br>";
try {
    $config = require_once __DIR__ . '/config/config.php';
    echo "✅ Config carregado<br>";
} catch(Exception $e) {
    echo "❌ Erro no config: " . $e->getMessage() . "<br>";
}

echo "<br>2. Testando Database class...<br>";
try {
    require_once __DIR__ . '/helpers/Database.php';
    echo "✅ Database class carregada<br>";
} catch(Exception $e) {
    echo "❌ Erro na Database: " . $e->getMessage() . "<br>";
}

echo "<br>3. Testando ColaboradorDAO...<br>";
try {
    require_once __DIR__ . '/dao/ColaboradorDAO.php';
    echo "✅ ColaboradorDAO carregado<br>";
} catch(Exception $e) {
    echo "❌ Erro no ColaboradorDAO: " . $e->getMessage() . "<br>";
}

echo "<br>4. Testando AuthenticationService...<br>";
try {
    require_once __DIR__ . '/services/AuthenticationService.php';
    echo "✅ AuthenticationService carregado<br>";
} catch(Exception $e) {
    echo "❌ Erro no AuthenticationService: " . $e->getMessage() . "<br>";
}

echo "<br>✅ TESTE BÁSICO CONCLUÍDO - Se chegou até aqui, os arquivos estão carregando corretamente!<br>";
?>
