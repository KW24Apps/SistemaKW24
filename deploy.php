<?php
/**
 * Deploy Script - Sistema Administrativo KW24
 * Webhook para deploy automático do repositório SistemaKW24
 */

// Configurações
$repoDir = '/home/kw24co49/app.kw24.com.br/Apps';
$secret = 'hF9kL2xV7qP3sY8mZ4bW1cN0'; // MESMA chave configurada no Webhook GitHub
$logFile = '/home/kw24co49/app.kw24.com.br/deploy.log';

// Função para log
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND | LOCK_EX);
}

// Inicia o log
writeLog("=== DEPLOY INICIADO ===");
writeLog("Usuário executando: " . get_current_user());
writeLog("IP do cliente: " . ($_SERVER['REMOTE_ADDR'] ?? 'N/A'));

try {
    // Verifica se é uma requisição POST
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Método não permitido. Apenas POST é aceito.');
    }

    // Recebe os dados brutos do POST
    $payload = file_get_contents('php://input');
    
    if (empty($payload)) {
        throw new Exception('Payload vazio recebido.');
    }

    // Cabeçalho enviado pelo GitHub
    $signature = $_SERVER['HTTP_X_HUB_SIGNATURE_256'] ?? '';
    
    if (empty($signature)) {
        throw new Exception('Assinatura GitHub não encontrada.');
    }

    // Gera assinatura local
    $hash = 'sha256=' . hash_hmac('sha256', $payload, $secret, false);

    // Compara assinatura
    if (!hash_equals($hash, $signature)) {
        writeLog("ERRO: Assinatura inválida. Esperado: $hash | Recebido: $signature");
        http_response_code(403);
        exit('Acesso negado. Assinatura inválida.');
    }

    writeLog("Assinatura verificada com sucesso");

    // Decodifica o payload para verificar detalhes
    $data = json_decode($payload, true);
    $branch = $data['ref'] ?? 'unknown';
    $repository = $data['repository']['name'] ?? 'unknown';
    
    writeLog("Repository: $repository");
    writeLog("Branch: $branch");

    // Verifica se é a branch main
    if ($branch !== 'refs/heads/main') {
        writeLog("AVISO: Deploy apenas para branch main. Branch recebida: $branch");
        echo "Deploy apenas para branch main. Branch ignorada: $branch";
        exit;
    }

    // Verifica se o diretório existe
    if (!is_dir($repoDir)) {
        throw new Exception("Diretório do repositório não encontrado: $repoDir");
    }

    // Backup dos arquivos de configuração locais (se existirem)
    $configFiles = [
        'config/local_config.php',
        '.env.local'
    ];

    $backupDir = '/tmp/deploy_backup_' . date('YmdHis');
    mkdir($backupDir, 0755, true);

    foreach ($configFiles as $configFile) {
        $fullPath = "$repoDir/$configFile";
        if (file_exists($fullPath)) {
            copy($fullPath, "$backupDir/" . basename($configFile));
            writeLog("Backup criado: $configFile");
        }
    }

    // Limpa logs antigos (mas mantém a estrutura de pastas)
    if (is_dir("$repoDir/logs")) {
        $cleanLogs = shell_exec("find $repoDir/logs -name '*.log' -type f -delete 2>&1");
        writeLog("Logs limpos: " . ($cleanLogs ?: 'OK'));
    }

    // Executa o git pull
    writeLog("Executando git pull...");
    $gitCommands = [
        "cd $repoDir",
        "git fetch origin main",
        "git reset --hard origin/main",
        "git clean -fd"
    ];
    
    $gitCommand = implode(' && ', $gitCommands);
    $output = shell_exec("$gitCommand 2>&1");
    
    writeLog("Saída do git: " . $output);

    // Restaura arquivos de configuração
    foreach ($configFiles as $configFile) {
        $backupFile = "$backupDir/" . basename($configFile);
        $targetFile = "$repoDir/$configFile";
        
        if (file_exists($backupFile)) {
            copy($backupFile, $targetFile);
            writeLog("Configuração restaurada: $configFile");
        }
    }

    // Remove backup temporário
    shell_exec("rm -rf $backupDir");

    // Define permissões corretas
    shell_exec("chmod -R 755 $repoDir");
    shell_exec("chmod -R 644 $repoDir/config/*.php");
    
    writeLog("Permissões atualizadas");

    // Verifica se há atualizações de dependências (futuro)
    // Aqui você pode adicionar comandos como composer install se necessário

    writeLog("=== DEPLOY CONCLUÍDO COM SUCESSO ===");

    // Resposta de sucesso
    echo json_encode([
        'status' => 'success',
        'message' => 'Deploy realizado com sucesso!',
        'timestamp' => date('Y-m-d H:i:s'),
        'repository' => $repository,
        'branch' => $branch,
        'output' => $output
    ]);

} catch (Exception $e) {
    writeLog("ERRO: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage(),
        'timestamp' => date('Y-m-d H:i:s')
    ]);
}
?>
