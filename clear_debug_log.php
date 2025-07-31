<?php
/**
 * LIMPA LOG DE DEBUG
 */

$logFile = __DIR__ . '/login_debug.log';

if (file_exists($logFile)) {
    unlink($logFile);
    echo "✅ Log anterior removido com sucesso!\n";
} else {
    echo "ℹ️ Nenhum log anterior encontrado.\n";
}

echo "🚀 Agora faça algumas tentativas de login e observe o comportamento:\n";
echo "1. Primeira tentativa (deve falhar)\n";
echo "2. Segunda tentativa (deve funcionar?)\n";
echo "3. Logout e teste novamente\n\n";
echo "📋 O log será salvo em: login_debug.log\n";
echo "🔍 Use este comando para monitorar: Get-Content login_debug.log -Wait\n";
?>
