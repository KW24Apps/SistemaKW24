# Monitor de Log de Debug
# Execute este script para acompanhar o log em tempo real

Write-Host "🔍 MONITOR DE DEBUG LOGIN - KW24 APPS" -ForegroundColor Green
Write-Host "==============================================" -ForegroundColor Green
Write-Host ""
Write-Host "📋 Monitorando: login_debug.log" -ForegroundColor Yellow
Write-Host "🚀 Faça as tentativas de login agora..." -ForegroundColor Yellow
Write-Host "💡 Use Ctrl+C para parar o monitoramento" -ForegroundColor Cyan
Write-Host ""

$logFile = ".\login_debug.log"

# Verifica se o arquivo existe
if (-not (Test-Path $logFile)) {
    Write-Host "⏳ Aguardando criação do arquivo de log..." -ForegroundColor Magenta
    while (-not (Test-Path $logFile)) {
        Start-Sleep -Seconds 1
    }
}

# Monitora o arquivo
try {
    Get-Content $logFile -Wait | ForEach-Object {
        $line = $_
        
        # Colorização baseada no conteúdo
        if ($line -match "===.*===") {
            Write-Host $line -ForegroundColor Green
        }
        elseif ($line -match "SUCCESS") {
            Write-Host $line -ForegroundColor Green
        }
        elseif ($line -match "FAILED|ERROR|EXCEPTION") {
            Write-Host $line -ForegroundColor Red
        }
        elseif ($line -match "DEBUG|AUTH") {
            Write-Host $line -ForegroundColor Yellow
        }
        else {
            Write-Host $line -ForegroundColor White
        }
    }
}
catch {
    Write-Host "❌ Erro no monitoramento: $_" -ForegroundColor Red
}
