<?php

/**
 * EMAIL SERVICE - SISTEMA KW24
 * Gerencia envio de emails via Hostgator SMTP
 * Integração com templates e configurações de segurança
 */

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    
    private $mailer;
    private $config;
    
    // Configurações padrão SMTP Hostgator
    private const SMTP_HOST = 'mail.kw24.com.br'; // Ajustar conforme seu domínio
    private const SMTP_PORT = 587;
    private const SMTP_SECURE = 'tls';
    
    public function __construct() {
        $this->loadConfig();
        $this->initializeMailer();
    }
    
    /**
     * Carrega configurações do email
     */
    private function loadConfig() {
        // Carregar do config.php ou definir aqui
        $this->config = [
            'smtp_host' => self::SMTP_HOST,
            'smtp_port' => self::SMTP_PORT,
            'smtp_secure' => self::SMTP_SECURE,
            'smtp_username' => 'noreply@kw24.com.br', // Configurar no Hostgator
            'smtp_password' => 'SUA_SENHA_EMAIL',      // Configurar no config.php
            'from_email' => 'noreply@kw24.com.br',
            'from_name' => 'Sistema KW24',
            'reply_to' => 'suporte@kw24.com.br'
        ];
        
        // Sobrescrever com configurações do arquivo se existir
        if (file_exists(__DIR__ . '/../config/email_config.php')) {
            $emailConfig = include __DIR__ . '/../config/email_config.php';
            $this->config = array_merge($this->config, $emailConfig);
        }
    }
    
    /**
     * Inicializa PHPMailer
     */
    private function initializeMailer() {
        $this->mailer = new PHPMailer(true);
        
        try {
            // Configurações do servidor
            $this->mailer->isSMTP();
            $this->mailer->Host = $this->config['smtp_host'];
            $this->mailer->SMTPAuth = true;
            $this->mailer->Username = $this->config['smtp_username'];
            $this->mailer->Password = $this->config['smtp_password'];
            $this->mailer->SMTPSecure = $this->config['smtp_secure'];
            $this->mailer->Port = $this->config['smtp_port'];
            
            // Configurações gerais
            $this->mailer->setFrom($this->config['from_email'], $this->config['from_name']);
            $this->mailer->addReplyTo($this->config['reply_to'], 'Suporte KW24');
            $this->mailer->isHTML(true);
            $this->mailer->CharSet = 'UTF-8';
            
            // Configurações de debug (desativar em produção)
            if (defined('EMAIL_DEBUG') && EMAIL_DEBUG) {
                $this->mailer->SMTPDebug = SMTP::DEBUG_SERVER;
                $this->mailer->Debugoutput = 'html';
            }
            
        } catch (Exception $e) {
            error_log("EmailService Init Error: " . $e->getMessage());
            throw new Exception("Erro ao configurar email: " . $e->getMessage());
        }
    }
    
    /**
     * Envia código de recuperação de senha
     * @param string $email Email do destinatário
     * @param string $nome Nome do usuário
     * @param string $code Código de recuperação
     * @param int $expiryMinutes Minutos para expirar
     * @return bool Success
     */
    public function sendRecoveryCode($email, $nome, $code, $expiryMinutes = 15) {
        try {
            // Limpar destinatários anteriores
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $nome);
            
            // Configurar assunto
            $this->mailer->Subject = 'Código de Recuperação - Sistema KW24';
            
            // Gerar conteúdo do email
            $htmlContent = $this->getRecoveryEmailTemplate($nome, $code, $expiryMinutes);
            $textContent = $this->getRecoveryEmailText($nome, $code, $expiryMinutes);
            
            // Configurar conteúdo
            $this->mailer->Body = $htmlContent;
            $this->mailer->AltBody = $textContent;
            
            // Enviar
            $sent = $this->mailer->send();
            
            if ($sent) {
                $this->logEmailSent($email, 'password_recovery', $code);
            }
            
            return $sent;
            
        } catch (Exception $e) {
            error_log("EmailService Recovery Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Envia confirmação de senha alterada
     * @param string $email Email do usuário
     * @param string $nome Nome do usuário
     * @return bool Success
     */
    public function sendPasswordChangeConfirmation($email, $nome) {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($email, $nome);
            
            $this->mailer->Subject = 'Senha Alterada - Sistema KW24';
            
            $htmlContent = $this->getPasswordChangeTemplate($nome);
            $textContent = $this->getPasswordChangeText($nome);
            
            $this->mailer->Body = $htmlContent;
            $this->mailer->AltBody = $textContent;
            
            $sent = $this->mailer->send();
            
            if ($sent) {
                $this->logEmailSent($email, 'password_changed');
            }
            
            return $sent;
            
        } catch (Exception $e) {
            error_log("EmailService Password Change Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Template HTML para recuperação de senha
     */
    private function getRecoveryEmailTemplate($nome, $code, $expiryMinutes) {
        $templatePath = __DIR__ . '/../templates/email/password-recovery.html';
        
        if (file_exists($templatePath)) {
            $template = file_get_contents($templatePath);
            
            // Substituir variáveis
            $template = str_replace([
                '{{NOME}}',
                '{{CODIGO}}',
                '{{EXPIRY_MINUTES}}',
                '{{CURRENT_YEAR}}',
                '{{CURRENT_TIMESTAMP}}'
            ], [
                htmlspecialchars($nome),
                $code,
                $expiryMinutes,
                date('Y'),
                date('d/m/Y H:i:s')
            ], $template);
            
            return $template;
        }
        
        // Fallback se template não existir
        return $this->getDefaultRecoveryTemplate($nome, $code, $expiryMinutes);
    }
    
    /**
     * Template texto para recuperação de senha
     */
    private function getRecoveryEmailText($nome, $code, $expiryMinutes) {
        $templatePath = __DIR__ . '/../templates/email/password-recovery.txt';
        
        if (file_exists($templatePath)) {
            $template = file_get_contents($templatePath);
            
            // Substituir variáveis
            $template = str_replace([
                '{{NOME}}',
                '{{CODIGO}}',
                '{{EXPIRY_MINUTES}}',
                '{{CURRENT_YEAR}}'
            ], [
                $nome,
                $code,
                $expiryMinutes,
                date('Y')
            ], $template);
            
            return $template;
        }
        
        // Fallback se template não existir
        return "
Olá {$nome},

Você solicitou a recuperação de senha do Sistema KW24.

Seu código de recuperação é: {$code}

Este código expira em {$expiryMinutes} minutos.

Se você não solicitou esta recuperação, ignore este email.

---
Sistema KW24
suporte@kw24.com.br
        ";
    }
    
    /**
     * Template padrão se arquivo não existir
     */
    private function getDefaultRecoveryTemplate($nome, $code, $expiryMinutes) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Recuperação de Senha - KW24</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background-color: white; }
                .header { background: linear-gradient(135deg, #033140, #086B8D); padding: 30px; text-align: center; }
                .logo { color: white; font-size: 24px; font-weight: bold; }
                .content { padding: 40px 30px; }
                .code-box { background: #f8f9fa; border: 2px solid #086B8D; border-radius: 12px; padding: 30px; text-align: center; margin: 30px 0; }
                .code { font-size: 36px; font-weight: bold; color: #033140; letter-spacing: 8px; font-family: monospace; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>KW24</div>
                </div>
                <div class='content'>
                    <h2>Olá, {$nome}!</h2>
                    <p>Você solicitou a recuperação de senha do Sistema KW24.</p>
                    <p>Use o código abaixo para redefinir sua senha:</p>
                    
                    <div class='code-box'>
                        <div class='code'>{$code}</div>
                    </div>
                    
                    <p><strong>⏰ Este código expira em {$expiryMinutes} minutos.</strong></p>
                    <p>Se você não solicitou esta recuperação, ignore este email.</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " Sistema KW24. Todos os direitos reservados.</p>
                    <p>Para suporte: suporte@kw24.com.br</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Template para confirmação de senha alterada
     */
    private function getPasswordChangeTemplate($nome) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>Senha Alterada - KW24</title>
            <style>
                body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 0; padding: 0; background-color: #f4f4f4; }
                .container { max-width: 600px; margin: 0 auto; background-color: white; }
                .header { background: linear-gradient(135deg, #033140, #086B8D); padding: 30px; text-align: center; }
                .logo { color: white; font-size: 24px; font-weight: bold; }
                .content { padding: 40px 30px; }
                .success-box { background: #d4edda; border: 2px solid #28a745; border-radius: 12px; padding: 20px; margin: 20px 0; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <div class='logo'>KW24</div>
                </div>
                <div class='content'>
                    <h2>Olá, {$nome}!</h2>
                    <div class='success-box'>
                        <p><strong>✅ Sua senha foi alterada com sucesso!</strong></p>
                    </div>
                    <p>Sua senha do Sistema KW24 foi redefinida em " . date('d/m/Y H:i') . ".</p>
                    <p>Se você não fez esta alteração, entre em contato conosco imediatamente.</p>
                </div>
                <div class='footer'>
                    <p>© " . date('Y') . " Sistema KW24. Todos os direitos reservados.</p>
                    <p>Para suporte: suporte@kw24.com.br</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    /**
     * Template texto para confirmação
     */
    private function getPasswordChangeText($nome) {
        return "
Olá {$nome},

Sua senha do Sistema KW24 foi alterada com sucesso em " . date('d/m/Y H:i') . ".

Se você não fez esta alteração, entre em contato conosco imediatamente.

---
Sistema KW24
suporte@kw24.com.br
        ";
    }
    
    /**
     * Log de emails enviados
     */
    private function logEmailSent($email, $type, $reference = null) {
        try {
            $logData = [
                'email' => $email,
                'type' => $type,
                'reference' => $reference,
                'timestamp' => date('Y-m-d H:i:s'),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1'
            ];
            
            $logFile = __DIR__ . '/../logs/email_sent.log';
            $logEntry = date('Y-m-d H:i:s') . " - " . json_encode($logData) . "\n";
            
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
            
        } catch (Exception $e) {
            error_log("EmailService Log Error: " . $e->getMessage());
        }
    }
    
    /**
     * Testa configuração de email
     */
    public function testConnection() {
        try {
            $this->mailer->addAddress($this->config['from_email']);
            $this->mailer->Subject = 'Teste de Configuração - Sistema KW24';
            $this->mailer->Body = 'Este é um teste de configuração do sistema de email.';
            
            return $this->mailer->send();
            
        } catch (Exception $e) {
            error_log("EmailService Test Error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Valida configuração de email
     */
    public function validateConfig() {
        $required = ['smtp_host', 'smtp_username', 'smtp_password', 'from_email'];
        
        foreach ($required as $field) {
            if (empty($this->config[$field])) {
                return [
                    'valid' => false,
                    'error' => "Campo obrigatório faltando: {$field}"
                ];
            }
        }
        
        return ['valid' => true];
    }
}
