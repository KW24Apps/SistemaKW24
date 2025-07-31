<?php
/**
 * SCRIPT PARA ATUALIZAR SENHA DO GABRIEL - KW24 APPS V2
 * Execute este script para converter a senha atual para hash seguro
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';

try {
    $db = Database::getInstance();
    $config = require_once __DIR__ . '/../config/config.php';
    
    // Senha atual: 159Qwaszx753!@*
    // Vamos gerar o hash seguro desta senha
    $senhaAtual = '159Qwaszx753!@*';
    $hashedPassword = password_hash($senhaAtual, $config['security']['password_algorithm']);
    
    echo "🔐 Atualizando senha do Gabriel Acker...\n";
    echo "Senha original: {$senhaAtual}\n";
    echo "Hash gerado: {$hashedPassword}\n\n";
    
    // Atualiza a senha no banco
    $sql = "UPDATE Colaboradores SET senha = :password, atualizado_em = NOW() WHERE UserName = 'gabriel.acker'";
    $db->execute($sql, ['password' => $hashedPassword]);
    
    echo "✅ Senha atualizada com sucesso!\n";
    echo "🔑 Login: gabriel.acker\n";
    echo "🔑 Senha: 159Qwaszx753!@*\n\n";
    
    // Teste de verificação
    echo "🧪 Testando verificação da senha...\n";
    $verificacao = password_verify($senhaAtual, $hashedPassword);
    echo $verificacao ? "✅ Verificação OK!" : "❌ Erro na verificação!";
    echo "\n\n";
    
    // Criar também uma senha simples para teste
    $senhaSimples = '123456';
    $hashSimples = password_hash($senhaSimples, $config['security']['password_algorithm']);
    
    echo "🔧 Criando usuário de teste KW24...\n";
    
    // Verifica se usuário KW24 já existe
    $sqlCheck = "SELECT id FROM Colaboradores WHERE UserName = 'KW24' LIMIT 1";
    $existing = $db->fetchOne($sqlCheck);
    
    if (!$existing) {
        $sqlInsert = "
            INSERT INTO Colaboradores (Nome, CPF, Cargo, Telefone, Email, UserName, senha, perfil, ativo) 
            VALUES (:nome, :cpf, :cargo, :telefone, :email, :usuario, :senha, :perfil, 1)
        ";
        
        $db->execute($sqlInsert, [
            'nome' => 'Usuário Teste KW24',
            'cpf' => '000.000.000-00',
            'cargo' => 'Teste',
            'telefone' => '(00) 00000-0000',
            'email' => 'teste@kw24.com.br',
            'usuario' => 'KW24',
            'senha' => $hashSimples,
            'perfil' => 'Administrador'
        ]);
        
        echo "✅ Usuário KW24 criado!\n";
        echo "🔑 Login: KW24\n";
        echo "🔑 Senha: 123456\n";
    } else {
        // Apenas atualiza a senha do usuário existente
        $sqlUpdate = "UPDATE Colaboradores SET senha = :password WHERE UserName = 'KW24'";
        $db->execute($sqlUpdate, ['password' => $hashSimples]);
        echo "✅ Senha do usuário KW24 atualizada!\n";
        echo "🔑 Login: KW24\n";
        echo "🔑 Senha: 123456\n";
    }
    
    echo "\n🎉 Configuração concluída!\n";
    echo "Agora você pode testar o login com:\n";
    echo "- gabriel.acker / 159Qwaszx753!@*\n";
    echo "- KW24 / 123456\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
