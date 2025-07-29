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
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: #666;">
                                Carregando contatos...
                            </td>
                        </tr>
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

    <!-- Modal de detalhes para contatos -->
    <div id="contato-detail-modal" class="contato-detail-modal" style="display:none;">
        <div class="contato-detail-overlay"></div>
        <div class="contato-detail-content">
            <div id="contato-detail-body">
                <!-- Conteúdo via JavaScript -->
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

    // Torna as funções de contatos acessíveis globalmente
    window.abrirModalContato = function(modo, contatoId = null) {
        console.log('Abrindo modal contato:', modo, contatoId);
        
        const modal = document.getElementById('contato-detail-modal');
        const modalBody = document.getElementById('contato-detail-body');
        
        if (!modal || !modalBody) {
            console.error('Modal de contato não encontrado');
            return;
        }
        
        modal.style.display = 'flex';
        
        // Cria o conteúdo do modal
        modalBody.innerHTML = \`
            <button class="contato-detail-close" onclick="fecharModalContato()">&times;</button>
            <div class="contato-modal-header">
                <h2 class="contato-modal-title">\${modo === 'criar' ? 'Criar Novo Contato' : modo === 'editar' ? 'Editar Contato' : 'Detalhes do Contato'}</h2>
            </div>
            <div class="contato-detail-content-grid">
                <div class="contato-modal-left">
                    <form id="contato-form">
                        <div>
                            <label for="contato-nome">Nome *</label>
                            <input type="text" id="contato-nome" name="nome" \${modo === 'visualizar' ? 'disabled' : ''} required>
                        </div>
                        <div>
                            <label for="contato-cargo">Cargo</label>
                            <input type="text" id="contato-cargo" name="cargo" \${modo === 'visualizar' ? 'disabled' : ''}>
                        </div>
                        <div>
                            <label for="contato-email">Email</label>
                            <input type="email" id="contato-email" name="email" \${modo === 'visualizar' ? 'disabled' : ''}>
                        </div>
                        <div>
                            <label for="contato-telefone">Telefone</label>
                            <input type="text" id="contato-telefone" name="telefone" \${modo === 'visualizar' ? 'disabled' : ''}>
                        </div>
                        \${modo !== 'criar' ? \`
                        <div>
                            <label for="contato-id-bitrix">ID Bitrix</label>
                            <input type="text" id="contato-id-bitrix" name="id_bitrix" disabled>
                        </div>\` : ''}
                    </form>
                </div>
                <div class="contato-modal-right">
                    <h3>Informações</h3>
                    <p>Preencha os dados do contato conforme necessário.</p>
                    \${modo === 'visualizar' ? '<p><small>Para editar, clique no botão "Editar".</small></p>' : ''}
                </div>
            </div>
            <div class="contato-modal-footer-actions" id="contato-modal-actions">
                \${modo === 'criar' ? \`
                    <button type="button" class="btn-criar-contato-modal" onclick="salvarContato()">Criar Contato</button>
                    <button type="button" class="btn-cancelar-contato-modal" onclick="fecharModalContato()">Cancelar</button>
                \` : modo === 'editar' ? \`
                    <button type="button" class="btn-criar-contato-modal" onclick="salvarContato(\${contatoId})">Salvar</button>
                    <button type="button" class="btn-cancelar-contato-modal" onclick="fecharModalContato()">Cancelar</button>
                \` : \`
                    <button type="button" class="btn-editar-contato" onclick="editarContato(\${contatoId})">Editar</button>
                    <button type="button" class="btn-cancelar-contato-modal" onclick="fecharModalContato()">Fechar</button>
                \`}
            </div>
        \`;
        
        // Mostra os botões
        const modalActions = modalBody.querySelector('#contato-modal-actions');
        if (modalActions) {
            modalActions.style.display = 'flex';
        }
        
        // Se for editar ou visualizar, carrega os dados
        if (contatoId && modo !== 'criar') {
            // Garante que os dados estão carregados antes de preencher
            if (contatosDataCache.length === 0) {
                console.log('Cache vazio, carregando dados...');
                fetch('/Apps/public/contatos_search.php')
                    .then(res => res.json())
                    .then(data => {
                        contatosDataCache = data;
                        setTimeout(() => carregarDadosContato(contatoId), 100);
                    })
                    .catch(error => {
                        console.error('Erro ao carregar dados:', error);
                    });
            } else {
                setTimeout(() => carregarDadosContato(contatoId), 100);
            }
        }
        
        // Event listeners
        const overlay = modal.querySelector('.contato-detail-overlay');
        if (overlay) {
            // Remove listeners antigos
            overlay.removeEventListener('click', fecharModalContato);
            overlay.addEventListener('click', fecharModalContato);
        }
        
        // Previne fechamento ao clicar no conteúdo
        const content = modal.querySelector('.contato-detail-content');
        if (content) {
            content.removeEventListener('click', function(e) { e.stopPropagation(); });
            content.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
    };

    window.fecharModalContato = function() {
        const modal = document.getElementById('contato-detail-modal');
        if (modal) {
            modal.style.display = 'none';
            // Limpa o conteúdo
            const modalBody = document.getElementById('contato-detail-body');
            if (modalBody) {
                modalBody.innerHTML = '';
            }
        }
    };

    window.editarContato = function(contatoId) {
        fecharModalContato();
        setTimeout(() => abrirModalContato('editar', contatoId), 100);
    };

    function carregarDadosContato(contatoId) {
        console.log('Carregando dados do contato:', contatoId);
        const contato = contatosDataCache.find(c => c.id == contatoId);
        if (contato) {
            console.log('Contato encontrado:', contato);
            const nomeField = document.getElementById('contato-nome');
            const cargoField = document.getElementById('contato-cargo');
            const emailField = document.getElementById('contato-email');
            const telefoneField = document.getElementById('contato-telefone');
            const idBitrixField = document.getElementById('contato-id-bitrix');
            
            if (nomeField) nomeField.value = contato.nome || '';
            if (cargoField) cargoField.value = contato.cargo || '';
            if (emailField) emailField.value = contato.email || '';
            if (telefoneField) telefoneField.value = contato.telefone_raw || '';
            if (idBitrixField) idBitrixField.value = contato.id_bitrix || '';
        } else {
            console.error('Contato não encontrado no cache:', contatoId);
        }
    }

    window.salvarContato = function(contatoId = null) {
        const form = document.getElementById('contato-form');
        const formData = new FormData(form);
        
        const url = contatoId ? \`/Apps/public/contato_update.php?id=\${contatoId}\` : '/Apps/public/contato_create.php';
        
        fetch(url, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(data.message);
                fecharModalContato();
                setTimeout(() => {
                    carregarContatosAjax(); // Recarrega a lista
                }, 500);
            } else {
                alert(data.message || 'Erro ao salvar contato');
            }
        })
        .catch(error => {
            console.error('Erro ao salvar contato:', error);
            alert('Erro ao salvar contato');
        });
    };

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
            body: \`term=\${encodeURIComponent(termo)}\`
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
            // Remove listeners antigos
            header.removeEventListener('click', handleSortClick);
            header.addEventListener('click', handleSortClick);
        });
        
        function handleSortClick() {
            const column = this.dataset.column;
            let direction = 'asc';
            
            // Se já está ordenado por esta coluna, inverte direção
            if (currentSortColumnContatos === column) {
                direction = currentSortDirectionContatos === 'asc' ? 'desc' : 'asc';
            }
            
            // Remove classes de todos os headers
            sortableHeaders.forEach(h => h.classList.remove('asc', 'desc'));
            
            // Adiciona classe no header atual
            this.classList.add(direction);
            
            // Ordena os dados
            sortContatosData(column, direction);
        }
    }

    function sortContatosData(column, direction) {
        currentSortColumnContatos = column;
        currentSortDirectionContatos = direction;
        
        const sortedData = [...contatosDataCache].sort((a, b) => {
            let aVal = a[column] || '';
            let bVal = b[column] || '';
            
            // Conversão para números se for ID
            if (column === 'id') {
                aVal = parseInt(aVal) || 0;
                bVal = parseInt(bVal) || 0;
            } else {
                aVal = aVal.toString().toLowerCase();
                bVal = bVal.toString().toLowerCase();
            }
            
            if (direction === 'asc') {
                return aVal > bVal ? 1 : aVal < bVal ? -1 : 0;
            } else {
                return aVal < bVal ? 1 : aVal > bVal ? -1 : 0;
            }
        });
        
        renderContatosTableAjax(sortedData);
    }

    function renderContatosTableAjax(contatos) {
        const contatosTableBody = document.getElementById('contatos-table-body');
        if (!contatosTableBody) return;
        
        if (!contatos || contatos.length === 0) {
            contatosTableBody.innerHTML = \`
                <tr>
                    <td colspan="5" style="text-align: center; padding: 40px; color: #666;">
                        Nenhum contato encontrado
                    </td>
                </tr>
            \`;
            return;
        }
        
        const linhas = contatos.map(contato => \`
            <tr onclick="abrirModalContato('visualizar', \${contato.id})" style="cursor: pointer;">
                <td>\${contato.id}</td>
                <td>\${contato.nome || ''}</td>
                <td>\${contato.cargo || ''}</td>
                <td>\${contato.email || ''}</td>
                <td>\${contato.telefone || ''}</td>
            </tr>
        \`).join('');
        
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

    // Inicializa imediatamente
    initContatos();
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
