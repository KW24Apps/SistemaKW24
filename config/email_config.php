<?php

/**
 * CONFIGURAÇÃO DE EMAIL - SISTEMA KW24
 * Configurações SMTP para Hostgator
 * 
 * INSTRUÇÕES DE CONFIGURAÇÃO:
 * 1. Acesse o cPanel do Hostgator
 * 2. Vá em "Contas de Email"
 * 3. Crie a conta: noreply@seudominio.com.br
 * 4. Configure as credenciais abaixo
 * 5. Teste a configuração usando EmailService::testConnection()
 */

return [
    // Configurações SMTP KW24 - CONFIGURADO ✅
    'smtp_host' => 'mail.kw24.com.br',  // Servidor SMTP KW24
    'smtp_port' => 587,                  // Porta TLS padrão
    'smtp_secure' => 'tls',              // TLS é recomendado
    'smtp_username' => 'noreply@kw24.com.br',  // Email KW24
    'smtp_password' => ';g)O0&k]),@)',          // Senha configurada
    
    // Configurações do remetente
    'from_email' => 'noreply@kw24.com.br',
    'from_name' => 'Sistema KW24',
    'reply_to' => 'suporte@kw24.com.br',
    
    // Configurações de segurança
    'smtp_auth' => true,
    'smtp_auto_tls' => true,
    
    // Debug (apenas desenvolvimento)
    'debug_mode' => false,  // Mudar para true apenas para debug
    
    // Configurações adicionais
    'charset' => 'UTF-8',
    'timeout' => 30,
    
    // Templates
    'templates_path' => __DIR__ . '/../templates/email/',
    
    // Logs
    'log_emails' => true,
    'log_path' => __DIR__ . '/../logs/email_sent.log'
];

/*
CONFIGURAÇÃO MANUAL HOSTGATOR:

1. CRIAR CONTA DE EMAIL:
   - Login no cPanel
   - Ir em "Contas de Email"
   - Criar: noreply@seudominio.com.br
   - Definir senha forte

2. CONFIGURAÇÕES SMTP:
   - Servidor: mail.seudominio.com.br
   - Porta: 587 (TLS) ou 465 (SSL)
   - Autenticação: Sim
   - Usuário: noreply@seudominio.com.br
   - Senha: A que você definiu

3. TESTE:
   - Use EmailService::testConnection()
   - Verifique os logs de erro

4. SOLUÇÃO DE PROBLEMAS:
   - Se não funcionar, tente porta 465 com SSL
   - Verifique se o domínio está propagado
   - Confirme que o email foi criado corretamente
   - Entre em contato com suporte Hostgator se necessário

5. PRODUÇÃO:
   - Sempre use HTTPS
   - Configure SPF/DKIM no DNS
   - Monitore logs de email
   - Defina debug_mode como false
*/
