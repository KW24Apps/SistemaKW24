<?php
/**
 * TESTE DE CONEXÃO COM BANCO DE DADOS
 */

echo "<h1>Teste de Conexão - KW24 Sistema</h1>";

// Configurações
$host = 'localhost';
$dbname = 'kw24co49_api_kwconfig';
$username = 'kw24co49_kw24';
$password = 'BlFOyf%X}#jXwrR-vi';

echo "<h2>1. Testando conexão PDO...</h2>";

try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ <strong>Conexão estabelecida com sucesso!</strong><br>";
    echo "📂 Banco: $dbname<br>";
    echo "👤 Usuário: $username<br>";
    
} catch (PDOException $e) {
    echo "❌ <strong>Erro na conexão:</strong> " . $e->getMessage() . "<br>";
    exit;
}

echo "<h2>2. Verificando tabelas...</h2>";

try {
    $stmt = $pdo->query("SHOW TABLES");
    $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    echo "📋 <strong>Tabelas encontradas:</strong><br>";
    foreach ($tables as $table) {
        echo "- $table<br>";
        
        if ($table === 'Colaboradores') {
            echo "  ✅ <strong>Tabela 'Colaboradores' encontrada!</strong><br>";
        }
    }
    
} catch (PDOException $e) {
    echo "❌ <strong>Erro ao listar tabelas:</strong> " . $e->getMessage() . "<br>";
}

echo "<h2>3. Verificando estrutura da tabela Colaboradores...</h2>";

try {
    $stmt = $pdo->query("DESCRIBE Colaboradores");
    $columns = $stmt->fetchAll();
    
    echo "🏗️ <strong>Colunas da tabela 'Colaboradores':</strong><br>";
    echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
    echo "<tr><th>Campo</th><th>Tipo</th><th>Nulo</th><th>Chave</th><th>Padrão</th></tr>";
    
    foreach ($columns as $col) {
        echo "<tr>";
        echo "<td>{$col['Field']}</td>";
        echo "<td>{$col['Type']}</td>";
        echo "<td>{$col['Null']}</td>";
        echo "<td>{$col['Key']}</td>";
        echo "<td>{$col['Default']}</td>";
        echo "</tr>";
    }
    echo "</table>";
    
} catch (PDOException $e) {
    echo "❌ <strong>Erro ao verificar estrutura:</strong> " . $e->getMessage() . "<br>";
}

echo "<h2>4. Testando busca de colaboradores...</h2>";

try {
    $stmt = $pdo->query("SELECT COUNT(*) as total FROM Colaboradores");
    $result = $stmt->fetch();
    
    echo "📊 <strong>Total de colaboradores:</strong> {$result['total']}<br>";
    
    if ($result['total'] > 0) {
        echo "<br>👥 <strong>Primeiros 5 colaboradores:</strong><br>";
        $stmt = $pdo->query("SELECT id, Nome, UserName, Email, ativo FROM Colaboradores LIMIT 5");
        $users = $stmt->fetchAll();
        
        echo "<table border='1' style='border-collapse: collapse; margin: 10px 0;'>";
        echo "<tr><th>ID</th><th>Nome</th><th>UserName</th><th>Email</th><th>Ativo</th></tr>";
        
        foreach ($users as $user) {
            echo "<tr>";
            echo "<td>{$user['id']}</td>";
            echo "<td>{$user['Nome']}</td>";
            echo "<td>{$user['UserName']}</td>";
            echo "<td>{$user['Email']}</td>";
            echo "<td>" . ($user['ativo'] ? 'Sim' : 'Não') . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    }
    
} catch (PDOException $e) {
    echo "❌ <strong>Erro ao buscar colaboradores:</strong> " . $e->getMessage() . "<br>";
}

echo "<hr>";
echo "<p><strong>Teste concluído!</strong> " . date('Y-m-d H:i:s') . "</p>";
?>
