<?php
/**
 * MIGRATION SCRIPT - ATUALIZAÇÃO DE SENHAS
 * Script para migrar senhas antigas (texto plano/MD5) para hash seguro
 * Execute este script UMA VEZ após implementar o novo sistema
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../helpers/Database.php';

class PasswordMigration {
    private $db;
    private $config;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->config = require_once __DIR__ . '/../config/config.php';
    }
    
    /**
     * Migra todas as senhas para hash seguro (adaptado para tabela Colaboradores)
     */
    public function migratePasswords(): void {
        echo "Iniciando migração de senhas...\n";
        
        // Busca todos os usuários com senhas não hash
        $sql = "SELECT id, UserName, senha FROM Colaboradores WHERE ativo = 1";
        $users = $this->db->fetchAll($sql);
        
        $migrated = 0;
        $skipped = 0;
        
        foreach ($users as $user) {
            if ($this->needsMigration($user['senha'])) {
                if ($this->migrateUserPassword($user)) {
                    $migrated++;
                    echo "✅ Migrado: {$user['UserName']}\n";
                } else {
                    echo "❌ Erro ao migrar: {$user['UserName']}\n";
                }
            } else {
                $skipped++;
                echo "⏭️ Já migrado: {$user['UserName']}\n";
            }
        }
        
        echo "\n📊 Resumo da migração:\n";
        echo "- Senhas migradas: {$migrated}\n";
        echo "- Já estavam seguras: {$skipped}\n";
        echo "- Total processado: " . ($migrated + $skipped) . "\n";
    }
    
    /**
     * Verifica se a senha precisa ser migrada
     */
    private function needsMigration(string $password): bool {
        // Se password_get_info retorna algo, já é um hash válido
        $info = password_get_info($password);
        return $info['algo'] === null;
    }
    
    /**
     * Migra senha de um usuário específico
     */
    private function migrateUserPassword(array $user): bool {
        try {
            // Para senhas em texto plano, assumimos que são as senhas padrão
            // Para MD5, tentamos algumas senhas comuns
            $possiblePasswords = [
                $user['senha'], // texto plano
                '123456',       // senha padrão comum
                'admin',        // senha admin
                $user['UserName'] // usuario como senha
            ];
            
            $newPassword = null;
            
            // Tenta identificar a senha original
            foreach ($possiblePasswords as $testPassword) {
                if ($this->verifyOldPassword($testPassword, $user['senha'])) {
                    $newPassword = $testPassword;
                    break;
                }
            }
            
            if (!$newPassword) {
                // Se não conseguiu identificar, usa a própria senha como fallback
                $newPassword = $user['senha'];
            }
            
            // Gera novo hash seguro
            $hashedPassword = password_hash(
                $newPassword, 
                $this->config['security']['password_algorithm']
            );
            
            // Atualiza no banco
            $sql = "UPDATE Colaboradores SET senha = :password, atualizado_em = NOW() WHERE id = :id";
            $this->db->execute($sql, [
                'password' => $hashedPassword,
                'id' => $user['id']
            ]);
            
            return true;
            
        } catch (Exception $e) {
            echo "Erro na migração do usuário {$user['UserName']}: " . $e->getMessage() . "\n";
            return false;
        }
    }
    
    /**
     * Verifica senha com método antigo (MD5 ou texto plano)
     */
    private function verifyOldPassword(string $plainPassword, string $storedPassword): bool {
        // Texto plano
        if ($plainPassword === $storedPassword) {
            return true;
        }
        
        // MD5
        if (md5($plainPassword) === $storedPassword) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Cria usuário padrão se não existir (adaptado para tabela Colaboradores)
     */
    public function createDefaultUser(): void {
        echo "Verificando usuário padrão...\n";
        
        $sql = "SELECT id FROM Colaboradores WHERE UserName = 'KW24' LIMIT 1";
        $existing = $this->db->fetchOne($sql);
        
        if (!$existing) {
            $hashedPassword = password_hash(
                '123456', 
                $this->config['security']['password_algorithm']
            );
            
            $sql = "
                INSERT INTO Colaboradores (Nome, CPF, Cargo, Telefone, Email, UserName, senha, perfil, ativo) 
                VALUES (:nome, :cpf, :cargo, :telefone, :email, :usuario, :senha, :perfil, 1)
            ";
            
            $this->db->execute($sql, [
                'nome' => 'Administrador KW24',
                'cpf' => '000.000.000-00',
                'cargo' => 'Administrador',
                'telefone' => '(48) 99999-9999',
                'email' => 'admin@kw24.com.br',
                'usuario' => 'KW24',
                'senha' => $hashedPassword,
                'perfil' => 'Administrador'
            ]);
            
            echo "✅ Usuário padrão criado: KW24 / 123456\n";
        } else {
            echo "ℹ️ Usuário padrão já existe\n";
        }
    }
}

// Execução do script
if (php_sapi_name() === 'cli') {
    // Executando via linha de comando
    try {
        $migration = new PasswordMigration();
        $migration->createDefaultUser();
        $migration->migratePasswords();
        echo "\n🎉 Migração concluída com sucesso!\n";
    } catch (Exception $e) {
        echo "\n❌ Erro na migração: " . $e->getMessage() . "\n";
        exit(1);
    }
} else {
    // Executando via web (com proteção básica)
    if (isset($_GET['run_migration']) && $_GET['run_migration'] === 'confirm') {
        echo "<pre>";
        try {
            $migration = new PasswordMigration();
            $migration->createDefaultUser();
            $migration->migratePasswords();
            echo "\n🎉 Migração concluída com sucesso!\n";
        } catch (Exception $e) {
            echo "\n❌ Erro na migração: " . $e->getMessage() . "\n";
        }
        echo "</pre>";
    } else {
        echo "<h2>Migração de Senhas - KW24 Apps v2</h2>";
        echo "<p>Este script irá migrar todas as senhas para hash seguro.</p>";
        echo "<p><strong>ATENÇÃO:</strong> Execute apenas UMA VEZ!</p>";
        echo "<a href='?run_migration=confirm' onclick='return confirm(\"Tem certeza?\")'>Executar Migração</a>";
    }
}
