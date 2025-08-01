<?php
/**
 * EMAIL SERVICE - KW24 APPS V2
 * Serviço de envio de emails usando configuração SMTP
 */

class EmailService {
    private $config;
    private $templatesPath;
    
    public function __construct() {
        $this->config = require_once __DIR__ . '/../config/email_config.php';
        $this->templatesPath = $this->config['templates_path'];
    }
    
    /**
     * Envia email de recuperação de senha
     */
    public function sendPasswordRecovery(string $toEmail, string $userName, string $recoveryCode): bool {
        try {
            // Carregar template HTML
            $htmlContent = $this->loadTemplate('password-recovery.html', [
                '{{USER_NAME}}' => htmlspecialchars($userName),
                '{{RECOVERY_CODE}}' => $recoveryCode,
                '{{CURRENT_YEAR}}' => date('Y')
            ]);
            
            // Enviar email
            return $this->sendEmail(
                $toEmail,
                'Recuperação de Senha - Sistema KW24',
                $htmlContent,
                true // isHTML
            );
            
        } catch (Exception $e) {
            $this->logError('Erro ao enviar email de recuperação', [
                'email' => $toEmail,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Envia email genérico
     */
    public function sendEmail(string $to, string $subject, string $body, bool $isHTML = false): bool {
        try {
            // Validar configuração
            $validation = $this->validateConfig();
            if (!$validation['valid']) {
                throw new Exception('Configuração inválida: ' . $validation['error']);
            }
            
            // Usar função mail() nativa do PHP (compatível com Hostgator)
            $headers = $this->buildHeaders($isHTML);
            
            // Enviar
            $sent = mail($to, $subject, $body, $headers);
            
            if ($sent) {
                $this->logSuccess($to, $subject);
                return true;
            } else {
                throw new Exception('Falha na função mail()');
            }
            
        } catch (Exception $e) {
            $this->logError('Erro no envio de email', [
                'to' => $to,
                'subject' => $subject,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Constrói headers do email
     */
    private function buildHeaders(bool $isHTML = false): string {
        $headers = [];
        
        // From
        $headers[] = 'From: ' . $this->config['from_name'] . ' <' . $this->config['from_email'] . '>';
        
        // Reply-To
        if (!empty($this->config['reply_to'])) {
            $headers[] = 'Reply-To: ' . $this->config['reply_to'];
        }
        
        // Content-Type
        if ($isHTML) {
            $headers[] = 'Content-Type: text/html; charset=' . $this->config['charset'];
            $headers[] = 'MIME-Version: 1.0';
        } else {
            $headers[] = 'Content-Type: text/plain; charset=' . $this->config['charset'];
        }
        
        // Headers de segurança
        $headers[] = 'X-Mailer: KW24 System';
        $headers[] = 'X-Priority: 3';
        
        return implode("\r\n", $headers);
    }
    
    /**
     * Carrega template de email
     */
    private function loadTemplate(string $templateName, array $variables = []): string {
        $templatePath = $this->templatesPath . $templateName;
        
        if (!file_exists($templatePath)) {
            throw new Exception("Template não encontrado: {$templateName}");
        }
        
        $content = file_get_contents($templatePath);
        
        if ($content === false) {
            throw new Exception("Erro ao ler template: {$templateName}");
        }
        
        // Substituir variáveis
        foreach ($variables as $key => $value) {
            $content = str_replace($key, $value, $content);
        }
        
        return $content;
    }
    
    /**
     * Valida configuração de email
     */
    public function validateConfig(): array {
        $required = [
            'from_email',
            'from_name',
            'charset'
        ];
        
        foreach ($required as $field) {
            if (empty($this->config[$field])) {
                return [
                    'valid' => false,
                    'error' => "Campo obrigatório ausente: {$field}"
                ];
            }
        }
        
        // Validar email
        if (!filter_var($this->config['from_email'], FILTER_VALIDATE_EMAIL)) {
            return [
                'valid' => false,
                'error' => 'Email do remetente inválido'
            ];
        }
        
        return ['valid' => true];
    }
    
    /**
     * Testa conexão/configuração
     */
    public function testConnection(): bool {
        try {
            // Teste simples enviando email para o próprio sistema
            $testEmail = $this->config['from_email'];
            $testSubject = 'Teste de Configuração - ' . date('Y-m-d H:i:s');
            $testBody = 'Este é um email de teste da configuração do sistema KW24.';
            
            return $this->sendEmail($testEmail, $testSubject, $testBody);
            
        } catch (Exception $e) {
            $this->logError('Erro no teste de conexão', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Log de sucesso
     */
    private function logSuccess(string $to, string $subject): void {
        if (!$this->config['log_emails']) return;
        
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => 'SUCCESS',
            'to' => $to,
            'subject' => $subject,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'CLI'
        ];
        
        $this->writeLog($logEntry);
    }
    
    /**
     * Log de erro
     */
    private function logError(string $message, array $context = []): void {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => 'ERROR',
            'message' => $message,
            'context' => $context,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'CLI'
        ];
        
        $this->writeLog($logEntry);
    }
    
    /**
     * Escreve no arquivo de log
     */
    private function writeLog(array $logEntry): void {
        try {
            $logFile = $this->config['log_path'];
            $logDir = dirname($logFile);
            
            // Criar diretório se não existir
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";
            file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
            
        } catch (Exception $e) {
            // Se não conseguir logar, não fazer nada para não quebrar o fluxo
        }
    }
    
    /**
     * Obtém estatísticas de email
     */
    public function getStats(int $days = 7): array {
        try {
            $logFile = $this->config['log_path'];
            
            if (!file_exists($logFile)) {
                return [
                    'sent' => 0,
                    'failed' => 0,
                    'period' => $days
                ];
            }
            
            $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $cutoffDate = date('Y-m-d', strtotime("-{$days} days"));
            
            $sent = 0;
            $failed = 0;
            
            foreach ($lines as $line) {
                $entry = json_decode($line, true);
                if (!$entry || !isset($entry['timestamp'])) continue;
                
                if ($entry['timestamp'] >= $cutoffDate) {
                    if ($entry['status'] === 'SUCCESS') {
                        $sent++;
                    } else {
                        $failed++;
                    }
                }
            }
            
            return [
                'sent' => $sent,
                'failed' => $failed,
                'period' => $days,
                'success_rate' => $sent + $failed > 0 ? round(($sent / ($sent + $failed)) * 100, 2) : 0
            ];
            
        } catch (Exception $e) {
            return [
                'sent' => 0,
                'failed' => 0,
                'period' => $days,
                'error' => $e->getMessage()
            ];
        }
    }
}
?>
