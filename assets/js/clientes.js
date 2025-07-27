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
            clientesTableBody.innerHTML += `
                <tr>
                    <td>${cliente.id ? cliente.id : ''}</td>
                    <td>${cliente.nome ? cliente.nome : ''}</td>
                    <td>${cliente.cnpj ? cliente.cnpj : ''}</td>
                    <td>${cliente.link_bitrix ? cliente.link_bitrix : ''}</td>
                    <td>${cliente.email ? cliente.email : ''}</td>
                    <td>${cliente.telefone ? formatTelefone(cliente.telefone) : ''}</td>
                </tr>
            `;
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
