# Sistema Administrativo KW24

Sistema de administração completo para gerenciamento de clientes, contatos, logs e aplicações da KW24.

## 🎯 Visão Geral

Este é um sistema web moderno desenvolvido em PHP com interface responsiva, que oferece:
- **Gestão de Clientes e Contatos**: CRUD completo com modais dinâmicos
- **Visualizador de Logs**: Interface intuitiva para análise de logs
- **Dashboard Administrativo**: Painel de controle centralizado
- **Sistema Universal de Cadastros**: Componentes reutilizáveis para diferentes módulos

## 🚀 Instalação e Configuração

### Pré-requisitos
- PHP 7.4 ou superior
- MySQL 5.7 ou superior
- Servidor web (Apache/Nginx)
- Extensões PHP: PDO, PDO_MySQL, session

### Instalação Local

1. **Clone o repositório**:
   ```bash
   git clone https://github.com/KW24Apps/Apps.git
   cd Apps
   ```

2. **Configure o banco de dados**:
   - Crie um banco MySQL
   - Configure as credenciais em `config/config.php`

3. **Configure o servidor web**:
   - Aponte o document root para a pasta `public/`
   - Ou acesse via `http://localhost/caminho-do-projeto/public/`

4. **Primeiro acesso**:
   - Acesse a aplicação no navegador
   - Use as credenciais padrão: `admin` / `admin123`

## 📁 Estrutura do Projeto

```
Apps/
├── .gitignore             # Arquivos ignorados pelo Git
├── README.md              # Documentação do projeto
├── config/
│   └── config.php         # Configurações gerais do sistema
├── dao/
│   └── DAO.php           # Classe de acesso a dados
├── helpers/
│   └── Database.php      # Helper de conexão com banco
├── includes/
│   └── helpers.php       # Funções auxiliares globais
├── models/               # Modelos de dados (vazio atualmente)
├── controllers/          # Controladores MVC (vazio atualmente)
├── public/               # Arquivos públicos e páginas
│   ├── index.php         # Página inicial (redirecionamento)
│   ├── login.php         # Página de login
│   ├── logout.php        # Script de logout
│   ├── dashboard.php     # Dashboard principal
│   ├── cadastro.php      # Sistema de cadastros (clientes/contatos)
│   ├── logs.php          # Visualizador de logs
│   ├── relatorio.php     # Página de relatórios
│   ├── cliente_create.php # Criação de clientes
│   ├── cliente_save.php  # Salvamento de clientes
│   ├── clientes_search.php # Busca de clientes
│   ├── contato_create.php # Criação de contatos
│   ├── contato_update.php # Atualização de contatos
│   ├── contatos_search.php # Busca de contatos
│   └── ajax/             # Endpoints AJAX
│       ├── ajax-content.php    # Conteúdo AJAX genérico
│       ├── cadastro-content.php # AJAX para sistema de cadastros
│       └── logs-content.php    # AJAX para sistema de logs
├── views/
│   └── layouts/          # Templates de layout
│       ├── main.php      # Layout principal do sistema
│       ├── sidebar.php   # Barra lateral de navegação
│       ├── topbar.php    # Barra superior
│       └── area-atuacao.php # Seletor de área de atuação
└── assets/               # Recursos estáticos
    ├── css/              # Folhas de estilo
    │   ├── main.css      # Estilos principais
    │   ├── main-improved.css # Estilos principais melhorados
    │   ├── sidebar.css   # Estilos da barra lateral
    │   ├── sidebar-improved.css # Estilos melhorados da sidebar
    │   ├── topbar.css    # Estilos da barra superior
    │   ├── topbar-improved.css # Estilos melhorados da topbar
    │   ├── login.css     # Estilos da página de login
    │   ├── dashboard.css # Estilos do dashboard
    │   ├── cadastro.css  # Estilos do sistema de cadastros
    │   ├── logs.css      # Estilos do visualizador de logs
    │   ├── area-atuacao.css # Estilos do seletor de área
    │   └── loading-skeleton.css # Animações de carregamento
    ├── js/               # Scripts JavaScript
    │   ├── ajax-utils.js # Utilitários AJAX
    │   ├── ajax-improved.js # AJAX melhorado
    │   ├── login.js      # Lógica da página de login
    │   ├── dashboard.js  # Lógica do dashboard
    │   ├── sidebar.js    # Lógica da barra lateral
    │   ├── sidebar-improved.js # Sidebar melhorada
    │   ├── topbar.js     # Lógica da barra superior
    │   ├── topbar-improved.js # Topbar melhorada
    │   ├── cadastro.js   # Sistema de cadastros (legado)
    │   ├── cadastro-universal.js # Sistema universal de cadastros
    │   └── logs.js       # Visualizador de logs
    └── img/              # Imagens
        ├── 03_KW24_BRANCO1.png # Logo principal
        ├── 03_KW24_BRANCO1OLD.png # Logo antiga
        ├── Logo_Menu.png # Logo do menu
        └── Fundo_Login.webp # Fundo da página de login
```

## 🔧 Funcionalidades

### ✅ Implementadas
- **Sistema de Login**: Autenticação segura com sessões
- **Dashboard Responsivo**: Interface moderna baseada no Bitrix24
- **Sistema de Cadastros Universal**: CRUD completo para clientes e contatos
  - Modal de confirmação customizado
  - Detecção automática de alterações
  - Sistema de alertas universais
  - Validação de formulários
- **Visualizador de Logs**: Multi-domínio com filtros avançados
- **Navegação Dinâmica**: Sidebar e topbar responsivas
- **Sistema AJAX**: Carregamento dinâmico de conteúdo
- **Estrutura MVC Parcial**: Organização profissional do código

### 🚧 Em Desenvolvimento
- **Relatórios**: Dashboards e estatísticas avançadas
- **API Management**: Interface para gerenciar APIs externas
- **Sistema de Permissões**: Controle de acesso por usuário
- **Backup Automático**: Sistema de backup de dados

## ⚙️ Configurações

### Banco de Dados
Edite `config/config.php` para configurar a conexão com o banco:

```php
<?php
return [
    'database' => [
        'host' => 'localhost',
        'dbname' => 'kw24_sistema',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ],
    'session' => [
        'timeout' => 3600, // 1 hora
        'name' => 'KW24_SESSION'
    ]
];
```

### Credenciais de Login
Padrão atual (altere em `includes/helpers.php`):
- **Usuário**: `admin`
- **Senha**: `admin123`

## 🔒 Segurança

- ✅ Sistema de autenticação com sessões
- ✅ Proteção contra acesso direto a arquivos PHP
- ✅ Validação de dados de entrada
- ✅ Controle de sessão com timeout automático
- ✅ Sanitização de dados nos formulários

## 📊 Monitoramento

### Sistema de Logs
- **Visualizador Integrado**: Interface para análise de logs em tempo real
- **Filtros Avançados**: Por data, tipo, domínio
- **Busca Inteligente**: Localização rápida de eventos específicos

### Status do Sistema
- Acesse: `http://localhost/app.kw24.com.br/Apps/public/` para desenvolvimento local
- Dashboard com métricas em tempo real

## 🔄 Workflow de Desenvolvimento

1. **Desenvolvimento Local**: 
   - Configure um servidor local (XAMPP, WAMP, LAMP)
   - Clone o projeto na pasta `htdocs` ou similar
   - Configure o banco de dados MySQL
   - Acesse via `http://localhost/app.kw24.com.br/Apps/public/`

2. **Estrutura de Arquivos**:
   - **Frontend**: Arquivos em `/public/` e `/assets/`
   - **Backend**: Lógica em `/dao/`, `/helpers/`, `/includes/`
   - **Layouts**: Templates em `/views/layouts/`

3. **Padrões de Desenvolvimento**:
   - Use o sistema universal de cadastros para novos módulos
   - Siga a estrutura AJAX existente para carregamento dinâmico
   - Mantenha a consistência visual com os componentes existentes

## 🆘 Troubleshooting

### Sistema não carrega?
1. Verifique se o servidor web está rodando
2. Confirme a configuração do banco de dados em `config/config.php`
3. Verifique os logs de erro do PHP
4. Certifique-se que a pasta `public` é acessível

### Erro de login?
1. Verifique as credenciais em `includes/helpers.php`
2. Limpe o cache do navegador
3. Verifique se as sessões estão funcionando

### Modal de cadastro não funciona?
1. Verifique se o JavaScript está carregando
2. Abra o console do navegador para verificar erros
3. Confirme se o `cadastro-universal.js` está sendo incluído

### Erro 404 nas páginas?
1. Verifique se o arquivo existe na pasta `/public/`
2. Confirme a configuração do servidor web
3. Verifique se há redirecionamentos configurados

---

**Desenvolvido por KW24** 🚀
