<?php
/**
 * DEBUG ESPECÍFICO PARA MIGRAÇÃO DE SENHA
 * Adicione este código no método migrateUserPassword do AuthenticationService
 */

private function migrateUserPassword(int $userId, string $plainPassword): bool {
    // ========== DEBUG ESPECÍFICO ==========
    $debugFile = __DIR__ . '/../migration_debug.log';
    $timestamp = date('Y-m-d H:i:s.u');
    
    function migrationLog($message, $data = null) use ($debugFile, $timestamp) {
        $logMessage = "[$timestamp] MIGRATION: $message";
        if ($data !== null) {
            $logMessage .= " | Data: " . (is_array($data) || is_object($data) ? json_encode($data, JSON_UNESCAPED_UNICODE) : $data);
        }
        $logMessage .= "\n";
        file_put_contents($debugFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    migrationLog("=== MIGRATION STARTED ===");
    migrationLog("User ID", $userId);
    migrationLog("Plain password received", $plainPassword);
    migrationLog("Password length", strlen($plainPassword));
    migrationLog("Password as hex", bin2hex($plainPassword));
    migrationLog("Password char by char", str_split($plainPassword));
    
    // Busca usuário atual para ver senha no banco
    $currentUser = $this->colaboradorDAO->findById($userId);
    if ($currentUser) {
        migrationLog("Current password in DB", $currentUser['senha'] ?? 'NOT_FOUND');
        migrationLog("Current password length", strlen($currentUser['senha'] ?? ''));
        migrationLog("Current password as hex", bin2hex($currentUser['senha'] ?? ''));
    }
    
    // Gera novo hash
    migrationLog("Generating new hash with Argon2ID");
    $newHash = password_hash(
        $plainPassword, 
        $this->config['security']['password_algorithm'],
        $this->config['security']['password_options']
    );
    
    migrationLog("Generated hash", $newHash);
    migrationLog("Generated hash length", strlen($newHash));
    
    // Testa imediatamente se o hash funciona
    $testVerify = password_verify($plainPassword, $newHash);
    migrationLog("Immediate hash test", $testVerify ? 'SUCCESS' : 'FAILED');
    
    // Se falhou, testa várias variações da senha
    if (!$testVerify) {
        migrationLog("HASH TEST FAILED - Testing variations");
        $variations = [
            trim($plainPassword),
            rtrim($plainPassword),
            ltrim($plainPassword),
            str_replace("\r", "", $plainPassword),
            str_replace("\n", "", $plainPassword),
            str_replace("\r\n", "", $plainPassword),
        ];
        
        foreach ($variations as $i => $variation) {
            $testVar = password_verify($variation, $newHash);
            migrationLog("Variation $i test", $testVar ? 'SUCCESS' : 'FAILED');
            migrationLog("Variation $i value", $variation);
            migrationLog("Variation $i hex", bin2hex($variation));
        }
    }
    
    // Atualiza no banco
    migrationLog("Updating password in database");
    $updateResult = $this->colaboradorDAO->updatePassword($userId, $newHash);
    migrationLog("Database update result", $updateResult ? 'SUCCESS' : 'FAILED');
    
    // Verifica se foi salvo corretamente
    $updatedUser = $this->colaboradorDAO->findById($userId);
    if ($updatedUser) {
        migrationLog("Password after update", $updatedUser['senha'] ?? 'NOT_FOUND');
        migrationLog("Stored hash matches generated", ($updatedUser['senha'] === $newHash) ? 'MATCH' : 'NO_MATCH');
        
        // Testa a senha original contra o hash salvo
        $finalTest = password_verify($plainPassword, $updatedUser['senha'] ?? '');
        migrationLog("Final test - original password vs stored hash", $finalTest ? 'SUCCESS' : 'FAILED');
    }
    
    migrationLog("=== MIGRATION COMPLETED ===\n");
    // ========== FIM DEBUG ==========
    
    // Código original continua aqui...
    try {
        $newHash = password_hash(
            $plainPassword, 
            $this->config['security']['password_algorithm'],
            $this->config['security']['password_options']
        );
        
        return $this->colaboradorDAO->updatePassword($userId, $newHash);
        
    } catch (Exception $e) {
        $this->logError('Erro na migração de senha: ' . $e->getMessage());
        return false;
    }
}
?>
