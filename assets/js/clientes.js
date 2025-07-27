document.addEventListener('DOMContentLoaded', () => {
    // ...existing code...

    const searchInput = document.getElementById('clientes-search');
    const clientesTableBody = document.querySelector('#clientes-table tbody');
    const clientesLoader = document.getElementById('clientes-loader');

    console.log('Debug: searchInput =', searchInput);
    console.log('Debug: clientesTableBody =', clientesTableBody);
    console.log('Debug: clientesLoader =', clientesLoader);

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
            tr.innerHTML = `
                <td>${cliente.id ? cliente.id : ''}</td>
                <td>${cliente.nome ? cliente.nome : ''}</td>
                <td>${cliente.cnpj ? cliente.cnpj : ''}</td>
                <td>${cliente.link_bitrix ? cliente.link_bitrix : ''}</td>
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
                    <div class="cliente-modal-content">
                        <div class="cliente-modal-left">
                            <h2>Dados do Cliente</h2>
                            <form id="cliente-edit-form">
                                <div>
                                    <label>ID:</label>
                                    <input type="text" value="${data.id}" disabled>
                                </div>
                                <div>
                                    <label>Nome:</label>
                                    <input type="text" name="nome" value="${data.nome || ''}">
                                </div>
                                <div>
                                    <label>CNPJ:</label>
                                    <input type="text" name="cnpj" value="${data.cnpj || ''}">
                                </div>
                                <div>
                                    <label>Link Bitrix:</label>
                                    <input type="text" name="link_bitrix" value="${data.link_bitrix || ''}">
                                </div>
                                <div>
                                    <label>Email:</label>
                                    <input type="text" name="email" value="${data.email || ''}">
                                </div>
                                <div>
                                    <label>Telefone:</label>
                                    <input type="text" name="telefone" value="${data.telefone || ''}">
                                </div>
                                <div>
                                    <label>Endereço:</label>
                                    <input type="text" name="endereco" value="${data.endereco || ''}">
                                </div>
                                <div style="margin-top:18px;display:flex;gap:12px;justify-content:flex-end;">
                                    <button type="submit" class="btn-aplicar-filtro">Salvar</button>
                                    <button type="button" class="btn-fechar-filtro" id="btn-cancelar-edicao">Cancelar</button>
                                </div>
                            </form>
                        </div>
                        <div class="cliente-modal-right">
                            <h3>Aplicações</h3>
                            <div style="color:#aaa">(Em breve)</div>
                        </div>
                    </div>
                `;
                
                // Event listeners para o formulário
                setupModalEvents(modal);
            })
            .catch(error => {
                console.error('Erro ao buscar cliente:', error);
                clientesLoader.style.display = 'none';
                modalBody.innerHTML = '<div style="padding:32px">Erro ao buscar dados do cliente.</div>';
            });
    }

    // Configura eventos do modal
    function setupModalEvents(modal) {
        // Formulário de edição
        const form = document.getElementById('cliente-edit-form');
        if (form) {
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                salvarCliente(form, modal);
            });
        }

        // Botão cancelar
        const btnCancelar = document.getElementById('btn-cancelar-edicao');
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
                alert('Cliente salvo com sucesso!');
                modal.style.display = 'none';
                // Recarrega a tabela se houver busca ativa
                const termo = searchInput.value.trim();
                if (termo !== '') {
                    buscarClientes();
                }
            } else {
                alert('Erro ao salvar: ' + data.message);
            }
        })
        .catch(error => {
            clientesLoader.style.display = 'none';
            console.error('Erro ao salvar cliente:', error);
            alert('Erro ao salvar cliente. Tente novamente.');
        });
    }

    function buscarClientes() {
        console.log('Debug: buscarClientes() iniciada');
        const termo = searchInput.value.trim();
        if (termo === '') {
            console.log('Debug: Campo de busca vazio, não pesquisar.');
            return;
        }
        console.log('Debug: termo de busca:', termo);
        clientesLoader.style.display = 'flex';
        clientesLoader.style.position = 'absolute';
        clientesLoader.style.top = '50%';
        clientesLoader.style.left = '50%';
        clientesLoader.style.transform = 'translate(-50%, -50%)';
        console.log('Debug: Fazendo fetch para:', `/Apps/public/clientes_search.php?q=${encodeURIComponent(termo)}`);
        fetch(`/Apps/public/clientes_search.php?q=${encodeURIComponent(termo)}`)
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
    } else {
        console.error('Debug: searchInput não encontrado!');
    }
});
