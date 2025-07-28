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
