# Sistema Administrativo KW24

Sistema de administração completo para gerenciamento de logs, clientes e aplicações da KW24.

## 🚀 Deploy Automático

### 1. Configuração Inicial no Servidor

```bash
# Clone o repositório
cd /home/kw24co49/
git clone https://github.com/KW24Apps/SistemaKW24.git app.kw24.com.br/Apps

# Entre no diretório
cd app.kw24.com.br/Apps

# Execute o script de configuração
chmod +x setup.sh
./setup.sh
```

### 2. Configuração do Webhook no GitHub

1. Acesse: `https://github.com/KW24Apps/SistemaKW24/settings/hooks`
2. Clique em **"Add webhook"**
3. Configure:
   - **Payload URL**: `https://app.kw24.com.br/deploy.php`
   - **Content type**: `application/json`
   - **Secret**: `hF9kL2xV7qP3sY8mZ4bW1cN0`
   - **Events**: Selecione **"Just the push event"**
   - **Active**: ✅ Marcado

### 3. Teste o Deploy

```bash
# Faça uma alteração qualquer e commit
git add .
git commit -m "Teste de deploy automático"
git push origin main

# Verifique os logs
tail -f /home/kw24co49/app.kw24.com.br/deploy.log
```

## 📁 Estrutura do Projeto

```
Apps/
├── .htaccess              # Configurações Apache
├── deploy.php             # Script de deploy automático
├── setup.sh              # Script de configuração inicial
├── config/
│   ├── log_domains.php    # Configuração de domínios
│   └── local_config.php   # Configurações locais (não versionado)
├── controllers/
│   └── LogController.php  # Controlador de logs
├── includes/
│   └── helpers.php        # Funções auxiliares
├── public/
│   ├── index.php          # Dashboard principal
│   ├── login.php          # Página de login
│   └── logout.php         # Logout
├── views/
│   └── layouts/
│       └── main.php       # Layout principal
├── assets/
│   ├── css/               # Arquivos CSS
│   └── js/                # Arquivos JavaScript
└── logs/                  # Logs locais (limpos no deploy)
```

## 🔧 Funcionalidades

### ✅ Implementadas
- **Sistema de Login**: Autenticação segura com sessões
- **Dashboard Responsivo**: Interface moderna baseada no Bitrix24
- **Visualizador de Logs**: Multi-domínio com filtros avançados
- **Deploy Automático**: Webhook integrado com GitHub
- **Estrutura MVC**: Organização profissional do código

### 🚧 Em Desenvolvimento
- **Gerenciamento de Clientes**: CRUD completo de clientes
- **Controle de Aplicações**: Monitoramento de aplicações
- **Relatórios**: Dashboards e estatísticas
- **API Management**: Interface para gerenciar APIs

## ⚙️ Configurações

### Domínios de Logs
Edite `config/log_domains.php` para adicionar novos domínios:

```php
return [
    'apis.kw24.com.br' => [
        'path' => '/home/kw24co49/apis.kw24.com.br/Apps/logs',
        'description' => 'APIs KW24'
    ],
    'app.kw24.com.br' => [
        'path' => '/home/kw24co49/app.kw24.com.br/Apps/logs',
        'description' => 'Sistema Administrativo'
    ]
];
```

### Credenciais de Login
Padrão atual (altere em `includes/helpers.php`):
- **Usuário**: `admin`
- **Senha**: `admin123`

## 🔒 Segurança

- ✅ Webhook com verificação de assinatura
- ✅ Proteção de arquivos de configuração
- ✅ Sessões com timeout
- ✅ Limpeza automática de logs locais
- ✅ Backup de configurações durante deploy

## 📊 Monitoramento

### Logs de Deploy
```bash
# Ver logs em tempo real
tail -f /home/kw24co49/app.kw24.com.br/deploy.log

# Ver últimas 50 linhas
tail -50 /home/kw24co49/app.kw24.com.br/deploy.log
```

### Status do Sistema
Acesse: `https://app.kw24.com.br/` para verificar se o sistema está funcionando.

## 🔄 Workflow de Desenvolvimento

1. **Desenvolvimento Local**: Faça alterações na pasta `x:\VSCode\app.kw24.com.br\Apps`
2. **Commit e Push**: 
   ```bash
   git add .
   git commit -m "Descrição da alteração"
   git push origin main
   ```
3. **Deploy Automático**: O webhook faz o deploy automaticamente
4. **Verificação**: Verifique os logs e teste no servidor

## 🆘 Troubleshooting

### Deploy não funcionou?
1. Verifique os logs: `tail -f /home/kw24co49/app.kw24.com.br/deploy.log`
2. Teste o webhook manualmente: acesse `https://app.kw24.com.br/deploy.php`
3. Verifique as permissões: `ls -la /home/kw24co49/app.kw24.com.br/Apps/`

### Erro 500?
1. Verifique os logs do Apache
2. Confirme se o `.htaccess` está correto
3. Verifique se todos os diretórios existem

---

**Desenvolvido por KW24** 🚀
