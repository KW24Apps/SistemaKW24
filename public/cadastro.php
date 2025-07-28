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
    
    // Adiciona evento para o botão criar cliente
    const btnCriarCliente = document.getElementById('btn-criar-cliente');
    if (btnCriarCliente) {
        btnCriarCliente.addEventListener('click', function() {
            if (typeof window.abrirModalCriarCliente === 'function') {
                window.abrirModalCriarCliente();
            } else {
                abrirModalCriarCliente();
            }
        });
    }
    
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

<script>
// Torna as funções acessíveis globalmente
window.abrirModalCriarCliente = function() {
    const modal = document.getElementById('cliente-detail-modal');
    const modalBody = document.getElementById('cliente-detail-body');
    
    if (!modal || !modalBody) {
        console.error('Modal não encontrado');
        return;
    }
    
    // Conteúdo do modal para criar cliente
    modalBody.innerHTML = `
        <div class="cliente-detail-header">
            <h2>Criar Novo Cliente</h2>
            <button type="button" id="cliente-detail-close" class="cliente-detail-close">×</button>
        </div>
        <div class="cliente-detail-content-grid">
            <div class="cliente-modal-left">
                <form id="cliente-create-form">
                    <div>
                        <label>Nome/Empresa: <span style="color: red;">*</span></label>
                        <input type="text" name="nome" value="" placeholder="Digite o nome da empresa" required>
                    </div>
                    <div>
                        <label>CNPJ:</label>
                        <input type="text" name="cnpj" value="" placeholder="Digite o CNPJ">
                    </div>
                    <div>
                        <label>Link Bitrix:</label>
                        <input type="text" name="link_bitrix" value="" placeholder="URL do Bitrix">
                    </div>
                    <div>
                        <label>Email:</label>
                        <input type="email" name="email" value="" placeholder="email@exemplo.com">
                    </div>
                    <div>
                        <label>Telefone:</label>
                        <input type="text" name="telefone" value="" placeholder="(11) 99999-9999">
                    </div>
                    <div>
                        <label>Endereço:</label>
                        <input type="text" name="endereco" value="" placeholder="Endereço completo">
                    </div>
                </form>
            </div>
            <div class="cliente-modal-right">
                <h3>Aplicações</h3>
                <div style="color:#aaa">(Será configurado após criar o cliente)</div>
            </div>
        </div>
        <div id="modal-create-actions" class="modal-footer-actions">
            <button type="button" id="btn-criar-modal" class="btn-criar-modal">
                <i class="fas fa-save"></i> Criar Cliente
            </button>
            <button type="button" id="btn-cancelar-create-modal" class="btn-cancelar-modal">
                <i class="fas fa-times"></i> Cancelar
            </button>
        </div>
    `;
    
    // Mostra o modal
    modal.style.display = 'flex';
    
    // Event listeners para o formulário de criação
    setupModalCreateEvents(modal);
};

function setupModalCreateEvents(modal) {
    const form = document.getElementById('cliente-create-form');
    const btnCriar = document.getElementById('btn-criar-modal');
    const btnCancelar = document.getElementById('btn-cancelar-create-modal');
    
    // Botão criar
    if (btnCriar) {
        btnCriar.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Valida campos obrigatórios
            const nomeInput = form.querySelector('input[name="nome"]');
            if (!nomeInput.value.trim()) {
                alert('O nome da empresa é obrigatório!');
                nomeInput.focus();
                return;
            }
            
            criarNovoCliente(form, modal);
        });
    }
    
    // Botão cancelar
    if (btnCancelar) {
        btnCancelar.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }
    
    // Botão fechar (X)
    const btnFechar = document.getElementById('cliente-detail-close');
    if (btnFechar) {
        btnFechar.onclick = function() {
            modal.style.display = 'none';
        };
    }
    
    // Fechar ao clicar fora da área
    const overlay = modal.querySelector('.cliente-detail-overlay');
    if (overlay) {
        overlay.addEventListener('click', function() {
            modal.style.display = 'none';
        });
    }
}

function criarNovoCliente(form, modal) {
    const formData = new FormData(form);
    const clienteData = {
        nome: formData.get('nome'),
        cnpj: formData.get('cnpj'),
        link_bitrix: formData.get('link_bitrix'),
        email: formData.get('email'),
        telefone: formData.get('telefone'),
        endereco: formData.get('endereco')
    };
    
    // Mostra loader no botão
    const btnCriar = document.getElementById('btn-criar-modal');
    if (btnCriar) {
        btnCriar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando...';
        btnCriar.disabled = true;
    }
    
    fetch('/Apps/public/cliente_create.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(clienteData)
    })
    .then(res => res.json())
    .then(data => {
        if (btnCriar) {
            btnCriar.innerHTML = '<i class="fas fa-save"></i> Criar Cliente';
            btnCriar.disabled = false;
        }
        
        if (data.success) {
            // Mostra mensagem de sucesso
            alert('Cliente criado com sucesso!');
            modal.style.display = 'none';
            
            // Recarrega a tabela para mostrar o novo cliente
            if (typeof carregarTodosClientesAjax === 'function') {
                carregarTodosClientesAjax();
            } else {
                location.reload();
            }
        } else {
            alert('Erro ao criar cliente: ' + (data.message || 'Erro desconhecido'));
        }
    })
    .catch(error => {
        console.error('Erro ao criar cliente:', error);
        
        if (btnCriar) {
            btnCriar.innerHTML = '<i class="fas fa-save"></i> Criar Cliente';
            btnCriar.disabled = false;
        }
        
        alert('Erro ao criar cliente. Tente novamente.');
    });
}
</script>

<div class="cadastro-content">
    <?php if ($sub === 'clientes'): ?>
        <!-- CONTEÚDO CLIENTES -->
        <div class="clientes-page-wrapper">
            <div class="clientes-header">
                <h1>Clientes</h1>
                <div class="clientes-actions">
                    <button id="btn-criar-cliente" class="btn-criar-cliente">
                        <i class="fas fa-plus"></i> Criar
                    </button>
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
