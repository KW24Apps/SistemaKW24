<?php
/**
 * VISUALIZADOR DE LOGS - Para acessar via navegador
 * Acesse: https://app.kw24.com.br/Apps/view_logs.php
 */

// Proteção básica
session_start();
if (!isset($_SESSION['user_authenticated'])) {
    die('❌ Acesso negado. Faça login primeiro.');
}

$logFiles = [
    'migration_debug.log' => 'Log de Migração de Senha',
    'login_debug.log' => 'Log de Debug do Login',
    'logs/auth.log' => 'Log de Autenticação'
];

$selectedLog = $_GET['log'] ?? 'migration_debug.log';

?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Visualizador de Logs - KW24</title>
    <style>
        body { font-family: monospace; margin: 20px; background: #1a1a1a; color: #00ff00; }
        .header { background: #333; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
        .log-selector { margin-bottom: 20px; }
        .log-selector select { padding: 8px; font-size: 14px; }
        .log-content { 
            background: #000; 
            border: 1px solid #333; 
            padding: 15px; 
            height: 600px; 
            overflow-y: auto; 
            white-space: pre-wrap;
            border-radius: 5px;
        }
        .refresh-btn { 
            background: #007bff; 
            color: white; 
            padding: 8px 16px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            margin-left: 10px;
        }
        .clear-btn { 
            background: #dc3545; 
            color: white; 
            padding: 8px 16px; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            margin-left: 10px;
        }
        .success { color: #00ff00; }
        .error { color: #ff4444; }
        .warning { color: #ffaa00; }
    </style>
</head>
<body>
    <div class="header">
        <h1>🔍 Visualizador de Logs - KW24 Apps</h1>
        <p>Logs de debug para diagnóstico do sistema de autenticação</p>
    </div>

    <div class="log-selector">
        <form method="get" style="display: inline;">
            <select name="log" onchange="this.form.submit()">
                <?php foreach ($logFiles as $file => $name): ?>
                    <option value="<?= $file ?>" <?= $selectedLog === $file ? 'selected' : '' ?>>
                        <?= $name ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="submit" class="refresh-btn">🔄 Atualizar</button>
        </form>
        
        <form method="post" style="display: inline;">
            <input type="hidden" name="clear_log" value="<?= $selectedLog ?>">
            <button type="submit" class="clear-btn" onclick="return confirm('Limpar este log?')">
                🗑️ Limpar Log
            </button>
        </form>
    </div>

    <?php
    // Processar limpeza de log
    if (isset($_POST['clear_log'])) {
        $logFile = __DIR__ . '/' . $_POST['clear_log'];
        if (file_exists($logFile)) {
            file_put_contents($logFile, '');
            echo "<div class='success'>✅ Log limpo com sucesso!</div>";
        }
    }

    // Exibir conteúdo do log
    $logFile = __DIR__ . '/' . $selectedLog;
    $logContent = '';
    
    if (file_exists($logFile)) {
        $logContent = file_get_contents($logFile);
        if (empty($logContent)) {
            $logContent = "📝 Log vazio ou ainda não foi gerado.\n\nPara gerar logs:\n1. Faça login no sistema\n2. Se sua senha for texto plano, será migrada automaticamente\n3. Os logs aparecerão aqui\n";
        }
    } else {
        $logContent = "❌ Arquivo de log não encontrado: {$selectedLog}\n\nO arquivo será criado automaticamente quando:\n1. Você fizer login\n2. O sistema executar a migração de senha\n";
    }

    // Aplicar cores aos logs
    $logContent = preg_replace('/\[.*SUCCESS.*\]/', '<span class="success">$0</span>', $logContent);
    $logContent = preg_replace('/\[.*FAILED.*\]/', '<span class="error">$0</span>', $logContent);
    $logContent = preg_replace('/\[.*ERROR.*\]/', '<span class="error">$0</span>', $logContent);
    $logContent = preg_replace('/\[.*MIGRATION.*\]/', '<span class="warning">$0</span>', $logContent);
    ?>

    <div class="log-content"><?= $logContent ?></div>

    <script>
        // Auto-refresh a cada 5 segundos
        setTimeout(() => location.reload(), 5000);
    </script>
</body>
</html>
