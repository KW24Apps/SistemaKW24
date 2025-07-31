<?php
/**
 * TESTE DIRETO DE CONEXÃO
 */

echo "<h1>Teste Direto - Sem Classes</h1>";

// Credenciais diretas (as mesmas da API funcionante)
$host = 'localhost';
$dbname = 'kw24co49_api_kwconfig';
$username = 'kw24co49_kw24'; 
$password = 'BlFOyf%X}#jXwrR-vi';

echo "<h2>1. Teste PDO Direto</h2>";

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    
    echo "DSN: $dsn<br>";
    echo "Username: $username<br>";
    echo "Password: " . (empty($password) ? 'VAZIO' : 'COM ' . strlen($password) . ' caracteres') . "<br><br>";
    
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ <strong>CONEXÃO OK!</strong><br><br>";
    
    // Testa consulta
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Colaboradores");
    $result = $stmt->fetch();
    echo "✅ Colaboradores na base: {$result['total']}<br><br>";
    
    // Busca um usuário de teste
    $stmt = $pdo->prepare("SELECT id, Nome, UserName, Email FROM Colaboradores WHERE ativo = 1 LIMIT 3");
    $stmt->execute();
    $users = $stmt->fetchAll();
    
    echo "👥 <strong>Usuários ativos para teste:</strong><br>";
    foreach ($users as $user) {
        echo "- {$user['UserName']} ({$user['Nome']}) - ID: {$user['id']}<br>";
    }
    
} catch (PDOException $e) {
    echo "❌ <strong>ERRO:</strong> " . $e->getMessage() . "<br>";
    echo "Código: " . $e->getCode() . "<br>";
}

echo "<h2>2. Teste com as Classes do Sistema</h2>";

try {
    require_once __DIR__ . '/helpers/Database.php';
    
    $db = Database::getInstance();
    echo "✅ Database class carregada<br>";
    
    $result = $db->fetchOne("SELECT COUNT(*) as total FROM Colaboradores");
    echo "✅ Query via classe: {$result['total']} colaboradores<br>";
    
} catch (Exception $e) {
    echo "❌ <strong>ERRO na classe:</strong> " . $e->getMessage() . "<br>";
}

echo "<h2>3. Teste do DAO</h2>";

try {
    require_once __DIR__ . '/dao/ColaboradorDAO.php';
    
    $dao = new ColaboradorDAO();
    echo "✅ ColaboradorDAO carregado<br>";
    
    $users = $dao->findAllActive();
    echo "✅ DAO funcionando: " . count($users) . " usuários ativos<br>";
    
} catch (Exception $e) {
    echo "❌ <strong>ERRO no DAO:</strong> " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><strong>Diagnóstico completo!</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
