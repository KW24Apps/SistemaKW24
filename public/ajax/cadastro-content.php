<?php
// AJAX endpoint para carregar conteúdo do cadastro
session_start();
require_once __DIR__ . '/../../includes/helpers.php';
requireAuthentication();

$sub = isset($_GET['sub']) ? $_GET['sub'] : 'clientes';

// Valida subpáginas
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
    require_once __DIR__ . '/../../dao/DAO.php';
    $dao = new DAO();
    $clientes = $dao->getClientesCampos();
}

// Retorna apenas o conteúdo (sem layout)
?>
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

    <!-- Reinicializa JavaScript dos clientes -->
    <script>
    // Recarrega funcionalidades específicas dos clientes
    if (typeof initClientesTable === 'function') {
        initClientesTable();
    }
    </script>

<?php elseif ($sub === 'contatos'): ?>
    <!-- CONTEÚDO CONTATOS -->
    <div class="contatos-page-wrapper">
        <div class="contatos-header">
            <h1>Contatos</h1>
            <div class="contatos-actions">
                <button id="btn-criar-contato" class="btn-criar-contato">
                    <i class="fas fa-plus"></i> Criar
                </button>
                <input type="text" id="contatos-search" class="contatos-search" placeholder="Filtrar e pesquisar contatos..." autocomplete="off">
            </div>
        </div>

        <div id="contatos-loader" class="contatos-loader" style="display:none">
            <span class="loading-spinner"></span>
            <span class="loading-text">Atualizando...</span>
        </div>

        <div class="contatos-container">
            <div id="contatos-table-wrapper" class="contatos-table-wrapper">
                <table id="contatos-table" class="contatos-table">
                    <thead>
                        <tr>
                            <th class="sortable" data-column="id">ID</th>
                            <th class="sortable" data-column="nome">Nome</th>
                            <th class="sortable" data-column="cargo">Cargo</th>
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
    <div id="contato-detail-modal" class="contato-detail-modal" style="display:none;">
        <div class="contato-detail-overlay"></div>
        <div class="contato-detail-content">
            <div id="contato-detail-body">
                <!-- Conteúdo via AJAX -->
            </div>
        </div>
    </div>

    <!-- Scripts para Contatos -->
    <script>
    // =================== VARIÁVEIS GLOBAIS PARA CONTATOS ===================
    let currentSortColumnContatos = 'id';
    let currentSortDirectionContatos = 'asc';
    let contatosDataCache = [];

    // =================== FUNÇÕES PRINCIPAIS CONTATOS ===================

    // Função para inicializar página de contatos - baseada na de clientes
    function initContatosPageAjax() {
        console.log('Inicializando página de contatos via AJAX...');
        
        // Elementos
        const searchInput = document.getElementById('contatos-search');
        const contatosTableBody = document.querySelector('#contatos-table tbody');
        const contatosLoader = document.getElementById('contatos-loader');
        
        if (!searchInput || !contatosTableBody || !contatosLoader) {
            console.error('Elementos da página de contatos não encontrados');
            return;
        }
        
        // Carrega todos os contatos ao inicializar
        carregarTodosContatosAjax();
        
        // Adiciona evento para o botão criar contato
        const btnCriarContato = document.getElementById('btn-criar-contato');
        if (btnCriarContato) {
            btnCriarContato.addEventListener('click', function() {
                abrirModalCriarContatoAjax();
            });
        }
        
        // Adiciona eventos de busca
        searchInput.addEventListener('keydown', e => {
            if (e.key === 'Enter') {
                e.preventDefault();
                const termo = searchInput.value.trim();
                if (termo.length >= 2) {
                    buscarContatosAjax(termo);
                } else {
                    carregarTodosContatosAjax();
                }
            }
        });
        
        // Remove event listeners duplicados se existirem
        const existingInputListener = searchInput.getAttribute('data-listener-added');
        if (!existingInputListener) {
            searchInput.setAttribute('data-listener-added', 'true');
        }
        
        // Inicializa ordenação dos cabeçalhos
        initTableSortingContatosAjax();
    }

    // Função para inicializar ordenação da tabela - baseada na de clientes
    function initTableSortingContatosAjax() {
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
                    // Inverte a direção se for a mesma coluna
                    currentSortDirectionContatos = currentSortDirectionContatos === 'asc' ? 'desc' : 'asc';
                } else {
                    // Nova coluna, sempre começa em ordem crescente
                    currentSortDirectionContatos = 'asc';
                    currentSortColumnContatos = column;
                }
                
                // Adiciona classe visual ao cabeçalho
                this.classList.add(currentSortDirectionContatos);
                
                // Ordena os dados
                sortContatosDataAjax(column, currentSortDirectionContatos);
            });
        });
    }

    // Função para ordenar dados dos contatos - baseada na de clientes
    function sortContatosDataAjax(column, direction) {
        if (!contatosDataCache.length) {
            console.log('Nenhum dado para ordenar');
            return;
        }
        
        const sortedData = [...contatosDataCache].sort((a, b) => {
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
                case 'cargo':
                    valueA = (a.cargo || '').toLowerCase();
                    valueB = (b.cargo || '').toLowerCase();
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
        renderContatosTableAjax(sortedData);
    }

    // Função para carregar todos os contatos via AJAX - baseada na de clientes
    function carregarTodosContatosAjax() {
        const contatosTableBody = document.querySelector('#contatos-table tbody');
        const contatosLoader = document.getElementById('contatos-loader');
        
        if (!contatosTableBody || !contatosLoader) return;
        
        console.log('Carregando todos os contatos via AJAX...');
        contatosLoader.style.display = 'flex';
        
        fetch('/Apps/public/contatos_search.php')
            .then(res => res.json())
            .then(data => {
                console.log('Contatos carregados via AJAX:', data);
                contatosDataCache = data; // Armazena no cache para ordenação
                
                // Aplica ordenação padrão por ID
                sortContatosDataAjax('id', 'asc');
                
                contatosLoader.style.display = 'none';
            })
            .catch(error => {
                console.error('Erro ao carregar contatos via AJAX:', error);
                contatosTableBody.innerHTML = '<tr><td colspan="5">Erro ao carregar contatos.</td></tr>';
                contatosLoader.style.display = 'none';
            });
    }

    // Função para buscar contatos via AJAX - baseada na de clientes
    function buscarContatosAjax(termo) {
        const contatosTableBody = document.querySelector('#contatos-table tbody');
        const contatosLoader = document.getElementById('contatos-loader');
        
        if (!contatosTableBody || !contatosLoader) return;
        
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
                contatosDataCache = data; // Armazena no cache para ordenação
                
                // Aplica ordenação padrão por ID nos resultados da busca
                sortContatosDataAjax('id', 'asc');
                
                contatosLoader.style.display = 'none';
            })
            .catch(error => {
                console.error('Erro na busca via AJAX:', error);
                contatosTableBody.innerHTML = '<tr><td colspan="5">Erro na busca.</td></tr>';
                contatosLoader.style.display = 'none';
            });
    }

    // Função para renderizar tabela de contatos via AJAX - baseada na de clientes
    function renderContatosTableAjax(contatos) {
        const contatosTableBody = document.querySelector('#contatos-table tbody');
        if (!contatosTableBody) return;
        
        if (!contatos || contatos.length === 0) {
            contatosTableBody.innerHTML = '<tr><td colspan="5">Nenhum contato encontrado.</td></tr>';
            return;
        }
        
        const rows = contatos.map(contato => {
            const telefoneFormatado = formatTelefoneAjax(contato.telefone || '');
            
            return `
                <tr data-contato-id="${contato.id}" class="contato-row">
                    <td>${contato.id || ''}</td>
                    <td>${contato.nome || ''}</td>
                    <td>${contato.cargo || ''}</td>
                    <td>${contato.email || ''}</td>
                    <td>${telefoneFormatado}</td>
                </tr>
            `;
        }).join('');
        
        contatosTableBody.innerHTML = rows;
        
        // Adiciona event listeners para abrir modal ao clicar nas linhas
        const contatoRows = contatosTableBody.querySelectorAll('.contato-row');
        contatoRows.forEach(row => {
            row.addEventListener('click', function(e) {
                const contatoId = this.getAttribute('data-contato-id');
                if (contatoId) {
                    abrirContatoModalAjax(contatoId);
                }
            });
            
            // Adiciona cursor pointer para indicar que é clicável
            row.style.cursor = 'pointer';
        });
    }

    // Função para formatar telefone via AJAX - baseada na de clientes
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

    // Torna as funções acessíveis globalmente - baseado no de clientes
    function abrirModalCriarContatoAjax() {
        console.log('Abrindo modal para criar novo contato...');
        abrirModalContatoAjax(null); // null indica criação de novo contato
    }

    // Função para mostrar alertas (estilo login) - copiada para uso no AJAX
    function mostrarAlertaAjax(mensagem, tipo = 'success') {
        // Remove alerta anterior se existir
        const alertaAnterior = document.querySelector('.alert-top');
        if (alertaAnterior) {
            alertaAnterior.remove();
        }

        // Cria novo alerta
        const alerta = document.createElement('div');
        alerta.className = `alert-top alert-${tipo}`;
        alerta.innerHTML = `
            <i class="fa fa-${tipo === 'success' ? 'check-circle' : 'exclamation-triangle'}"></i>
            ${mensagem}
        `;

        // Adiciona ao body com animação
        document.body.appendChild(alerta);
        
        // Animação inicial (estilo login)
        alerta.style.opacity = '0';
        alerta.style.transform = 'translateY(-30px)';
        
        setTimeout(() => {
            alerta.style.transition = 'opacity 0.5s, transform 0.5s';
            alerta.style.opacity = '1';
            alerta.style.transform = 'translateY(0)';
        }, 100);

        // Remove após 4 segundos com animação suave
        setTimeout(() => {
            alerta.style.opacity = '0';
            alerta.style.transform = 'translateY(-30px)';
            setTimeout(() => {
                if (alerta && alerta.parentNode) {
                    alerta.remove();
                }
            }, 500);
        }, 4000);
    }

    // =================== FUNÇÃO DE VERIFICAÇÃO DE MUDANÇAS ===================
    function marcarDadosOriginaisContatos(formData) {
        dadosOriginaisContatos = { ...formData };
        dadosAlteradosContatos = false;
    }

    function verificarMudancasContatos(formData) {
        const mudou = JSON.stringify(dadosOriginaisContatos) !== JSON.stringify(formData);
        dadosAlteradosContatos = mudou;
        return mudou;
    }

    // Função para tentar fechar modal verificando alterações - baseada na de clientes
    function tentarFecharModalContatoAjax(modal, form) {
        console.log('Tentando fechar modal de contato, verificando alterações...');
        
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
            mostrarModalConfirmacaoContatos(modal);
        } else {
            // Fecha diretamente se não há alterações
            modal.style.display = 'none';
        }
    }

    function adicionarDeteccaoMudancasContatos(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return;

        const inputs = modal.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('input', () => {
                const formData = obterDadosFormularioContatos(modalId);
                verificarMudancasContatos(formData);
            });
        });

        // Detecta tentativa de fechar modal
        const closeButtons = modal.querySelectorAll('.modal-close, .btn-cancel, [data-action="close"]');
        closeButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                if (!confirmarSaidaContatos()) {
                    e.preventDefault();
                    e.stopPropagation();
                    mostrarModalConfirmacaoContatos(modal);
                }
            });
        });
    }

    function obterDadosFormularioContatos(modalId) {
        const modal = document.getElementById(modalId);
        if (!modal) return {};

        const inputs = modal.querySelectorAll('input, textarea, select');
        const dados = {};
        inputs.forEach(input => {
            if (input.name) {
                dados[input.name] = input.value;
            }
        });
        return dados;
    }

    // Função para mostrar modal de confirmação para contatos
    function mostrarModalConfirmacaoContatos(modalOriginal) {
        // Remove modal anterior se existir
        const modalAnterior = document.getElementById('modal-confirmacao-salvar-contatos');
        if (modalAnterior) {
            modalAnterior.remove();
        }

        // Cria modal de confirmação
        const modalConfirmacao = document.createElement('div');
        modalConfirmacao.id = 'modal-confirmacao-salvar-contatos';
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
                    <button type="button" id="btn-salvar-e-fechar-contatos" class="btn-salvar-e-fechar">
                        <i class="fas fa-save"></i> Salvar
                    </button>
                    <button type="button" id="btn-descartar-e-fechar-contatos" class="btn-descartar-e-fechar">
                        <i class="fas fa-times"></i> Descartar
                    </button>
                    <button type="button" id="btn-cancelar-fechamento-contatos" class="btn-cancelar-fechamento">
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
        const btnSalvarEFechar = document.getElementById('btn-salvar-e-fechar-contatos');
        const btnDescartarEFechar = document.getElementById('btn-descartar-e-fechar-contatos');
        const btnCancelarFechamento = document.getElementById('btn-cancelar-fechamento-contatos');

        // Salvar e fechar
        if (btnSalvarEFechar) {
            btnSalvarEFechar.addEventListener('click', function() {
                const form = document.getElementById('contato-form');
                if (form) {
                    // Mostra loader enquanto salva
                    btnSalvarEFechar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Salvando...';
                    btnSalvarEFechar.disabled = true;
                    
                    salvarContatoAjax(form, modalOriginal);
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

    // Função universal para abrir modal (criar ou editar) - baseada na de clientes
    function abrirModalContatoAjax(contatoId) {
        const modal = document.getElementById('contato-detail-modal');
        const modalBody = document.getElementById('contato-detail-body');
        
        if (!modal || !modalBody) {
            console.error('Modal não encontrado');
            return;
        }
        
        modal.style.display = 'flex';
        
        if (contatoId) {
            // Modo edição - carrega dados do contato
            const contatosLoader = document.getElementById('contatos-loader');
            if (contatosLoader) {
                contatosLoader.style.display = 'flex';
            }
            
            console.log('Buscando contato ID:', contatoId);
            fetch(`/Apps/public/contatos_search.php?id=${contatoId}`)
                .then(res => {
                    console.log('Response status:', res.status);
                    return res.json();
                })
                .then(data => {
                    console.log('Dados recebidos:', data);
                    if (contatosLoader) {
                        contatosLoader.style.display = 'none';
                    }
                    
                    if (data.error) {
                        console.error('Erro na resposta:', data.message);
                        modalBody.innerHTML = '<div style="padding:32px">Erro: ' + data.message + '</div>';
                    } else {
                        renderModalContatoAjax(data, false); // false = modo edição
                    }
                })
                .catch(error => {
                    console.error('Erro ao buscar contato:', error);
                    if (contatosLoader) {
                        contatosLoader.style.display = 'none';
                    }
                    modalBody.innerHTML = '<div style="padding:32px">Erro ao buscar dados do contato.</div>';
                });
        } else {
            // Modo criação - dados vazios
            const dadosVazios = {
                id: '',
                nome: '',
                cargo: '',
                email: '',
                telefone: '',
                id_bitrix: ''
            };
            renderModalContatoAjax(dadosVazios, true); // true = modo criação
        }
    }

    // Renderiza o conteúdo do modal (criação ou edição) - baseada na de clientes
    function renderModalContatoAjax(data, isCriacao) {
        const modalBody = document.getElementById('contato-detail-body');
        const titulo = isCriacao ? 'Criar Novo Contato' : `Contato - ${data.nome || 'ID ' + data.id}`;
        
        console.log('Renderizando modal:', { data, isCriacao, titulo });
        
        modalBody.innerHTML = `
            <div class="contato-detail-header">
                <h2>${titulo}</h2>
                <button type="button" id="contato-detail-close" class="contato-detail-close">×</button>
            </div>
            <div class="contato-detail-content-grid">
                <div class="contato-modal-left">
                    <form id="contato-form">
                        ${!isCriacao ? `
                            <div>
                                <label>ID:</label>
                                <input type="text" value="${data.id || ''}" disabled>
                            </div>
                        ` : ''}
                        <div>
                            <label>Nome:${isCriacao ? ' <span style="color: red;">*</span>' : ''}</label>
                            <input type="text" name="nome" value="${data.nome || ''}" data-original="${data.nome || ''}" ${isCriacao ? 'placeholder="Digite o nome do contato" required' : ''}>
                        </div>
                        <div>
                            <label>Cargo:</label>
                            <input type="text" name="cargo" value="${data.cargo || ''}" data-original="${data.cargo || ''}" ${isCriacao ? 'placeholder="Digite o cargo"' : ''}>
                        </div>
                        <div>
                            <label>Email:</label>
                            <input type="email" name="email" value="${data.email || ''}" data-original="${data.email || ''}" ${isCriacao ? 'placeholder="email@exemplo.com"' : ''}>
                        </div>
                        <div>
                            <label>Telefone:</label>
                            <input type="text" name="telefone" value="${data.telefone_raw || data.telefone || ''}" data-original="${data.telefone_raw || data.telefone || ''}" ${isCriacao ? 'placeholder="(11) 99999-9999"' : ''}>
                        </div>
                        ${!isCriacao ? `
                            <div>
                                <label>ID Bitrix:</label>
                                <input type="text" name="id_bitrix" value="${data.id_bitrix || ''}" data-original="${data.id_bitrix || ''}" disabled>
                            </div>
                        ` : ''}
                    </form>
                </div>
                <div class="contato-modal-right">
                    <h3>Informações</h3>
                    <div style="color:#aaa">${isCriacao ? 'Preencha os dados do contato.' : 'Para editar, altere os campos e clique em Salvar.'}</div>
                </div>
            </div>
            <div id="modal-actions" class="contato-modal-footer-actions" style="display: ${isCriacao ? 'flex' : 'none'}; opacity: ${isCriacao ? '1' : '0'};">
                <button type="button" id="btn-salvar-modal">${isCriacao ? '<i class="fas fa-save"></i> Criar Contato' : 'Salvar'}</button>
                <button type="button" id="btn-cancelar-modal">Cancelar</button>
            </div>
        `;
        
        console.log('Modal HTML criado. Dados preenchidos:', {
            nome: data.nome,
            cargo: data.cargo,
            email: data.email,
            telefone: data.telefone_raw || data.telefone,
            id_bitrix: data.id_bitrix
        });
        console.log('Configurando eventos...');
        
        // Configura eventos do modal
        setupModalEventosUniversalContatosAjax(document.getElementById('contato-detail-modal'), data, isCriacao);
    }

    // Eventos universais do modal (criação ou edição) - baseados nos de clientes
    function setupModalEventosUniversalContatosAjax(modal, originalData, isCriacao) {
        console.log('Configurando eventos do modal:', { isCriacao });
        
        const modalActions = document.getElementById('modal-actions');
        const form = document.getElementById('contato-form');
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
                        const atual = inp.value.trim();
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
                    mostrarAlertaAjax('O nome do contato é obrigatório!', 'error');
                        nomeInput.focus();
                        return;
                    }
                    criarContatoAjax(form, modal);
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
                        salvarContatoAjax(form, modal);
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
                    // Para edição, usa a função de verificação
                    tentarFecharModalContatoAjax(modal, form);
                }
            });
        }
        
        // Botão fechar (X)
        const btnFechar = document.getElementById('contato-detail-close');
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
                    // Para edição, usa a função de verificação
                    tentarFecharModalContatoAjax(modal, form);
                }
            };
        }
        
        // Fechar ao clicar fora da área
        const overlay = modal.querySelector('.contato-detail-overlay');
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
                    // Para edição, usa a função de verificação
                    tentarFecharModalContatoAjax(modal, form);
                }
            });
        }
        
        // Marca dados originais para detectar mudanças (só para edição)
        if (!isCriacao) {
            const dadosOriginais = {
                nome: originalData.nome || '',
                cargo: originalData.cargo || '',
                email: originalData.email || '',
                telefone: originalData.telefone_raw || originalData.telefone || ''
            };
            marcarDadosOriginaisContatos(dadosOriginais);
            
            // Adiciona detecção de mudanças
            adicionarDeteccaoMudancasContatos('contato-detail-modal');
        }
    }

    // Função para criar contato - baseada na de clientes
    function criarContatoAjax(form, modal) {
        const formData = new FormData(form);
        const contatoData = {
            nome: formData.get('nome'),
            cargo: formData.get('cargo'),
            email: formData.get('email'),
            telefone: formData.get('telefone')
        };
        
        // Mostra loader no botão
        const btnSalvar = document.getElementById('btn-salvar-modal');
        if (btnSalvar) {
            btnSalvar.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Criando...';
            btnSalvar.disabled = true;
        }
        
        fetch('/Apps/public/contato_create.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(contatoData)
        })
        .then(res => res.json())
        .then(data => {
            if (btnSalvar) {
                btnSalvar.innerHTML = '<i class="fas fa-save"></i> Criar Contato';
                btnSalvar.disabled = false;
            }
            
            if (data.success) {
                mostrarAlertaAjax('Contato criado com sucesso!', 'success');
                dadosAlteradosContatos = false; // Reset do controle de mudanças
                modal.style.display = 'none';
                
                // Recarrega a tabela para mostrar o novo contato
                carregarTodosContatosAjax();
            } else {
                mostrarAlertaAjax('Erro ao criar contato: ' + (data.message || 'Erro desconhecido'), 'error');
            }
        })
        .catch(error => {
            console.error('Erro ao criar contato:', error);
            
            if (btnSalvar) {
                btnSalvar.innerHTML = '<i class="fas fa-save"></i> Criar Contato';
                btnSalvar.disabled = false;
            }
            
            mostrarAlertaAjax('Erro ao criar contato. Tente novamente.', 'error');
        });
    }

    // Função para salvar contato - baseada na de clientes
    function salvarContatoAjax(form, modal) {
        const formData = new FormData(form);
        const contatoId = form.querySelector('input[disabled]').value;
        const contatoData = {
            nome: formData.get('nome'),
            cargo: formData.get('cargo'),
            email: formData.get('email'),
            telefone: formData.get('telefone')
        };

        console.log('Salvando contato:', { contatoId, contatoData });

        // Mostra loader
        const contatosLoader = document.getElementById('contatos-loader');
        if (contatosLoader) {
            contatosLoader.style.display = 'flex';
        }

        fetch(`/Apps/public/contato_update.php?id=${contatoId}`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(contatoData)
        })
        .then(res => res.json())
        .then(data => {
            if (contatosLoader) {
                contatosLoader.style.display = 'none';
            }
            if (data.success) {
                mostrarAlertaAjax('Dados salvos com sucesso!', 'success');
                
                // Reset dos dados originais para refletir as mudanças salvas
                const form = document.getElementById('contato-form');
                if (form) {
                    const inputs = form.querySelectorAll('input[type="text"]:not([disabled]), input[type="email"]');
                    inputs.forEach(input => {
                        input.setAttribute('data-original', input.value.trim());
                    });
                }
                
                modal.style.display = 'none';
                // Recarrega a tabela
                const termo = document.getElementById('contatos-search') ? document.getElementById('contatos-search').value.trim() : '';
                if (termo !== '') {
                    buscarContatosAjax(termo);
                } else {
                    carregarTodosContatosAjax();
                }
            } else {
                mostrarAlertaAjax('Erro ao salvar: ' + data.message, 'error');
            }
        })
        .catch(error => {
            if (contatosLoader) {
                contatosLoader.style.display = 'none';
            }
            console.error('Erro ao salvar contato:', error);
            mostrarAlertaAjax('Erro ao salvar contato. Tente novamente.', 'error');
        });
    }

    // Atualiza a função global existente para usar a nova função universal
    function abrirContatoModalAjax(contatoId) {
        abrirModalContatoAjax(contatoId);
    }

    // Função para tentar fechar modal verificando alterações - baseada na de clientes
    function tentarFecharModalContatoAjax(modal, form) {
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
            // Pergunta se quer salvar ou descartar
            if (confirm('Você tem alterações não salvas. Descartar alterações?')) {
                modal.style.display = 'none';
            }
        } else {
            // Fecha diretamente se não há alterações
            modal.style.display = 'none';
        }
    }

    // Inicializa imediatamente
    initContatosPageAjax();
    </script>

<?php elseif ($sub === 'aplicacoes'): ?>
    <!-- CONTEÚDO APLICAÇÕES -->
    <div class="aplicacoes-container">
        <h1>Aplicações</h1>
        <p>Área de gerenciamento de aplicações.</p>
        <div class="aplicacoes-actions">
            <button class="btn-nova-aplicacao">Nova Aplicação</button>
        </div>
        <!-- Aqui será implementado o conteúdo de aplicações -->
    </div>

<?php endif; ?>
