document.addEventListener('DOMContentLoaded', () => {
    // ...existing code...

    const searchInput = document.getElementById('clientes-search');
    const clientesTableBody = document.querySelector('#clientes-table tbody');
    const clientesLoader = document.getElementById('clientes-loader');

    console.log('Debug: searchInput =', searchInput);
    console.log('Debug: clientesTableBody =', clientesTableBody);
    console.log('Debug: clientesLoader =', clientesLoader);

    // Carrega todos os clientes ao inicializar a página
    carregarTodosClientes();

    // Função para mostrar alertas
    function mostrarAlerta(mensagem, tipo = 'success') {
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

        // Adiciona ao body
        document.body.appendChild(alerta);

        // Remove após 4 segundos
        setTimeout(() => {
            if (alerta && alerta.parentNode) {
                alerta.remove();
            }
        }, 4000);
    }

    function formatTelefone(telefone) {
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

    function renderClientesTable(clientes) {
        clientesTableBody.innerHTML = '';
        if (!clientes || clientes.length === 0) {
            clientesTableBody.innerHTML = '<tr><td colspan="6">Nenhum cliente encontrado.</td></tr>';
            return;
        }
        clientes.forEach(cliente => {
            const tr = document.createElement('tr');
            const linkBitrix = cliente.link_bitrix ? 
                `<a href="${cliente.link_bitrix}" target="_blank" class="link-bitrix" title="Clique para abrir no Bitrix">${cliente.link_bitrix}</a>` : 
                '';
            
            tr.innerHTML = `
                <td>${cliente.id ? cliente.id : ''}</td>
                <td>${cliente.nome ? cliente.nome : ''}</td>
                <td>${cliente.cnpj ? cliente.cnpj : ''}</td>
                <td>${linkBitrix}</td>
                <td>${cliente.email ? cliente.email : ''}</td>
                <td>${cliente.telefone ? formatTelefone(cliente.telefone) : ''}</td>
            `;
            tr.addEventListener('click', () => {
                abrirClienteModal(cliente.id);
            });
            clientesTableBody.appendChild(tr);
        });
    }

    // Função para abrir modal com dados completos do cliente
    function abrirClienteModal(clienteId) {
        console.log('Debug: Tentando abrir modal para cliente ID:', clienteId);
        
        const modal = document.getElementById('cliente-detail-modal');
        const modalBody = document.getElementById('cliente-detail-body');
        
        console.log('Debug: Modal encontrado:', modal);
        console.log('Debug: ModalBody encontrado:', modalBody);
        
        if (!modal || !modalBody) {
            console.error('Modal ou modalBody não encontrado');
            return;
        }
        
        modal.style.display = 'flex';
        clientesLoader.style.display = 'flex';
        
        console.log('Debug: Fazendo fetch para cliente ID:', clienteId);
        
        fetch(`/Apps/public/clientes_search.php?id=${encodeURIComponent(clienteId)}`)
            .then(res => res.json())
            .then(data => {
                clientesLoader.style.display = 'none';
                if (!data || !data.id) {
                    modalBody.innerHTML = '<div style="padding:32px">Cliente não encontrado.</div>';
                    return;
                }
                
                // Preenche o modal com dados do cliente
                modalBody.innerHTML = `
                    <button type="button" id="cliente-detail-close" class="cliente-detail-close">×</button>
                    <div class="cliente-modal-header">
                        <h2 class="cliente-modal-title">Dados do Cliente</h2>
                    </div>
                    <div class="cliente-modal-body">
                        <div class="cliente-modal-content">
                            <div class="cliente-modal-left">
                            <form id="cliente-edit-form">
                                <div>
                                    <label>ID:</label>
                                    <input type="text" value="${data.id}" disabled>
                                </div>
                                <div>
                                    <label>Nome:</label>
                                    <input type="text" name="nome" value="${data.nome || ''}" data-original="${data.nome || ''}">
                                </div>
                                <div>
                                    <label>CNPJ:</label>
                                    <input type="text" name="cnpj" value="${data.cnpj || ''}" data-original="${data.cnpj || ''}">
                                </div>
                                <div>
                                    <label>Link Bitrix:</label>
                                    <input type="text" name="link_bitrix" value="${data.link_bitrix || ''}" data-original="${data.link_bitrix || ''}">
                                </div>
                                <div>
                                    <label>Email:</label>
                                    <input type="text" name="email" value="${data.email || ''}" data-original="${data.email || ''}">
                                </div>
                                <div>
                                    <label>Telefone:</label>
                                    <input type="text" name="telefone" value="${data.telefone || ''}" data-original="${data.telefone || ''}">
                                </div>
                                <div>
                                    <label>Endereço:</label>
                                    <input type="text" name="endereco" value="${data.endereco || ''}" data-original="${data.endereco || ''}">
                                </div>
                            </form>
                        </div>
                        <div class="cliente-modal-right">
                            <h3>Aplicações</h3>
                            <div style="color:#aaa">(Em breve)</div>
                        </div>
                        </div>
                    </div>
                    <div id="modal-actions" class="modal-footer-actions" style="display: none;">
                        <button type="button" id="btn-salvar-modal">Salvar</button>
                        <button type="button" id="btn-cancelar-modal">Cancelar</button>
                    </div>
                `;
                
                // Event listeners para o formulário
                setupModalEvents(modal, data);
            })
            .catch(error => {
                console.error('Erro ao buscar cliente:', error);
                clientesLoader.style.display = 'none';
                modalBody.innerHTML = '<div style="padding:32px">Erro ao buscar dados do cliente.</div>';
            });
    }

    // Configura eventos do modal
    function setupModalEvents(modal, originalData) {
        let formAlterado = false;
        const modalActions = document.getElementById('modal-actions');
        const form = document.getElementById('cliente-edit-form');
        const btnSalvar = document.getElementById('btn-salvar-modal');
        const btnCancelar = document.getElementById('btn-cancelar-modal');
        
        // Monitora alterações nos campos
        const inputs = form.querySelectorAll('input[type="text"]:not([disabled])');
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
                    formAlterado = true;
                } else if (!hasChanges && modalActions.style.display !== 'none') {
                    modalActions.style.opacity = '0';
                    setTimeout(() => {
                        modalActions.style.display = 'none';
                    }, 300);
                    formAlterado = false;
                }
            });
        });

        // Botão salvar
        if (btnSalvar) {
            btnSalvar.addEventListener('click', function(e) {
                e.preventDefault();
                
                // Verifica se houve alterações
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
                    // Só fecha sem mostrar mensagem
                    modal.style.display = 'none';
                }
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

    // Salva dados do cliente
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
        clientesLoader.style.display = 'flex';

        fetch('/Apps/public/cliente_save.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(clienteData)
        })
        .then(res => res.json())
        .then(data => {
            clientesLoader.style.display = 'none';
            if (data.success) {
                mostrarAlerta('Dados salvos com sucesso!', 'success');
                modal.style.display = 'none';
                // Recarrega a tabela - se houver busca ativa, refaz a busca, senão carrega todos
                const termo = searchInput.value.trim();
                if (termo !== '') {
                    buscarClientes();
                } else {
                    carregarTodosClientes();
                }
            } else {
                mostrarAlerta('Erro ao salvar: ' + data.message, 'error');
            }
        })
        .catch(error => {
            clientesLoader.style.display = 'none';
            console.error('Erro ao salvar cliente:', error);
            mostrarAlerta('Erro ao salvar cliente. Tente novamente.', 'error');
        });
    }

    function buscarClientes() {
        console.log('Debug: buscarClientes() iniciada');
        const termo = searchInput.value.trim();
        
        console.log('Debug: termo de busca:', termo);
        clientesLoader.style.display = 'flex';
        clientesLoader.style.position = 'absolute';
        clientesLoader.style.top = '50%';
        clientesLoader.style.left = '50%';
        clientesLoader.style.transform = 'translate(-50%, -50%)';
        
        // Se termo está vazio, busca todos os clientes
        const url = termo === '' ? 
            '/Apps/public/clientes_search.php' : 
            `/Apps/public/clientes_search.php?q=${encodeURIComponent(termo)}`;
            
        console.log('Debug: Fazendo fetch para:', url);
        fetch(url)
            .then(res => {
                console.log('Debug: Resposta recebida:', res);
                return res.json();
            })
            .then(data => {
                console.log('Debug: Dados recebidos:', data);
                renderClientesTable(data);
                clientesLoader.style.display = 'none';
                clientesLoader.style.position = '';
                clientesLoader.style.top = '';
                clientesLoader.style.left = '';
                clientesLoader.style.transform = '';
            })
            .catch(error => {
                console.error('Debug: Erro na busca:', error);
                clientesTableBody.innerHTML = '<tr><td colspan="6">Erro ao buscar clientes.</td></tr>';
                clientesLoader.style.display = 'none';
                clientesLoader.style.position = '';
                clientesLoader.style.top = '';
                clientesLoader.style.left = '';
                clientesLoader.style.transform = '';
            });
    }

    // Função para carregar todos os clientes na inicialização
    function carregarTodosClientes() {
        console.log('Debug: Carregando todos os clientes...');
        clientesLoader.style.display = 'flex';
        
        fetch('/Apps/public/clientes_search.php')
            .then(res => res.json())
            .then(data => {
                console.log('Debug: Clientes carregados:', data);
                renderClientesTable(data);
                clientesLoader.style.display = 'none';
            })
            .catch(error => {
                console.error('Debug: Erro ao carregar clientes:', error);
                clientesTableBody.innerHTML = '<tr><td colspan="6">Erro ao carregar clientes.</td></tr>';
                clientesLoader.style.display = 'none';
            });
    }

    if (searchInput) {
        console.log('Debug: Adicionando eventos ao searchInput');
        searchInput.addEventListener('keydown', e => {
            console.log('Debug: Tecla pressionada:', e.key);
            if (e.key === 'Enter') {
                console.log('Debug: ENTER detectado, iniciando busca...');
                e.preventDefault();
                buscarClientes();
            }
        });
        searchInput.addEventListener('blur', () => {
            console.log('Debug: Campo perdeu foco, iniciando busca...');
            buscarClientes();
        });
        // Adiciona evento para detectar quando o campo é limpo
        searchInput.addEventListener('input', () => {
            const termo = searchInput.value.trim();
            console.log('Debug: Campo alterado, novo valor:', termo);
            // Se o campo foi completamente limpo, busca todos os clientes
            if (termo === '') {
                console.log('Debug: Campo limpo, carregando todos os clientes...');
                buscarClientes();
            }
        });
    } else {
        console.error('Debug: searchInput não encontrado!');
    }
});
