<?php
session_start();
require_once __DIR__ . '/../includes/helpers.php';
requireAuthentication();

$pageTitle = 'Cadastro - Sistema KW24';
$activeMenu = 'cadastro';

// Determina qual submenu mostrar
$sub = isset($_GET['sub']) ? $_GET['sub'] : 'clientes';

// Valida subpáginas permitidas
$validSubs = ['clientes', 'contatos', 'aplicacoes'];
if (!in_array($sub, $validSubs)) {
    $sub = 'clientes';
}

// Função para formatar telefone
function formatTelefone($telefone) {
    $telefone = preg_replace('/[^0-9]/', '', $telefone);
    if (strlen($telefone) === 13) {
        return sprintf('(%s) %s-%s', substr($telefone,2,2), substr($telefone,4,5), substr($telefone,9,4));
    } elseif (strlen($telefone) === 12) {
        return sprintf('(%s) %s-%s', substr($telefone,2,2), substr($telefone,4,4), substr($telefone,8,4));
    } elseif (strlen($telefone) === 11) {
        return sprintf('(%s) %s-%s', substr($telefone,0,2), substr($telefone,2,5), substr($telefone,7,4));
    } elseif (strlen($telefone) === 10) {
        return sprintf('(%s) %s-%s', substr($telefone,0,2), substr($telefone,2,4), substr($telefone,6,4));
    }
    return $telefone;
}

// Se for clientes, carrega dados
if ($sub === 'clientes') {
    require_once __DIR__ . '/../dao/DAO.php';
    $dao = new DAO();
    $clientes = $dao->getClientesCampos();
}

ob_start();
?>
<!-- Submenu na topbar -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const submenuHtml = `
        <div class="cadastro-submenu">
            <button class="cadastro-submenu-btn <?= $sub === 'clientes' ? 'active' : '' ?>" data-page="clientes">
                <i class="fas fa-users"></i> Clientes
            </button>
            <button class="cadastro-submenu-btn <?= $sub === 'contatos' ? 'active' : '' ?>" data-page="contatos">
                <i class="fas fa-address-book"></i> Contatos
            </button>
            <button class="cadastro-submenu-btn <?= $sub === 'aplicacoes' ? 'active' : '' ?>" data-page="aplicacoes">
                <i class="fas fa-cogs"></i> Aplicações
            </button>
        </div>
        <div class="cadastro-submenu-separator"></div>
    `;
    
    const submenuContainer = document.querySelector('.topbar-submenu');
    if (submenuContainer) {
        submenuContainer.innerHTML = submenuHtml;
        
        // AJAX para navegação dos submenus
        document.querySelectorAll('.cadastro-submenu-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const page = this.dataset.page;
                
                // Remove active de todos os botões
                document.querySelectorAll('.cadastro-submenu-btn').forEach(b => b.classList.remove('active'));
                // Adiciona active no botão clicado
                this.classList.add('active');
                
                // Carrega conteúdo via AJAX
                loadCadastroContent(page);
            });
        });
    }
    
    // Se estamos na página de clientes, inicializa automaticamente
    <?php if ($sub === 'clientes'): ?>
    setTimeout(() => {
        initClientesPage();
    }, 500);
    <?php endif; ?>
});

function loadCadastroContent(page) {
    const mainContent = document.querySelector('.cadastro-content');
    if (!mainContent) return;
    
    // Mostra loading
    mainContent.innerHTML = '<div class="loading-container"><div class="loading-spinner"></div><span>Carregando...</span></div>';
    
    // Faz requisição AJAX
    fetch(`/Apps/public/ajax/cadastro-content.php?sub=${page}`)
        .then(response => response.text())
        .then(html => {
            mainContent.innerHTML = html;
            
            // Reexecuta scripts se necessário
            const scripts = mainContent.querySelectorAll('script');
            scripts.forEach(script => {
                const newScript = document.createElement('script');
                newScript.textContent = script.textContent;
                script.replaceWith(newScript);
            });
            
            // Se for página de clientes, inicializa funcionalidades específicas
            if (page === 'clientes') {
                setTimeout(() => {
                    initClientesPage();
                }, 100);
            }
            
            // Atualiza URL sem recarregar página
            history.pushState({}, '', `/Apps/public/cadastro.php?sub=${page}`);
        })
        .catch(error => {
            console.error('Erro ao carregar conteúdo:', error);
            mainContent.innerHTML = '<div class="error-container">Erro ao carregar conteúdo. Tente novamente.</div>';
        });
}

// Função para inicializar página de clientes
function initClientesPage() {
    console.log('Inicializando página de clientes...');
    
    // Elementos
    const searchInput = document.getElementById('clientes-search');
    const clientesTableBody = document.querySelector('#clientes-table tbody');
    const clientesLoader = document.getElementById('clientes-loader');
    
    if (!searchInput || !clientesTableBody || !clientesLoader) {
        console.error('Elementos da página de clientes não encontrados');
        return;
    }
    
    // Carrega todos os clientes ao inicializar
    carregarTodosClientesAjax();
    
    // Adiciona eventos de busca
    searchInput.addEventListener('keydown', e => {
        if (e.key === 'Enter') {
            e.preventDefault();
            const termo = searchInput.value.trim();
            if (termo.length >= 2) {
                buscarClientesAjax(termo);
            } else {
                carregarTodosClientesAjax();
            }
        }
    });
    
    // Remove event listeners duplicados se existirem
    const existingInputListener = searchInput.getAttribute('data-listener-added');
    if (!existingInputListener) {
        searchInput.setAttribute('data-listener-added', 'true');
    }
}

// Função para carregar todos os clientes via AJAX
function carregarTodosClientesAjax() {
    const clientesTableBody = document.querySelector('#clientes-table tbody');
    const clientesLoader = document.getElementById('clientes-loader');
    
    if (!clientesTableBody || !clientesLoader) return;
    
    console.log('Carregando todos os clientes via AJAX...');
    clientesLoader.style.display = 'flex';
    
    fetch('/Apps/public/clientes_search.php')
        .then(res => res.json())
        .then(data => {
            console.log('Clientes carregados via AJAX:', data);
            renderClientesTableAjax(data);
            clientesLoader.style.display = 'none';
        })
        .catch(error => {
            console.error('Erro ao carregar clientes via AJAX:', error);
            clientesTableBody.innerHTML = '<tr><td colspan="6">Erro ao carregar clientes.</td></tr>';
            clientesLoader.style.display = 'none';
        });
}

// Função para buscar clientes via AJAX
function buscarClientesAjax(termo) {
    const clientesTableBody = document.querySelector('#clientes-table tbody');
    const clientesLoader = document.getElementById('clientes-loader');
    
    if (!clientesTableBody || !clientesLoader) return;
    
    console.log('Buscando clientes via AJAX:', termo);
    clientesLoader.style.display = 'flex';
    
    fetch('/Apps/public/clientes_search.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `term=${encodeURIComponent(termo)}`
    })
        .then(res => res.json())
        .then(data => {
            console.log('Resultados da busca via AJAX:', data);
            renderClientesTableAjax(data);
            clientesLoader.style.display = 'none';
        })
        .catch(error => {
            console.error('Erro na busca via AJAX:', error);
            clientesTableBody.innerHTML = '<tr><td colspan="6">Erro na busca.</td></tr>';
            clientesLoader.style.display = 'none';
        });
}

// Função para renderizar tabela de clientes via AJAX
function renderClientesTableAjax(clientes) {
    const clientesTableBody = document.querySelector('#clientes-table tbody');
    if (!clientesTableBody) return;
    
    if (!clientes || clientes.length === 0) {
        clientesTableBody.innerHTML = '<tr><td colspan="6">Nenhum cliente encontrado.</td></tr>';
        return;
    }
    
    const rows = clientes.map(cliente => {
        const telefoneFormatado = formatTelefoneAjax(cliente.telefone || '');
        const linkBitrix = cliente.link_bitrix ? 
            `<a href="${cliente.link_bitrix}" target="_blank" class="link-bitrix" title="Clique para abrir no Bitrix">${cliente.link_bitrix}</a>` : 
            'N/A';
        
        return `
            <tr data-cliente-id="${cliente.id}" class="cliente-row">
                <td>${cliente.id || ''}</td>
                <td>${cliente.nome || ''}</td>
                <td>${cliente.cnpj || ''}</td>
                <td>${linkBitrix}</td>
                <td>${cliente.email || ''}</td>
                <td>${telefoneFormatado}</td>
            </tr>
        `;
    }).join('');
    
    clientesTableBody.innerHTML = rows;
    
    // Adiciona event listeners para abrir modal ao clicar nas linhas
    const clienteRows = clientesTableBody.querySelectorAll('.cliente-row');
    clienteRows.forEach(row => {
        row.addEventListener('click', function(e) {
            // Verifica se o clique não foi no link do Bitrix
            if (!e.target.closest('.link-bitrix')) {
                const clienteId = this.getAttribute('data-cliente-id');
                if (clienteId) {
                    // Tenta usar a função global primeiro, senão espera ela estar disponível
                    if (typeof window.abrirClienteModal === 'function') {
                        window.abrirClienteModal(clienteId);
                    } else if (typeof abrirClienteModal === 'function') {
                        abrirClienteModal(clienteId);
                    } else {
                        console.log('Aguardando função abrirClienteModal estar disponível...');
                        // Tenta novamente após um pequeno delay
                        setTimeout(() => {
                            if (typeof window.abrirClienteModal === 'function') {
                                window.abrirClienteModal(clienteId);
                            } else {
                                console.error('Função abrirClienteModal não encontrada');
                            }
                        }, 100);
                    }
                }
            }
        });
        
        // Adiciona cursor pointer para indicar que é clicável
        row.style.cursor = 'pointer';
    });
}

// Função para formatar telefone via AJAX
function formatTelefoneAjax(telefone) {
    if (!telefone) return '';
    
    telefone = telefone.replace(/[^0-9]/g, '');
    if (telefone.length === 13) {
        return `(${telefone.substr(2,2)}) ${telefone.substr(4,5)}-${telefone.substr(9,4)}`;
    } else if (telefone.length === 12) {
        return `(${telefone.substr(2,2)}) ${telefone.substr(4,4)}-${telefone.substr(8,4)}`;
    } else if (telefone.length === 11) {
        return `(${telefone.substr(0,2)}) ${telefone.substr(2,5)}-${telefone.substr(7,4)}`;
    } else if (telefone.length === 10) {
        return `(${telefone.substr(0,2)}) ${telefone.substr(2,4)}-${telefone.substr(6,4)}`;
    }
    return telefone;
}
</script>

<div class="cadastro-content">
    <?php if ($sub === 'clientes'): ?>
        <!-- CONTEÚDO CLIENTES -->
        <div class="clientes-page-wrapper">
            <div class="clientes-header">
                <h1>Clientes</h1>
                <div class="clientes-actions">
                    <input type="text" id="clientes-search" class="clientes-search" placeholder="Filtrar e pesquisar clientes..." autocomplete="off">
                </div>
            </div>

            <div id="clientes-loader" class="clientes-loader" style="display:none">
                <span class="loading-spinner"></span>
                <span class="loading-text">Atualizando...</span>
            </div>

            <div class="clientes-container">
                <div id="clientes-table-wrapper" class="clientes-table-wrapper">
                    <table id="clientes-table" class="clientes-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Empresa</th>
                                <th>CNPJ</th>
                                <th>Link Bitrix</th>
                                <th>Email</th>
                                <th>Telefone</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Os dados serão carregados via JavaScript -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Modal de detalhes -->
        <div id="cliente-detail-modal" class="cliente-detail-modal" style="display:none;">
            <div class="cliente-detail-overlay"></div>
            <div class="cliente-detail-content">
                <div id="cliente-detail-body">
                    <!-- Conteúdo via AJAX -->
                </div>
            </div>
        </div>

        <!-- Filtro avançado -->
        <div id="clientes-filter-panel" class="clientes-filter-panel" style="display:none;">
            <form id="clientes-filter-form">
                <h3>Filtro avançado</h3>
                <label for="filter-aplicacao">Aplicação</label>
                <input type="text" id="filter-aplicacao" name="aplicacao" placeholder="Aplicação">
                <label for="filter-cnpj">CNPJ</label>
                <input type="text" id="filter-cnpj" name="cnpj" placeholder="CNPJ">
                <button type="submit" class="btn-aplicar-filtro">Aplicar filtro</button>
                <button type="button" class="btn-fechar-filtro" id="btn-fechar-filtro">Fechar</button>
            </form>
        </div>

    <?php elseif ($sub === 'contatos'): ?>
        <!-- CONTEÚDO CONTATOS -->
        <div class="contatos-container">
            <h1>Contatos</h1>
            <p>Área de gerenciamento de contatos.</p>
            <!-- Aqui será implementado o conteúdo de contatos -->
        </div>

    <?php elseif ($sub === 'aplicacoes'): ?>
        <!-- CONTEÚDO APLICAÇÕES -->
        <div class="aplicacoes-container">
            <h1>Aplicações</h1>
            <p>Área de gerenciamento de aplicações.</p>
            <!-- Aqui será implementado o conteúdo de aplicações -->
        </div>

    <?php endif; ?>
</div>

<?php
$content = ob_get_clean();

$additionalCSS = '<link rel="stylesheet" href="/Apps/assets/css/cadastro.css">';
$additionalJS  = '<script src="/Apps/assets/js/cadastro.js"></script>';

// Layout base
include __DIR__ . '/../views/layouts/main.php';
