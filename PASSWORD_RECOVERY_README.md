# Sistema de Recuperação de Senha - KW24

Sistema completo de recuperação de senha via email com códigos de 6 dígitos, integração SMTP Hostgator e templates responsivos.

## 📁 Estrutura de Arquivos

```
/Apps/
├── services/
│   ├── PasswordRecoveryService.php  # Lógica principal
│   └── EmailService.php            # Envio de emails SMTP
├── controllers/
│   └── PasswordRecoveryController.php  # API endpoints
├── templates/email/
│   ├── password-recovery.html      # Template HTML responsivo
│   └── password-recovery.txt       # Template texto (acessibilidade)
├── config/
│   └── email_config.php           # Configurações SMTP
└── routers/
    └── passwordRecoveryRoutes.php  # Roteamento da API
```

## ⚡ Funcionalidades

- ✅ **Códigos de 6 dígitos** com expiração de 15 minutos
- ✅ **Rate limiting** (3 tentativas/hora, 10/dia)
- ✅ **Email mascarado** para feedback seguro
- ✅ **Templates responsivos** HTML + texto
- ✅ **Integração Hostgator SMTP**
- ✅ **Logs completos** de segurança
- ✅ **Validação robusta** de entrada
- ✅ **API RESTful** documentada

## 🔧 Instalação

### 1. Configurar Email no Hostgator

```bash
# 1. Acesse cPanel do Hostgator
# 2. Vá em "Contas de Email"
# 3. Crie: noreply@seudominio.com.br
# 4. Defina senha forte
```

### 2. Configurar Credenciais

Edite `/Apps/config/email_config.php`:

```php
return [
    'smtp_host' => 'mail.seudominio.com.br',     // Seu domínio
    'smtp_username' => 'noreply@seudominio.com.br',
    'smtp_password' => 'SUA_SENHA_AQUI',         // Senha do email
    'from_email' => 'noreply@seudominio.com.br',
    'reply_to' => 'suporte@seudominio.com.br'
];
```

### 3. Instalar PHPMailer (se necessário)

```bash
# Via Composer (recomendado)
composer require phpmailer/phpmailer

# Ou baixar manualmente de:
# https://github.com/PHPMailer/PHPMailer
```

### 4. Configurar Banco de Dados

O sistema cria automaticamente a tabela `password_recovery`:

```sql
-- Tabela criada automaticamente pelo sistema
CREATE TABLE password_recovery (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_identifier VARCHAR(255) NOT NULL,
    recovery_code VARCHAR(6) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    attempts INT DEFAULT 0,
    ip_address VARCHAR(45) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE
);
```

## 🚀 Uso da API

### Base URL
```
https://seudominio.com.br/Apps/routers/passwordRecoveryRoutes.php
```

### 1. Iniciar Recuperação

```javascript
// POST /initiate
const response = await fetch('/Apps/routers/passwordRecoveryRoutes.php/initiate', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        identifier: 'usuario@email.com'  // ou telefone
    })
});

const result = await response.json();
// Response: { success: true, message: "Código enviado para u***@email.com", data: { masked_email: "u***@email.com" } }
```

### 2. Validar Código

```javascript
// POST /validate-code
const response = await fetch('/Apps/routers/passwordRecoveryRoutes.php/validate-code', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        identifier: 'usuario@email.com',
        code: '123456'
    })
});

const result = await response.json();
// Response: { success: true, message: "Código válido", data: { recovery_id: 123 } }
```

### 3. Redefinir Senha

```javascript
// POST /reset-password
const response = await fetch('/Apps/routers/passwordRecoveryRoutes.php/reset-password', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
        identifier: 'usuario@email.com',
        newPassword: 'minhaNovaSenh@123',
        confirmPassword: 'minhaNovaSenh@123',
        recoveryId: 123  // do passo anterior
    })
});

const result = await response.json();
// Response: { success: true, message: "Senha redefinida com sucesso" }
```

### 4. Verificar Status

```javascript
// GET /status
const response = await fetch('/Apps/routers/passwordRecoveryRoutes.php/status');
const result = await response.json();
// Response: { success: true, data: { service_active: true, email_service: true, database: true } }
```

## 🔒 Segurança

### Rate Limiting
- **3 tentativas por hora** por usuário
- **10 tentativas por dia** por usuário
- Bloqueio automático em caso de abuso

### Validações
- Códigos expiram em **15 minutos**
- Códigos de **6 dígitos** únicos
- **Uso único** por código
- Validação de **formato de email/telefone**

### Logs
- Todas as tentativas são logadas
- IP e User-Agent registrados
- Logs de email enviados
- Monitoramento de erros

## 🧪 Testes

### Teste de Email
```bash
# Acesse (apenas em desenvolvimento):
GET /Apps/routers/passwordRecoveryRoutes.php/test-email
```

### Teste Manual
```php
// Em desenvolvimento, adicione:
define('DEBUG_MODE', true);

// Teste rápido:
$emailService = new EmailService();
$result = $emailService->testConnection();
var_dump($result);
```

## 📊 Monitoramento

### Logs Disponíveis
```bash
/Apps/logs/
├── email_sent.log              # Emails enviados
├── password_recovery_access.log # Acessos à API
└── password_recovery_errors.log # Erros do sistema
```

### Limpeza Automática
```php
// Rodar diariamente via cron:
$service = new PasswordRecoveryService();
$service->cleanExpiredCodes();
```

## 🎨 Personalização

### Templates de Email
- **HTML**: `/Apps/templates/email/password-recovery.html`
- **Texto**: `/Apps/templates/email/password-recovery.txt`

Variáveis disponíveis:
- `{{NOME}}` - Nome do usuário
- `{{CODIGO}}` - Código de recuperação
- `{{EXPIRY_MINUTES}}` - Minutos para expirar
- `{{CURRENT_YEAR}}` - Ano atual
- `{{CURRENT_TIMESTAMP}}` - Data/hora atual

### Configurações
```php
// Em PasswordRecoveryService.php:
private const CODE_LENGTH = 6;              // Tamanho do código
private const CODE_EXPIRY_MINUTES = 15;     // Expiração em minutos
private const MAX_ATTEMPTS_PER_HOUR = 3;    // Limite por hora
private const MAX_ATTEMPTS_PER_DAY = 10;    // Limite por dia
```

## 🔧 Solução de Problemas

### Email não está sendo enviado
1. Verifique as credenciais em `email_config.php`
2. Teste com `/test-email`
3. Verifique se o domínio está propagado
4. Confirme que a conta de email foi criada no cPanel

### Códigos não funcionam
1. Verifique se não expiraram (15 min)
2. Confirme que o banco de dados está acessível
3. Verifique os logs de erro

### Rate limiting muito restritivo
```php
// Ajuste em PasswordRecoveryService.php:
private const MAX_ATTEMPTS_PER_HOUR = 5;  // Aumentar se necessário
```

## 📝 Changelog

### v1.0.0
- ✅ Sistema completo de recuperação
- ✅ Templates responsivos
- ✅ Integração Hostgator SMTP
- ✅ API RESTful documentada
- ✅ Logs de segurança
- ✅ Rate limiting
- ✅ Validações robustas

## 📞 Suporte

Para dúvidas ou problemas:
- Email: suporte@kw24.com.br
- Logs: Verifique `/Apps/logs/`
- Debug: Ative `DEBUG_MODE` em desenvolvimento
