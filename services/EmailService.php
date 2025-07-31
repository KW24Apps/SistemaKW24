<?php

/**
 * EMAIL SERVICE - SISTEMA KW24
 * Serviço para envio de emails via SMTP nativo
 * Versão simplificada sem dependências externas
 */

class EmailService {
    
    private $config;
    private $templatesPath;
    
    public function __construct() {
        $this->config = require __DIR__ . '/../config/email_config.php';
        $this->templatesPath = __DIR__ . '/../templates/email/';
    }
    
    /**
     * Envia email de recuperação de senha
     */
    public function sendPasswordRecoveryEmail($recipientEmail, $recipientName, $recoveryCode) {
        try {
            // Carregar templates
            $htmlTemplate = $this->loadTemplate('password-recovery.html');
            $txtTemplate = $this->loadTemplate('password-recovery.txt');
            
            // Substituir variáveis
            $htmlContent = $this->replaceTemplateVariables($htmlTemplate, [
                'USER_NAME' => $recipientName,
                'RECOVERY_CODE' => $recoveryCode
            ]);
            
            $txtContent = $this->replaceTemplateVariables($txtTemplate, [
                'USER_NAME' => $recipientName, 
                'RECOVERY_CODE' => $recoveryCode
            ]);
            
            // Configurar headers
            $subject = 'Código de Recuperação de Senha - KW24';
            $headers = $this->buildHeaders($htmlContent, $txtContent);
            
            // Enviar email
            $sent = mail($recipientEmail, $subject, $htmlContent, $headers);
            
            if ($sent) {
                $this->logEmailSent($recipientEmail, 'password_recovery', true);
                return [
                    'success' => true,
                    'message' => 'Email enviado com sucesso'
                ];
            } else {
                $this->logEmailSent($recipientEmail, 'password_recovery', false, 'Falha no envio');
                return [
                    'success' => false,
                    'error' => 'Falha no envio do email'
                ];
            }
            
        } catch (Exception $e) {
            $this->logEmailSent($recipientEmail, 'password_recovery', false, $e->getMessage());
            return [
                'success' => false,
                'error' => 'Erro interno: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Valida configuração de email
     */
    public function validateConfig() {
        $required = ['smtp_host', 'smtp_username', 'smtp_password', 'from_email'];
        $missing = [];
        
        foreach ($required as $key) {
            if (empty($this->config[$key])) {
                $missing[] = $key;
            }
        }
        
        return [
            'valid' => empty($missing),
            'missing_config' => $missing,
            'smtp_host' => $this->config['smtp_host'] ?? 'Não configurado'
        ];
    }
    
    /**
     * Carrega template de email
     */
    private function loadTemplate($templateName) {
        $templatePath = $this->templatesPath . $templateName;
        
        if (!file_exists($templatePath)) {
            throw new Exception("Template não encontrado: {$templateName}");
        }
        
        return file_get_contents($templatePath);
    }
    
    /**
     * Substitui variáveis no template
     */
    private function replaceTemplateVariables($template, $variables) {
        foreach ($variables as $key => $value) {
            $template = str_replace("{{" . $key . "}}", $value, $template);
        }
        return $template;
    }
    
    /**
     * Constrói headers do email
     */
    private function buildHeaders($htmlContent, $txtContent) {
        $boundary = uniqid('boundary_');
        $fromEmail = $this->config['from_email'];
        $fromName = $this->config['from_name'];
        
        $headers = [
            "From: {$fromName} <{$fromEmail}>",
            "Reply-To: {$fromEmail}",
            "MIME-Version: 1.0",
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
            "X-Mailer: KW24 System",
            "X-Priority: 1"
        ];
        
        return implode("\r\n", $headers);
    }
    
    /**
     * Log de emails enviados
     */
    private function logEmailSent($recipient, $type, $success, $error = null) {
        try {
            $logFile = $this->config['log_path'] ?? __DIR__ . '/../logs/email_sent.log';
            
            $logData = [
                'timestamp' => date('Y-m-d H:i:s'),
                'recipient' => $recipient,
                'type' => $type,
                'success' => $success,
                'error' => $error
            ];
            
            $logEntry = json_encode($logData) . "\n";
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
            
        } catch (Exception $e) {
            error_log("EmailService::logEmailSent Error: " . $e->getMessage());
        }
    }
    
    /**
     * Teste de conexão SMTP (simplificado)
     */
    public function testConnection() {
        try {
            $config = $this->validateConfig();
            
            if (!$config['valid']) {
                return [
                    'success' => false,
                    'error' => 'Configuração incompleta',
                    'missing' => $config['missing_config']
                ];
            }
            
            // Para teste, verificar se função mail() está disponível
            if (!function_exists('mail')) {
                return [
                    'success' => false,
                    'error' => 'Função mail() não está disponível no servidor'
                ];
            }
            
            return [
                'success' => true,
                'message' => 'Configuração de email válida',
                'smtp_host' => $config['smtp_host']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
}
