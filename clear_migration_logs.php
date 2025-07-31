<?php
/**
 * LIMPA LOGS DE DEBUG PARA MIGRAÇÃO
 */

$logs = [
    __DIR__ . '/migration_debug.log',
    __DIR__ . '/login_debug.log',
    __DIR__ . '/auth.log'
];

echo "🧹 LIMPANDO LOGS DE DEBUG\n";
echo "========================\n\n";

foreach ($logs as $logFile) {
    if (file_exists($logFile)) {
        unlink($logFile);
        echo "✅ Removido: " . basename($logFile) . "\n";
    } else {
        echo "ℹ️ Não existe: " . basename($logFile) . "\n";
    }
}

echo "\n🚀 TESTE AGORA:\n";
echo "1. Coloque sua senha em texto no banco: '159Qwaszx753!@*'\n";
echo "2. Faça login (vai migrar automaticamente)\n";
echo "3. Saia e tente logar novamente\n";
echo "4. Me envie o arquivo migration_debug.log\n\n";
echo "📋 O log vai mostrar EXATAMENTE qual senha está sendo usada!\n";
?>
