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

// Se for contatos, carrega dados
if ($sub === 'contatos') {
    require_once __DIR__ . '/../dao/DAO.php';
    $dao = new DAO();
    $contatos = $dao->getContatosCampos();
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
    
    // Se estamos na página de contatos, inicializa automaticamente
    <?php if ($sub === 'contatos'): ?>
    setTimeout(() => {
        initContatos();
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
            
            // Se for página de contatos, inicializa funcionalidades específicas
            if (page === 'contatos') {
                setTimeout(() => {
                    initContatos();
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
    
    // Inicializa ordenação dos cabeçalhos
    initTableSorting();
}

// Variáveis globais para controle de ordenação
let currentSortColumn = 'id'; // Define ID como padrão
let currentSortDirection = 'asc';
let clientesDataCache = [];

// Função para inicializar ordenação da tabela
function initTableSorting() {
    const sortableHeaders = document.querySelectorAll('.clientes-table th.sortable');
    
    // Define o ID como ordenação padrão visual
    const idHeader = document.querySelector('.clientes-table th[data-column="id"]');
    if (idHeader) {
        idHeader.classList.add('asc');
    }
    
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const column = this.getAttribute('data-column');
            
            // Remove classes de ordenação de outros cabeçalhos
            sortableHeaders.forEach(h => h.classList.remove('asc', 'desc'));
            sortableHeaders.forEach(h => h.classList.remove('asc', 'desc'));
            
            // Determina a direção da ordenação
            if (currentSortColumn === column) {
                // Inverte a direção se for a mesma coluna
                currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
            } else {
                // Nova coluna, sempre começa em ordem crescente
                currentSortDirection = 'asc';
                currentSortColumn = column;
            }
            
            // Adiciona classe visual ao cabeçalho
            this.classList.add(currentSortDirection);
            
            // Ordena os dados
            sortClientesData(column, currentSortDirection);
        });
    });
}

// Função para ordenar dados dos clientes
function sortClientesData(column, direction) {
    if (!clientesDataCache.length) {
        console.log('Nenhum dado para ordenar');
        return;
    }
    
    const sortedData = [...clientesDataCache].sort((a, b) => {
        let valueA, valueB;
        
        switch(column) {
            case 'id':
                valueA = parseInt(a.id) || 0;
                valueB = parseInt(b.id) || 0;
                break;
            case 'nome':
                valueA = (a.nome || '').toLowerCase();
                valueB = (b.nome || '').toLowerCase();
                break;
            case 'cnpj':
                valueA = (a.cnpj || '').replace(/\D/g, '');
                valueB = (b.cnpj || '').replace(/\D/g, '');
                break;
            default:
                return 0;
        }
        
        // Comparação numérica para ID
        if (column === 'id') {
            return direction === 'asc' ? valueA - valueB : valueB - valueA;
        }
        
        // Comparação alfabética para outros campos
        if (valueA < valueB) {
            return direction === 'asc' ? -1 : 1;
        }
        if (valueA > valueB) {
            return direction === 'asc' ? 1 : -1;
        }
        return 0;
    });
    
    // Atualiza a tabela com dados ordenados
    renderClientesTableAjax(sortedData);
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
            clientesDataCache = data; // Armazena no cache para ordenação
            
            // Aplica ordenação padrão por ID
            sortClientesData('id', 'asc');
            
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
            clientesDataCache = data; // Armazena no cache para ordenação
            
            // Aplica ordenação padrão por ID nos resultados da busca
            sortClientesData('id', 'asc');
            
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
    console.log('Abrindo modal para criar novo cliente...');
    abrirModalCliente(null); // null indica criação de novo cliente
};

// Função universal para abrir modal (criar ou editar)
function abrirModalCliente(clienteId) {
    const modal = document.getElementById('cliente-detail-modal');
    const modalBody = document.getElementById('cliente-detail-body');
    
    if (!modal || !modalBody) {
        console.error('Modal não encontrado');
        return;
    }
    
    modal.style.display = 'flex';
    
    if (clienteId) {
        // Modo edição - carrega dados do cliente
        const clientesLoader = document.getElementById('clientes-loader');
        if (clientesLoader) {
            clientesLoader.style.display = 'flex';
        }
        
        fetch(`/Apps/public/clientes_search.php?id=${clienteId}`)
            .then(res => res.json())
            .then(data => {
                if (clientesLoader) {
                    clientesLoader.style.display = 'none';
                }
                renderModalCliente(data, false); // false = modo edição
            })
            .catch(error => {
                console.error('Erro ao buscar cliente:', error);
                if (clientesLoader) {
                    clientesLoader.style.display = 'none';
                }
                modalBody.innerHTML = '<div style="padding:32px">Erro ao buscar dados do cliente.</div>';
            });
    } else {
        // Modo criação - dados vazios
        const dadosVazios = {
            id: '',
            nome: '',
            cnpj: '',
            link_bitrix: '',
            email: '',
            telefone: '',
            endereco: ''
        };
        renderModalCliente(dadosVazios, true); // true = modo criação
    }
}

// Renderiza o conteúdo do modal (criação ou edição)
function renderModalCliente(data, isCriacao) {
    const modalBody = document.getElementById('cliente-detail-body');
    const titulo = isCriacao ? 'Criar Novo Cliente' : `Cliente - ${data.nome || 'ID ' + data.id}`;
    
    console.log('Renderizando modal:', { data, isCriacao, titulo });
    
    modalBody.innerHTML = `
        <div class="cliente-detail-header">
            <h2>${titulo}</h2>
            <button type="button" id="cliente-detail-close" class="cliente-detail-close">×</button>
        </div>
        <div class="cliente-detail-content-grid">
            <div class="cliente-modal-left">
                <form id="cliente-form">
                    ${!isCriacao ? `
                        <div>
                            <label>ID:</label>
                            <input type="text" value="${data.id}" disabled>
                        </div>
                    ` : ''}
                    <div>
                        <label>Nome/Empresa:${isCriacao ? ' <span style="color: red;">*</span>' : ''}</label>
                        <input type="text" name="nome" value="${data.nome || ''}" data-original="${data.nome || ''}" ${isCriacao ? 'placeholder="Digite o nome da empresa" required' : ''}>
                    </div>
                    <div>
                        <label>CNPJ:</label>
                        <input type="text" name="cnpj" value="${data.cnpj || ''}" data-original="${data.cnpj || ''}" ${isCriacao ? 'placeholder="Digite o CNPJ"' : ''}>
                    </div>
                    <div>
                        <label>Link Bitrix:</label>
                        <input type="text" name="link_bitrix" value="${data.link_bitrix || ''}" data-original="${data.link_bitrix || ''}" ${isCriacao ? 'placeholder="URL do Bitrix"' : ''}>
                    </div>
                    <div>
                        <label>Email:</label>
                        <input type="email" name="email" value="${data.email || ''}" data-original="${data.email || ''}" ${isCriacao ? 'placeholder="email@exemplo.com"' : ''}>
                    </div>
                    <div>
                        <label>Telefone:</label>
                        <input type="text" name="telefone" value="${data.telefone || ''}" data-original="${data.telefone || ''}" ${isCriacao ? 'placeholder="(11) 99999-9999"' : ''}>
                    </div>
                    <div>
                        <label>Endereço:</label>
                        <input type="text" name="endereco" value="${data.endereco || ''}" data-original="${data.endereco || ''}" ${isCriacao ? 'placeholder="Endereço completo"' : ''}>
                    </div>
                </form>
            </div>
            <div class="cliente-modal-right">
                <h3>Aplicações</h3>
                <div style="color:#aaa">${isCriacao ? '(Será configurado após criar o cliente)' : '(Em breve)'}</div>
            </div>
        </div>
        <div id="modal-actions" class="modal-footer-actions" style="display: ${isCriacao ? 'flex' : 'none'}; opacity: ${isCriacao ? '1' : '0'};">
            <button type="button" id="btn-salvar-modal">${isCriacao ? '<i class="fas fa-save"></i> Criar Cliente' : 'Salvar'}</button>
            <button type="button" id="btn-cancelar-modal">Cancelar</button>
        </div>
    `;
    
    console.log('Modal HTML criado. Configurando eventos...');
    
    // Configura eventos do modal
    setupModalEventosUniversal(document.getElementById('cliente-detail-modal'), data, isCriacao);
}

// Eventos universais do modal (criação ou edição)
function setupModalEventosUniversal(modal, originalData, isCriacao) {
    console.log('Configurando eventos do modal:', { isCriacao });
    
    const modalActions = document.getElementById('modal-actions');
    const form = document.getElementById('cliente-form');
    const btnSalvar = document.getElementById('btn-salvar-modal');
    const btnCancelar = document.getElementById('btn-cancelar-modal');
    
    console.log('Elementos encontrados:', {
        modalActions: !!modalActions,
        form: !!form,
        btnSalvar: !!btnSalvar,
        btnCancelar: !!btnCancelar
    });
    
    // Monitora alterações nos campos (para modo edição)
    if (!isCriacao) {
        const inputs = form.querySelectorAll('input[type="text"]:not([disabled]), input[type="email"]');
        inputs.forEach(input => {
            input.addEventListener('input', function() {
                // Verifica se algum campo foi alterado
                let hasChanges = false;
                inputs.forEach(inp => {
                    const orig = inp.getAttribute('data-original') || '';
                    const atual = inp.value;
                    if (orig !== atual) {
                        hasChanges = true;
                    }
                });
                
                if (hasChanges && modalActions.style.display === 'none') {
                    modalActions.style.display = 'flex';
                    modalActions.style.opacity = '0';
                    setTimeout(() => {
                        modalActions.style.opacity = '1';
                    }, 10);
                } else if (!hasChanges && modalActions.style.display !== 'none') {
                    modalActions.style.opacity = '0';
                    setTimeout(() => {
                        modalActions.style.display = 'none';
                    }, 300);
                }
            });
        });
    }
    
    // Botão salvar/criar
    if (btnSalvar) {
        btnSalvar.addEventListener('click', function(e) {
            e.preventDefault();
            
            if (isCriacao) {
                // Validação para criação
                const nomeInput = form.querySelector('input[name="nome"]');
                if (!nomeInput.value.trim()) {
                    alert('O nome da empresa é obrigatório!');
                    nomeInput.focus();
                    return;
                }
                criarCliente(form, modal);
            } else {
                // Validação para edição
                const inputs = form.querySelectorAll('input[type="text"]:not([disabled]), input[type="email"]');
                let hasChanges = false;
                inputs.forEach(inp => {
                    const orig = inp.getAttribute('data-original') || '';
                    const atual = inp.value;
                    if (orig !== atual) {
                        hasChanges = true;
                    }
                });
                
                if (hasChanges) {
                    salvarCliente(form, modal);
                } else {
                    modal.style.display = 'none';
                }
            }
        });
    }
    
    // Botão cancelar
    if (btnCancelar) {
        btnCancelar.addEventListener('click', function() {
            if (isCriacao) {
                // Para criação, verifica se tem dados preenchidos
                const nomeInput = form.querySelector('input[name="nome"]');
                if (nomeInput && nomeInput.value.trim()) {
                    // Se tem dados, pergunta se quer descartar
                    if (confirm('Descartar dados inseridos?')) {
                        modal.style.display = 'none';
                    }
                } else {
                    // Se não tem dados, fecha direto
                    modal.style.display = 'none';
                }
            } else {
                tentarFecharModal(modal, form);
            }
        });
    }
    
    // Botão fechar (X)
    const btnFechar = document.getElementById('cliente-detail-close');
    if (btnFechar) {
        btnFechar.onclick = function() {
            if (isCriacao) {
                // Para criação, verifica se tem dados preenchidos
                const nomeInput = form.querySelector('input[name="nome"]');
                if (nomeInput && nomeInput.value.trim()) {
                    // Se tem dados, pergunta se quer descartar
                    if (confirm('Descartar dados inseridos?')) {
                        modal.style.display = 'none';
                    }
                } else {
                    // Se não tem dados, fecha direto
                    modal.style.display = 'none';
                }
            } else {
                tentarFecharModal(modal, form);
            }
        };
    }
    
    // Fechar ao clicar fora da área
    const overlay = modal.querySelector('.cliente-detail-overlay');
    if (overlay) {
        overlay.addEventListener('click', function() {
            if (isCriacao) {
                // Para criação, verifica se tem dados preenchidos
                const nomeInput = form.querySelector('input[name="nome"]');
                if (nomeInput && nomeInput.value.trim()) {
                    // Se tem dados, pergunta se quer descartar
                    if (confirm('Descartar dados inseridos?')) {
                        modal.style.display = 'none';
                    }
                } else {
                    // Se não tem dados, fecha direto
                    modal.style.display = 'none';
                }
            } else {
                tentarFecharModal(modal, form);
            }
        });
    }
}

// Função para criar cliente
function criarCliente(form, modal) {
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
    const btnSalvar = document.getElementById('btn-salvar-modal');
    if (btnSalvar) {
        btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando...';
        btnSalvar.disabled = true;
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
        if (btnSalvar) {
            btnSalvar.innerHTML = '<i class="fas fa-save"></i> Criar Cliente';
            btnSalvar.disabled = false;
        }
        
        if (data.success) {
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
        
        if (btnSalvar) {
            btnSalvar.innerHTML = '<i class="fas fa-save"></i> Criar Cliente';
            btnSalvar.disabled = false;
        }
        
        alert('Erro ao criar cliente. Tente novamente.');
    });
}

// Função para salvar cliente (reutiliza a existente)
function salvarCliente(form, modal) {
    const formData = new FormData(form);
    const clienteData = {
        id: form.querySelector('input[disabled]').value,
        nome: formData.get('nome'),
        cnpj: formData.get('cnpj'),
        link_bitrix: formData.get('link_bitrix'),
        email: formData.get('email'),
        telefone: formData.get('telefone'),
        endereco: formData.get('endereco')
    };

    // Mostra loader
    const clientesLoader = document.getElementById('clientes-loader');
    if (clientesLoader) {
        clientesLoader.style.display = 'flex';
    }

    fetch('/Apps/public/cliente_save.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify(clienteData)
    })
    .then(res => res.json())
    .then(data => {
        if (clientesLoader) {
            clientesLoader.style.display = 'none';
        }
        if (data.success) {
            alert('Dados salvos com sucesso!');
            modal.style.display = 'none';
            // Recarrega a tabela
            const termo = document.getElementById('clientes-search') ? document.getElementById('clientes-search').value.trim() : '';
            if (typeof carregarTodosClientesAjax === 'function') {
                if (termo !== '') {
                    buscarClientesAjax(termo);
                } else {
                    carregarTodosClientesAjax();
                }
            }
        } else {
            alert('Erro ao salvar: ' + data.message);
        }
    })
    .catch(error => {
        if (clientesLoader) {
            clientesLoader.style.display = 'none';
        }
        console.error('Erro ao salvar cliente:', error);
        alert('Erro ao salvar cliente. Tente novamente.');
    });
}

// Atualiza a função global existente para usar a nova função universal
window.abrirClienteModal = function(clienteId) {
    abrirModalCliente(clienteId);
};

// Função para tentar fechar modal verificando alterações
function tentarFecharModal(modal, form) {
    console.log('Tentando fechar modal, verificando alterações...');
    
    // Verifica se há alterações não salvas
    const inputs = form.querySelectorAll('input[type="text"]:not([disabled]), input[type="email"]');
    let hasChanges = false;
    
    inputs.forEach(inp => {
        const orig = inp.getAttribute('data-original') || '';
        const atual = inp.value.trim();
        if (orig !== atual) {
            console.log(`Campo alterado: ${inp.name} - Original: "${orig}" - Atual: "${atual}"`);
            hasChanges = true;
        }
    });

    console.log('Tem alterações:', hasChanges);

    if (hasChanges) {
        // Mostra modal de confirmação
        mostrarModalConfirmacao(modal);
    } else {
        // Fecha diretamente se não há alterações
        modal.style.display = 'none';
    }
}

// Função para mostrar modal de confirmação
function mostrarModalConfirmacao(modalOriginal) {
    // Remove modal anterior se existir
    const modalAnterior = document.getElementById('modal-confirmacao-salvar');
    if (modalAnterior) {
        modalAnterior.remove();
    }

    // Cria modal de confirmação
    const modalConfirmacao = document.createElement('div');
    modalConfirmacao.id = 'modal-confirmacao-salvar';
    modalConfirmacao.className = 'modal-confirmacao-salvar';
    modalConfirmacao.innerHTML = `
        <div class="modal-confirmacao-overlay"></div>
        <div class="modal-confirmacao-content">
            <div class="modal-confirmacao-header">
                <i class="fas fa-exclamation-triangle"></i>
                <h3>Alterações não salvas</h3>
            </div>
            <div class="modal-confirmacao-body">
                <p>Você fez alterações que não foram salvas. O que deseja fazer?</p>
            </div>
            <div class="modal-confirmacao-footer">
                <button type="button" id="btn-salvar-e-fechar" class="btn-salvar-e-fechar">
                    <i class="fas fa-save"></i> Salvar
                </button>
                <button type="button" id="btn-descartar-e-fechar" class="btn-descartar-e-fechar">
                    <i class="fas fa-times"></i> Descartar
                </button>
                <button type="button" id="btn-cancelar-fechamento" class="btn-cancelar-fechamento">
                    <i class="fas fa-arrow-left"></i> Continuar editando
                </button>
            </div>
        </div>
    `;

    // Adiciona ao body
    document.body.appendChild(modalConfirmacao);

    // Mostra o modal
    setTimeout(() => {
        modalConfirmacao.classList.add('show');
    }, 10);

    // Event listeners
    const btnSalvarEFechar = document.getElementById('btn-salvar-e-fechar');
    const btnDescartarEFechar = document.getElementById('btn-descartar-e-fechar');
    const btnCancelarFechamento = document.getElementById('btn-cancelar-fechamento');

    // Salvar e fechar
    if (btnSalvarEFechar) {
        btnSalvarEFechar.addEventListener('click', function() {
            const form = document.getElementById('cliente-form');
            if (form) {
                // Mostra loader enquanto salva
                btnSalvarEFechar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
                btnSalvarEFechar.disabled = true;
                
                salvarCliente(form, modalOriginal);
            }
            modalConfirmacao.remove();
        });
    }

    // Descartar e fechar
    if (btnDescartarEFechar) {
        btnDescartarEFechar.addEventListener('click', function() {
            modalOriginal.style.display = 'none';
            modalConfirmacao.remove();
        });
    }

    // Cancelar fechamento (continuar editando)
    if (btnCancelarFechamento) {
        btnCancelarFechamento.addEventListener('click', function() {
            modalConfirmacao.remove();
        });
    }

    // Fechar ao clicar fora
    const overlay = modalConfirmacao.querySelector('.modal-confirmacao-overlay');
    if (overlay) {
        overlay.addEventListener('click', function() {
            modalConfirmacao.remove();
        });
    }
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
                                <th class="sortable" data-column="id">ID</th>
                                <th class="sortable" data-column="nome">Empresa</th>
                                <th class="sortable" data-column="cnpj">CNPJ</th>
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
        <div class="contatos-page-wrapper">
            <!-- Header dos contatos -->
            <div class="contatos-header">
                <h1>Contatos</h1>
                <div class="contatos-actions">
                    <button class="btn-criar-contato" onclick="abrirModalContato('criar')">
                        <i class="fas fa-plus"></i> Criar
                    </button>
                    <input type="text" 
                           class="contatos-search" 
                           id="contatos-search" 
                           placeholder="Buscar contatos..."
                           autocomplete="off">
                </div>
            </div>

            <!-- Container da tabela -->
            <div class="contatos-container-table">
                <div class="contatos-table-wrapper">
                    <table class="contatos-table">
                        <thead>
                            <tr>
                                <th class="sortable" data-column="id">ID</th>
                                <th class="sortable" data-column="nome">Nome</th>
                                <th class="sortable" data-column="cargo">Cargo</th>
                                <th class="sortable" data-column="email">Email</th>
                                <th>Telefone</th>
                            </tr>
                        </thead>
                        <tbody id="contatos-table-body">
                            <?php if (isset($contatos) && !empty($contatos)): ?>
                                <?php foreach ($contatos as $contato): ?>
                                    <tr onclick="abrirModalContato('visualizar', <?= $contato['id'] ?>)" style="cursor: pointer;">
                                        <td><?= htmlspecialchars($contato['id']) ?></td>
                                        <td><?= htmlspecialchars($contato['nome']) ?></td>
                                        <td><?= htmlspecialchars($contato['cargo'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($contato['email'] ?? '') ?></td>
                                        <td><?= htmlspecialchars(formatTelefone($contato['telefone'] ?? '')) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 40px; color: #666;">
                                        Nenhum contato encontrado
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Loader -->
            <div class="contatos-loader" id="contatos-loader">
                <div class="loading-spinner"></div>
                <span class="loading-text">Carregando contatos...</span>
            </div>
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

<!-- SCRIPTS JAVASCRIPT PARA CONTATOS -->
<script>
// =================== VARIÁVEIS GLOBAIS PARA CONTATOS ===================
let currentSortColumnContatos = 'id';
let currentSortDirectionContatos = 'asc';
let contatosDataCache = [];

// =================== FUNÇÕES PRINCIPAIS CONTATOS ===================

// Função unificada para abrir modal de contato (criar/editar/visualizar)
function abrirModalContato(modo, contatoId = null) {
    console.log('Abrindo modal contato:', modo, contatoId);
    
    // Cria o modal
    const modal = document.createElement('div');
    modal.className = 'contato-detail-modal';
    modal.innerHTML = `
        <div class="contato-detail-overlay"></div>
        <div class="contato-detail-content">
            <button class="contato-detail-close" onclick="fecharModalContato()">&times;</button>
            <div class="contato-modal-header">
                <h2 class="contato-modal-title">${modo === 'criar' ? 'Criar Novo Contato' : modo === 'editar' ? 'Editar Contato' : 'Detalhes do Contato'}</h2>
            </div>
            <div class="contato-detail-content-grid">
                <div class="contato-modal-left">
                    <form id="contato-form">
                        <div>
                            <label for="contato-nome">Nome *</label>
                            <input type="text" id="contato-nome" name="nome" ${modo === 'visualizar' ? 'disabled' : ''} required>
                        </div>
                        <div>
                            <label for="contato-cargo">Cargo</label>
                            <input type="text" id="contato-cargo" name="cargo" ${modo === 'visualizar' ? 'disabled' : ''}>
                        </div>
                        <div>
                            <label for="contato-email">Email</label>
                            <input type="email" id="contato-email" name="email" ${modo === 'visualizar' ? 'disabled' : ''}>
                        </div>
                        <div>
                            <label for="contato-telefone">Telefone</label>
                            <input type="text" id="contato-telefone" name="telefone" ${modo === 'visualizar' ? 'disabled' : ''}>
                        </div>
                        ${modo !== 'criar' ? `
                        <div>
                            <label for="contato-id-bitrix">ID Bitrix</label>
                            <input type="text" id="contato-id-bitrix" name="id_bitrix" disabled>
                        </div>` : ''}
                    </form>
                </div>
                <div class="contato-modal-right">
                    <h3>Informações</h3>
                    <p>Preencha os dados do contato conforme necessário.</p>
                    ${modo === 'visualizar' ? '<p><small>Para editar, clique no botão "Editar".</small></p>' : ''}
                </div>
            </div>
            <div class="contato-modal-footer-actions" id="contato-modal-actions">
                ${modo === 'criar' ? `
                    <button type="button" class="btn-criar-contato-modal" onclick="salvarContato()">Criar Contato</button>
                    <button type="button" class="btn-cancelar-contato-modal" onclick="fecharModalContato()">Cancelar</button>
                ` : modo === 'editar' ? `
                    <button type="button" class="btn-criar-contato-modal" onclick="salvarContato(${contatoId})">Salvar</button>
                    <button type="button" class="btn-cancelar-contato-modal" onclick="fecharModalContato()">Cancelar</button>
                ` : `
                    <button type="button" class="btn-editar-contato" onclick="editarContato(${contatoId})">Editar</button>
                    <button type="button" class="btn-cancelar-contato-modal" onclick="fecharModalContato()">Fechar</button>
                `}
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    
    // Mostra os botões
    const modalActions = modal.querySelector('#contato-modal-actions');
    modalActions.style.display = 'flex';
    
    // Se for editar ou visualizar, carrega os dados
    if (contatoId && modo !== 'criar') {
        carregarDadosContato(contatoId);
    }
    
    // Event listeners
    modal.querySelector('.contato-detail-overlay').addEventListener('click', fecharModalContato);
    
    // Previne fechamento ao clicar no conteúdo
    modal.querySelector('.contato-detail-content').addEventListener('click', function(e) {
        e.stopPropagation();
    });
}

function fecharModalContato() {
    const modal = document.querySelector('.contato-detail-modal');
    if (modal) {
        modal.remove();
    }
}

function editarContato(contatoId) {
    fecharModalContato();
    setTimeout(() => abrirModalContato('editar', contatoId), 100);
}

function carregarDadosContato(contatoId) {
    const contato = contatosDataCache.find(c => c.id == contatoId);
    if (contato) {
        document.getElementById('contato-nome').value = contato.nome || '';
        document.getElementById('contato-cargo').value = contato.cargo || '';
        document.getElementById('contato-email').value = contato.email || '';
        document.getElementById('contato-telefone').value = contato.telefone_raw || '';
        
        const idBitrixField = document.getElementById('contato-id-bitrix');
        if (idBitrixField) {
            idBitrixField.value = contato.id_bitrix || '';
        }
    }
}

function salvarContato(contatoId = null) {
    const form = document.getElementById('contato-form');
    const formData = new FormData(form);
    
    const url = contatoId ? `/Apps/public/contato_update.php?id=${contatoId}` : '/Apps/public/contato_create.php';
    
    fetch(url, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showAlert(data.message, 'success');
            fecharModalContato();
            setTimeout(() => {
                carregarContatosAjax(); // Recarrega a lista
            }, 500);
        } else {
            showAlert(data.message, 'error');
        }
    })
    .catch(error => {
        console.error('Erro:', error);
        showAlert('Erro ao salvar contato', 'error');
    });
}

// =================== FUNÇÕES DE BUSCA E CARREGAMENTO ===================

function carregarContatosAjax() {
    const contatosLoader = document.getElementById('contatos-loader');
    const contatosTableBody = document.getElementById('contatos-table-body');
    
    if (!contatosTableBody) return;
    
    console.log('Carregando contatos via AJAX...');
    contatosLoader.style.display = 'flex';
    
    fetch('/Apps/public/contatos_search.php')
        .then(res => res.json())
        .then(data => {
            console.log('Contatos carregados via AJAX:', data);
            contatosDataCache = data;
            
            // Aplica ordenação padrão por ID
            sortContatosData('id', 'asc');
            
            contatosLoader.style.display = 'none';
        })
        .catch(error => {
            console.error('Erro ao carregar contatos via AJAX:', error);
            contatosTableBody.innerHTML = '<tr><td colspan="5">Erro ao carregar contatos.</td></tr>';
            contatosLoader.style.display = 'none';
        });
}

function buscarContatosAjax(termo) {
    const contatosLoader = document.getElementById('contatos-loader');
    const contatosTableBody = document.getElementById('contatos-table-body');
    
    console.log('Buscando contatos via AJAX:', termo);
    contatosLoader.style.display = 'flex';
    
    fetch('/Apps/public/contatos_search.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `term=${encodeURIComponent(termo)}`
    })
        .then(res => res.json())
        .then(data => {
            console.log('Resultados da busca via AJAX:', data);
            contatosDataCache = data;
            
            // Aplica ordenação padrão por ID nos resultados da busca
            sortContatosData('id', 'asc');
            
            contatosLoader.style.display = 'none';
        })
        .catch(error => {
            console.error('Erro na busca via AJAX:', error);
            contatosTableBody.innerHTML = '<tr><td colspan="5">Erro na busca.</td></tr>';
            contatosLoader.style.display = 'none';
        });
}

// =================== FUNÇÕES DE ORDENAÇÃO ===================

function initTableSortingContatos() {
    const sortableHeaders = document.querySelectorAll('.contatos-table th.sortable');
    
    // Define o ID como ordenação padrão visual
    const idHeader = document.querySelector('.contatos-table th[data-column="id"]');
    if (idHeader) {
        idHeader.classList.add('asc');
    }
    
    sortableHeaders.forEach(header => {
        header.addEventListener('click', function() {
            const column = this.getAttribute('data-column');
            
            // Remove classes de ordenação de outros cabeçalhos
            sortableHeaders.forEach(h => h.classList.remove('asc', 'desc'));
            
            // Determina a direção da ordenação
            if (currentSortColumnContatos === column) {
                currentSortDirectionContatos = currentSortDirectionContatos === 'asc' ? 'desc' : 'asc';
            } else {
                currentSortDirectionContatos = 'asc';
                currentSortColumnContatos = column;
            }
            
            // Adiciona classe visual ao cabeçalho
            this.classList.add(currentSortDirectionContatos);
            
            // Ordena os dados
            sortContatosData(column, currentSortDirectionContatos);
        });
    });
}

function sortContatosData(column, direction) {
    if (!contatosDataCache.length) {
        console.log('Nenhum dado para ordenar');
        return;
    }
    
    const sortedData = [...contatosDataCache].sort((a, b) => {
        let valueA, valueB;
        
        switch (column) {
            case 'id':
                valueA = parseInt(a[column]) || 0;
                valueB = parseInt(b[column]) || 0;
                break;
            default:
                valueA = (a[column] || '').toString().toLowerCase();
                valueB = (b[column] || '').toString().toLowerCase();
        }
        
        if (direction === 'asc') {
            return valueA < valueB ? -1 : valueA > valueB ? 1 : 0;
        } else {
            return valueA > valueB ? -1 : valueA < valueB ? 1 : 0;
        }
    });
    
    renderContatosTableAjax(sortedData);
}

function renderContatosTableAjax(contatos) {
    const contatosTableBody = document.getElementById('contatos-table-body');
    if (!contatosTableBody) return;
    
    if (!contatos || contatos.length === 0) {
        contatosTableBody.innerHTML = `
            <tr>
                <td colspan="5" style="text-align: center; padding: 40px; color: #666;">
                    Nenhum contato encontrado
                </td>
            </tr>
        `;
        return;
    }
    
    const linhas = contatos.map(contato => `
        <tr onclick="abrirModalContato('visualizar', ${contato.id})" style="cursor: pointer;">
            <td>${contato.id}</td>
            <td>${contato.nome || ''}</td>
            <td>${contato.cargo || ''}</td>
            <td>${contato.email || ''}</td>
            <td>${contato.telefone || ''}</td>
        </tr>
    `).join('');
    
    contatosTableBody.innerHTML = linhas;
}

// =================== INICIALIZAÇÃO CONTATOS ===================

function initContatos() {
    console.log('Inicializando funcionalidades de contatos...');
    
    // Verifica se estamos na página de contatos
    if (!document.getElementById('contatos-table-body')) {
        return;
    }
    
    // Configura busca em tempo real
    const searchInput = document.getElementById('contatos-search');
    if (searchInput && !searchInput.hasAttribute('data-listener-added')) {
        let searchTimeout;
        searchInput.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const termo = this.value.trim();
            
            searchTimeout = setTimeout(() => {
                if (termo.length >= 2) {
                    buscarContatosAjax(termo);
                } else if (termo.length === 0) {
                    carregarContatosAjax();
                }
            }, 300);
        });
        searchInput.setAttribute('data-listener-added', 'true');
    }
    
    // Inicializa ordenação dos cabeçalhos
    initTableSortingContatos();
    
    // Carrega dados via AJAX se não há dados no servidor
    const tableBody = document.getElementById('contatos-table-body');
    if (tableBody && tableBody.children.length <= 1) {
        carregarContatosAjax();
    }
}

// Executa quando a página carrega e quando muda de submenu
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initContatos);
} else {
    initContatos();
}
</script>

<?php
$content = ob_get_clean();

$additionalCSS = '<link rel="stylesheet" href="/Apps/assets/css/cadastro.css">';
$additionalJS  = '<script src="/Apps/assets/js/cadastro.js"></script>';

// Layout base
include __DIR__ . '/../views/layouts/main.php';
