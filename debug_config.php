<?php
/**
 * DEBUG DA CONFIGURAÇÃO
 */

echo "<h1>Debug da Configuração - KW24 Sistema</h1>";

echo "<h2>Informações do Servidor:</h2>";
echo "HTTP_HOST: " . ($_SERVER['HTTP_HOST'] ?? 'indefinido') . "<br>";
echo "SERVER_NAME: " . ($_SERVER['SERVER_NAME'] ?? 'indefinido') . "<br>";
echo "PHP Version: " . phpversion() . "<br>";

echo "<h2>Detectando Ambiente:</h2>";
$isLocal = ($_SERVER['HTTP_HOST'] === 'localhost' || strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false);
echo "É ambiente local? " . ($isLocal ? 'SIM' : 'NÃO') . "<br>";

echo "<h2>Configuração Carregada:</h2>";
$config = require_once __DIR__ . '/config/config.php';

echo "<pre>";
print_r($config);
echo "</pre>";

echo "<h2>Testando Conexão com a Configuração:</h2>";

try {
    $dbConfig = $config['database'];
    
    echo "Host: {$dbConfig['host']}<br>";
    echo "Database: {$dbConfig['dbname']}<br>";
    echo "Username: {$dbConfig['username']}<br>";
    echo "Password: " . (empty($dbConfig['password']) ? 'VAZIO' : 'PREENCHIDO (' . strlen($dbConfig['password']) . ' chars)') . "<br>";
    
    $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset={$dbConfig['charset']}";
    
    $pdo = new PDO(
        $dsn,
        $dbConfig['username'],
        $dbConfig['password'],
        $dbConfig['options']
    );
    
    echo "<br>✅ <strong>Conexão estabelecida com sucesso!</strong><br>";
    
    // Teste rápido
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Colaboradores");
    $result = $stmt->fetch();
    echo "✅ Total de colaboradores: {$result['total']}<br>";
    
} catch (PDOException $e) {
    echo "<br>❌ <strong>Erro na conexão:</strong> " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><strong>Debug concluído!</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
