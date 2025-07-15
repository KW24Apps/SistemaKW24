#!/bin/bash

# Script de configuração inicial para o Sistema Administrativo KW24
# Execute este script no servidor após o primeiro clone

echo "=== Configuração Inicial - Sistema Administrativo KW24 ==="

# Diretório do projeto
PROJECT_DIR="/home/kw24co49/app.kw24.com.br/Apps"

# Verifica se está no diretório correto
if [ ! -f "deploy.php" ]; then
    echo "ERRO: Execute este script no diretório do projeto!"
    exit 1
fi

echo "📁 Configurando permissões..."

# Define permissões corretas
chmod -R 755 .
chmod -R 644 config/*.php
chmod 644 .htaccess
chmod 755 deploy.php

# Cria diretórios necessários
echo "📂 Criando diretórios necessários..."

mkdir -p logs
mkdir -p assets/css
mkdir -p assets/js
mkdir -p sessions
mkdir -p uploads

# Define permissões para diretórios de escrita
chmod 777 logs
chmod 777 sessions
chmod 777 uploads

echo "🔐 Configurando segurança..."

# Protege diretórios sensíveis
echo "deny from all" > config/.htaccess
echo "deny from all" > includes/.htaccess
echo "deny from all" > sessions/.htaccess

# Cria arquivo de configuração local (se não existir)
if [ ! -f "config/local_config.php" ]; then
    echo "⚙️ Criando arquivo de configuração local..."
    cat > config/local_config.php << 'EOF'
<?php
/**
 * Configurações locais do servidor
 * Este arquivo não é versionado e contém configurações específicas do ambiente
 */

// Configurações de ambiente
define('ENVIRONMENT', 'production'); // development, staging, production

// Configurações de segurança
define('SESSION_TIMEOUT', 3600); // 1 hora em segundos

// Configurações de log
define('LOG_LEVEL', 'INFO'); // DEBUG, INFO, WARNING, ERROR

// Configurações específicas do servidor
define('SERVER_TIMEZONE', 'America/Sao_Paulo');

// Definir timezone
date_default_timezone_set(SERVER_TIMEZONE);
?>
EOF
    chmod 644 config/local_config.php
    echo "✅ Arquivo config/local_config.php criado"
fi

# Cria arquivo de log de deploy
touch /home/kw24co49/app.kw24.com.br/deploy.log
chmod 666 /home/kw24co49/app.kw24.com.br/deploy.log

# Testa configurações do Apache
echo "🔍 Testando configurações..."

if [ -f ".htaccess" ]; then
    echo "✅ Arquivo .htaccess encontrado"
else
    echo "❌ ERRO: Arquivo .htaccess não encontrado!"
fi

# Verifica módulos Apache necessários
echo "📋 Verificando módulos Apache necessários:"
echo "   - mod_rewrite (necessário para URLs amigáveis)"
echo "   - mod_expires (necessário para cache)"

# Mostra informações importantes
echo ""
echo "🎯 CONFIGURAÇÃO CONCLUÍDA!"
echo ""
echo "📝 PRÓXIMOS PASSOS:"
echo "1. Configure o webhook no GitHub:"
echo "   URL: https://app.kw24.com.br/deploy.php"
echo "   Secret: hF9kL2xV7qP3sY8mZ4bW1cN0"
echo "   Events: Push events"
echo ""
echo "2. Teste o sistema acessando:"
echo "   https://app.kw24.com.br/"
echo ""
echo "3. Faça login com as credenciais configuradas"
echo ""
echo "4. Verifique os logs em:"
echo "   /home/kw24co49/app.kw24.com.br/deploy.log"
echo ""
echo "🔐 SEGURANÇA:"
echo "- Logs locais são automaticamente limpos no deploy"
echo "- Arquivos de configuração são protegidos"
echo "- Sessões têm timeout configurado"
echo ""
